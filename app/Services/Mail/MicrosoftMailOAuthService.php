<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailAccountStatus;
use App\Filament\Resources\Mail\MailAccounts\MailAccountResource;
use App\Jobs\Mail\SyncEmailAccountJob;
use App\Models\Mail\MailAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class MicrosoftMailOAuthService
{
    private const STATE_CACHE_PREFIX = 'microsoft_mail_oauth_state:';

    private const JWKS_CACHE_PREFIX = 'microsoft_mail_jwks:';

    private const VERIFYING_CACHE_PREFIX = 'mail_account_verifying:';

    private const REFRESH_LOCK_PREFIX = 'mail_oauth_refresh:';

    public const AUTH_SCOPES = [
        'openid',
        'profile',
        'email',
        'offline_access',
        'https://outlook.office.com/IMAP.AccessAsUser.All',
        'https://graph.microsoft.com/Mail.Send',
    ];

    public const TOKEN_SCOPES = [
        'openid',
        'profile',
        'email',
        'offline_access',
        'https://outlook.office.com/IMAP.AccessAsUser.All',
    ];

    public const GRAPH_SCOPES = [
        'offline_access',
        'https://graph.microsoft.com/Mail.Send',
    ];

    public function isConfigured(): bool
    {
        return filled($this->clientId())
            && filled($this->clientSecret())
            && filled($this->redirectUri());
    }

    public function clientId(): string
    {
        return trim((string) config('services.microsoft_mail.client_id', ''));
    }

    public function clientSecret(): string
    {
        return trim((string) config('services.microsoft_mail.client_secret', ''));
    }

    public function redirectUri(): string
    {
        $configured = trim((string) config('services.microsoft_mail.redirect_uri', ''));

        if ($configured !== '') {
            return $configured;
        }

        return route('mail.microsoft.callback');
    }

    public function tenant(): string
    {
        $tenant = trim((string) config('services.microsoft_mail.tenant', 'common'));

        return $tenant !== '' ? $tenant : 'common';
    }

    public static function isVerifying(int $accountId): bool
    {
        return Cache::has(self::VERIFYING_CACHE_PREFIX.$accountId);
    }

    public static function clearVerifying(int $accountId): void
    {
        Cache::forget(self::VERIFYING_CACHE_PREFIX.$accountId);
    }

    /**
     * @return array{url: string, state: string}
     */
    public function beginAuthorization(MailAccount $account, User $actor, ?string $returnUrl = null): array
    {
        Gate::forUser($actor)->authorize('update', $account);

        if (! $this->isConfigured()) {
            throw new RuntimeException(__('mail_inbox.oauth.not_configured'));
        }

        if (! $account->isOutlookOAuth()) {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_account_type'));
        }

        $state = Str::random(48);
        $codeVerifier = Str::random(64);
        $nonce = Str::random(40);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $safeReturnUrl = $this->sanitizeReturnUrl($returnUrl, $account);
        $sessionBinding = (string) (session()->get('microsoft_mail_oauth_binding') ?: Str::random(32));
        session()->put('microsoft_mail_oauth_binding', $sessionBinding);

        Cache::put(
            self::STATE_CACHE_PREFIX.$state,
            [
                'session_binding' => $sessionBinding,
                'user_id' => $actor->id,
                'account_id' => $account->id,
                'company_id' => (int) $account->company_id,
                'email' => strtolower(trim((string) $account->email)),
                'type' => $account->type,
                'code_verifier' => $codeVerifier,
                'nonce' => $nonce,
                'return_url' => $safeReturnUrl,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ],
            now()->addMinutes(10)
        );

        $query = http_build_query([
            'client_id' => $this->clientId(),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'response_mode' => 'query',
            'scope' => implode(' ', self::AUTH_SCOPES),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'nonce' => $nonce,
            'prompt' => 'select_account',
            'login_hint' => (string) $account->email,
        ], '', '&', PHP_QUERY_RFC3986);

        $authorizeUrl = sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?%s',
            rawurlencode($this->tenant()),
            $query
        );

        return [
            'url' => $authorizeUrl,
            'state' => $state,
        ];
    }

    /**
     * @return array{success: bool, account: ?MailAccount, message: string, redirect_url: string}
     */
    public function handleCallback(Request $request, User $actor): array
    {
        $fallbackUrl = MailAccountResource::getUrl('index');
        $state = (string) $request->query('state', '');

        if ($state === '') {
            return [
                'success' => false,
                'account' => null,
                'message' => __('mail_inbox.oauth.invalid_state'),
                'redirect_url' => $fallbackUrl,
            ];
        }

        $payload = Cache::pull(self::STATE_CACHE_PREFIX.$state);

        if (! is_array($payload) || ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return [
                'success' => false,
                'account' => null,
                'message' => __('mail_inbox.oauth.expired_state'),
                'redirect_url' => $fallbackUrl,
            ];
        }

        $currentBinding = (string) session()->get('microsoft_mail_oauth_binding', '');
        if ($currentBinding === '' || ($payload['session_binding'] ?? '') !== $currentBinding || (int) ($payload['user_id'] ?? 0) !== (int) $actor->id) {
            return [
                'success' => false,
                'account' => null,
                'message' => __('mail_inbox.oauth.invalid_state'),
                'redirect_url' => $fallbackUrl,
            ];
        }

        $account = MailAccount::find((int) ($payload['account_id'] ?? 0));
        if (! $account) {
            return [
                'success' => false,
                'account' => null,
                'message' => __('mail_inbox.oauth.account_not_found'),
                'redirect_url' => $fallbackUrl,
            ];
        }

        $redirectUrl = $this->sanitizeReturnUrl($payload['return_url'] ?? null, $account);

        if (Gate::forUser($actor)->denies('update', $account)) {
            return [
                'success' => false,
                'account' => $account,
                'message' => __('mail_inbox.oauth.unauthorized'),
                'redirect_url' => $fallbackUrl,
            ];
        }

        if (
            (int) $account->company_id !== (int) ($payload['company_id'] ?? 0)
            || strtolower(trim((string) $account->email)) !== (string) ($payload['email'] ?? '')
            || $account->type !== ($payload['type'] ?? '')
            || ! $account->isOutlookOAuth()
        ) {
            return [
                'success' => false,
                'account' => $account,
                'message' => __('mail_inbox.oauth.account_changed'),
                'redirect_url' => $redirectUrl,
            ];
        }

        if ($request->filled('error')) {
            $errorCode = (string) $request->query('error');
            $isCancelled = $errorCode === 'access_denied';

            Log::info('Microsoft OAuth callback returned error', [
                'account_id' => $account->id,
                'error' => $errorCode,
            ]);

            if (! $account->hasOAuthTokens()) {
                $account->update([
                    'status' => MailAccountStatus::PENDING_AUTH,
                    'error_message' => $isCancelled
                        ? __('mail_inbox.oauth.cancelled')
                        : __('mail_inbox.oauth.provider_error'),
                ]);
            }

            return [
                'success' => false,
                'account' => $account,
                'message' => $isCancelled
                    ? __('mail_inbox.oauth.cancelled')
                    : __('mail_inbox.oauth.provider_error'),
                'redirect_url' => $redirectUrl,
            ];
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return [
                'success' => false,
                'account' => $account,
                'message' => __('mail_inbox.oauth.missing_code'),
                'redirect_url' => $redirectUrl,
            ];
        }

        try {
            $tokenResponse = $this->exchangeCodeForTokens($code, (string) $payload['code_verifier']);
            $idToken = (string) ($tokenResponse['id_token'] ?? '');
            $accessToken = (string) ($tokenResponse['access_token'] ?? '');
            $refreshToken = (string) ($tokenResponse['refresh_token'] ?? '');
            $expiresIn = max(60, (int) ($tokenResponse['expires_in'] ?? 3600));

            if ($accessToken === '' || $refreshToken === '' || $idToken === '') {
                throw new RuntimeException(__('mail_inbox.oauth.incomplete_tokens'));
            }

            $this->verifyIdToken($idToken, (string) $payload['nonce'], (string) $account->email);

            $account->update([
                'oauth_access_token' => $accessToken,
                'oauth_refresh_token' => $refreshToken,
                'oauth_token_expires_at' => now()->addSeconds($expiresIn),
                'status' => MailAccountStatus::CONNECTED,
                'error_message' => null,
            ]);

            Cache::put(self::VERIFYING_CACHE_PREFIX.$account->id, true, now()->addMinutes(3));
            SyncEmailAccountJob::dispatch($account, verifyOnly: ! $account->is_active)->afterCommit();

            return [
                'success' => true,
                'account' => $account->fresh(),
                'message' => __('mail_inbox.oauth.connected_verifying'),
                'redirect_url' => $redirectUrl,
            ];
        } catch (\Throwable $e) {
            Log::warning('Microsoft OAuth callback failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            if (! $account->hasOAuthTokens()) {
                $account->update([
                    'status' => MailAccountStatus::PENDING_AUTH,
                    'error_message' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'account' => $account,
                'message' => $e->getMessage(),
                'redirect_url' => $redirectUrl,
            ];
        }
    }

    public function ensureValidAccessToken(MailAccount $account): string
    {
        if (! $account->isOutlookOAuth()) {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_account_type'));
        }

        if (
            filled($account->oauth_access_token)
            && $account->oauth_token_expires_at?->gt(now()->addMinutes(5))
        ) {
            return (string) $account->oauth_access_token;
        }

        if (empty($account->oauth_refresh_token)) {
            throw new RuntimeException(__('mail_inbox.sync.oauth_incomplete'));
        }

        $lock = Cache::lock(self::REFRESH_LOCK_PREFIX.$account->id, 30);
        if (! $lock->get()) {
            // Another worker is refreshing; re-read briefly after waiting
            usleep(300000);
            $fresh = MailAccount::find($account->id);
            if ($fresh && filled($fresh->oauth_access_token) && $fresh->oauth_token_expires_at?->gt(now()->addMinutes(2))) {
                return (string) $fresh->oauth_access_token;
            }

            throw new RuntimeException(__('mail_inbox.oauth.refresh_locked'));
        }

        try {
            $fresh = MailAccount::find($account->id);
            if (! $fresh || ! $fresh->isOutlookOAuth() || empty($fresh->oauth_refresh_token)) {
                throw new RuntimeException(__('mail_inbox.sync.oauth_incomplete'));
            }

            if (filled($fresh->oauth_access_token) && $fresh->oauth_token_expires_at?->gt(now()->addMinutes(5))) {
                return (string) $fresh->oauth_access_token;
            }

            $usedRefreshToken = (string) $fresh->oauth_refresh_token;
            $response = $this->httpClient()
                ->asForm()
                ->timeout(20)
                ->post($this->tokenEndpoint(), [
                    'client_id' => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $usedRefreshToken,
                    'scope' => implode(' ', self::TOKEN_SCOPES),
                ]);

            if (! $response->successful()) {
                $body = $response->json();
                $errorCode = is_array($body) ? (string) ($body['error'] ?? '') : '';

                if (in_array($errorCode, ['invalid_grant', 'interaction_required', 'invalid_client', 'unauthorized_client'], true)) {
                    $this->markReauthRequired($fresh->id, $usedRefreshToken, __('mail_inbox.oauth.reauth_needed'));
                    throw new RuntimeException(__('mail_inbox.oauth.reauth_needed'));
                }

                throw new RuntimeException(__('mail_inbox.oauth.refresh_transient_failed'));
            }

            $data = $response->json();
            $newAccessToken = (string) ($data['access_token'] ?? '');
            $newRefreshToken = (string) ($data['refresh_token'] ?? '');
            $expiresIn = max(60, (int) ($data['expires_in'] ?? 3600));

            if ($newAccessToken === '') {
                throw new RuntimeException(__('mail_inbox.oauth.refresh_transient_failed'));
            }

            $latest = MailAccount::find($fresh->id);
            if (
                ! $latest
                || ! $latest->isOutlookOAuth()
                || $latest->oauth_refresh_token !== $usedRefreshToken
            ) {
                throw new RuntimeException(__('mail_inbox.oauth.token_stale_after_disconnect'));
            }

            $latest->update([
                'oauth_access_token' => $newAccessToken,
                'oauth_refresh_token' => $newRefreshToken !== '' ? $newRefreshToken : $usedRefreshToken,
                'oauth_token_expires_at' => now()->addSeconds($expiresIn),
            ]);

            return $newAccessToken;
        } finally {
            $lock->release();
        }
    }

    public function ensureValidGraphAccessToken(MailAccount $account): string
    {
        $cacheKey = 'microsoft_graph_token:' . $account->id;
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && filled($cached)) {
            return $cached;
        }

        $fresh = MailAccount::find($account->id);
        if (! $fresh || ! $fresh->isOutlookOAuth() || empty($fresh->oauth_refresh_token)) {
            throw new RuntimeException(__('mail_inbox.sync.oauth_incomplete'));
        }

        $usedRefreshToken = (string) $fresh->oauth_refresh_token;
        $response = $this->httpClient()
            ->asForm()
            ->timeout(20)
            ->post($this->tokenEndpoint(), [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'grant_type' => 'refresh_token',
                'refresh_token' => $usedRefreshToken,
                'scope' => implode(' ', self::GRAPH_SCOPES),
            ]);

        if (! $response->successful()) {
            $body = $response->json();
            $errorCode = is_array($body) ? (string) ($body['error'] ?? '') : '';
            $errorDesc = is_array($body) ? (string) ($body['error_description'] ?? '') : $response->body();

            if (in_array($errorCode, ['invalid_grant', 'interaction_required', 'invalid_client', 'unauthorized_client'], true)) {
                $this->markReauthRequired($fresh->id, $usedRefreshToken, __('mail_inbox.oauth.reauth_needed'));
                throw new RuntimeException(__('mail_inbox.oauth.reauth_needed'));
            }

            Log::error('Microsoft Graph token refresh failed', ['error' => $errorCode, 'description' => $errorDesc]);
            throw new RuntimeException("Failed to acquire Microsoft Graph token: {$errorDesc}");
        }

        $data = (array) $response->json();
        $accessToken = (string) ($data['access_token'] ?? '');
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        if (blank($accessToken)) {
            throw new RuntimeException(__('mail_inbox.oauth.token_exchange_failed'));
        }

        if (isset($data['refresh_token']) && filled($data['refresh_token'])) {
            $fresh->update([
                'oauth_refresh_token' => (string) $data['refresh_token'],
            ]);
        }

        Cache::put($cacheKey, $accessToken, now()->addSeconds(max(60, $expiresIn - 300)));

        return $accessToken;
    }

    public function disconnect(MailAccount $account, User $actor): void
    {
        Gate::forUser($actor)->authorize('update', $account);

        $account->update([
            'oauth_access_token' => null,
            'oauth_refresh_token' => null,
            'oauth_token_expires_at' => null,
            'status' => MailAccountStatus::REAUTH_REQUIRED,
            'error_message' => null,
        ]);

        self::clearVerifying($account->id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function exchangeCodeForTokens(string $code, string $codeVerifier): array
    {
        $response = $this->httpClient()
            ->asForm()
            ->timeout(20)
            ->post($this->tokenEndpoint(), [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'code_verifier' => $codeVerifier,
                'scope' => implode(' ', self::TOKEN_SCOPES),
            ]);

        if (! $response->successful()) {
            $body = $response->json();
            $errorCode = is_array($body) ? (string) ($body['error'] ?? 'token_exchange_failed') : 'token_exchange_failed';
            Log::warning('Microsoft token exchange failed', ['error' => $errorCode, 'status' => $response->status()]);
            throw new RuntimeException(__('mail_inbox.oauth.token_exchange_failed'));
        }

        return (array) $response->json();
    }

    protected function verifyIdToken(string $idToken, string $expectedNonce, string $expectedEmail): void
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_id_token'));
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $header = json_decode($this->base64UrlDecode($headerB64), true);
        $claims = json_decode($this->base64UrlDecode($payloadB64), true);
        $signature = $this->base64UrlDecode($signatureB64);

        if (! is_array($header) || ! is_array($claims) || $signature === '') {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_id_token'));
        }

        if (($header['alg'] ?? '') !== 'RS256' || empty($header['kid'])) {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_id_token'));
        }

        $publicKey = $this->resolveSigningCertificate((string) $header['kid']);
        $verified = openssl_verify($headerB64.'.'.$payloadB64, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($verified !== 1) {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_id_token_signature'));
        }

        if (($claims['aud'] ?? '') !== $this->clientId()) {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_id_token'));
        }

        $now = time();
        if (! isset($claims['exp']) || (int) $claims['exp'] < ($now - 60)) {
            throw new RuntimeException(__('mail_inbox.oauth.expired_id_token'));
        }

        if (($claims['nonce'] ?? '') !== $expectedNonce) {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_id_token'));
        }

        $tid = (string) ($claims['tid'] ?? '');
        $expectedIssuer = $tid !== ''
            ? "https://login.microsoftonline.com/{$tid}/v2.0"
            : '';

        if ($expectedIssuer === '' || ($claims['iss'] ?? '') !== $expectedIssuer) {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_id_token'));
        }

        $expectedLower = strtolower(trim($expectedEmail));
        $candidateEmails = array_filter(array_map(
            static fn ($val): string => is_string($val) ? strtolower(trim($val)) : '',
            [
                $claims['email'] ?? null,
                $claims['preferred_username'] ?? null,
                $claims['upn'] ?? null,
            ]
        ));

        if ($candidateEmails !== [] && ! in_array($expectedLower, $candidateEmails, true)) {
            Log::warning('OAuth email mismatch debug', [
                'expected' => $expectedLower,
                'candidateEmails' => $candidateEmails,
                'email_claim' => $claims['email'] ?? null,
                'preferred_username' => $claims['preferred_username'] ?? null,
                'upn' => $claims['upn'] ?? null,
            ]);
            throw new RuntimeException(__('mail_inbox.oauth.email_mismatch', [
                'expected' => $expectedEmail,
            ]));
        }
    }

    protected function resolveSigningCertificate(string $kid): string
    {
        $keys = $this->fetchJwksKeys(false);
        $cert = $this->findCertForKid($keys, $kid);

        if ($cert === null) {
            $keys = $this->fetchJwksKeys(true);
            $cert = $this->findCertForKid($keys, $kid);
        }

        if ($cert === null) {
            throw new RuntimeException(__('mail_inbox.oauth.invalid_id_token_signature'));
        }

        return $cert;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchJwksKeys(bool $forceRefresh): array
    {
        $cacheKey = self::JWKS_CACHE_PREFIX.$this->tenant();

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $jwksUrl = sprintf(
            'https://login.microsoftonline.com/%s/discovery/v2.0/keys',
            rawurlencode($this->tenant())
        );

        $response = $this->httpClient()->timeout(15)->get($jwksUrl);
        if (! $response->successful()) {
            throw new RuntimeException(__('mail_inbox.oauth.jwks_fetch_failed'));
        }

        $keys = (array) ($response->json('keys') ?? []);
        Cache::put($cacheKey, $keys, now()->addHours(6));

        return $keys;
    }

    /**
     * @param  array<int, array<string, mixed>>  $keys
     */
    protected function findCertForKid(array $keys, string $kid): ?string
    {
        foreach ($keys as $key) {
            if (($key['kid'] ?? '') !== $kid) {
                continue;
            }

            $x5c = $key['x5c'][0] ?? null;
            if (is_string($x5c) && $x5c !== '') {
                return "-----BEGIN CERTIFICATE-----\n".chunk_split($x5c, 64, "\n")."-----END CERTIFICATE-----\n";
            }

            $n = $key['n'] ?? null;
            $e = $key['e'] ?? null;
            if (($key['kty'] ?? '') === 'RSA' && is_string($n) && is_string($e)) {
                return $this->jwkRsaToPem($n, $e);
            }
        }

        return null;
    }

    protected function jwkRsaToPem(string $nB64, string $eB64): ?string
    {
        $modulus = $this->base64UrlDecode($nB64);
        $exponent = $this->base64UrlDecode($eB64);
        if ($modulus === '' || $exponent === '') {
            return null;
        }

        if (ord($modulus[0]) > 0x7F) {
            $modulus = "\x00".$modulus;
        }
        if (ord($exponent[0]) > 0x7F) {
            $exponent = "\x00".$exponent;
        }

        $encodeLen = static function (int $len): string {
            if ($len < 128) {
                return chr($len);
            }
            $bytes = ltrim(pack('N', $len), "\x00");

            return chr(0x80 | strlen($bytes)).$bytes;
        };

        $modDer = "\x02".$encodeLen(strlen($modulus)).$modulus;
        $expDer = "\x02".$encodeLen(strlen($exponent)).$exponent;
        $seq = "\x30".$encodeLen(strlen($modDer.$expDer)).$modDer.$expDer;
        $bitString = "\x03".$encodeLen(strlen($seq) + 1)."\x00".$seq;
        $algoId = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $pubKeyDer = "\x30".$encodeLen(strlen($algoId.$bitString)).$algoId.$bitString;

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($pubKeyDer), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    protected function markReauthRequired(int $accountId, string $expectedRefreshToken, string $errorMessage): void
    {
        $latest = MailAccount::find($accountId);
        if (! $latest || $latest->oauth_refresh_token !== $expectedRefreshToken) {
            return;
        }

        $latest->update([
            'oauth_access_token' => null,
            'oauth_refresh_token' => null,
            'oauth_token_expires_at' => null,
            'status' => MailAccountStatus::REAUTH_REQUIRED,
            'error_message' => $errorMessage,
        ]);
    }

    protected function tokenEndpoint(): string
    {
        return sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', rawurlencode($this->tenant()));
    }

    protected function sanitizeReturnUrl(?string $returnUrl, MailAccount $account): string
    {
        $default = MailAccountResource::getUrl('edit', ['record' => $account]);

        if (! is_string($returnUrl) || $returnUrl === '') {
            return $default;
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $targetHost = parse_url($returnUrl, PHP_URL_HOST);

        if ($targetHost === null || ($appHost !== null && strcasecmp($targetHost, $appHost) === 0)) {
            return $returnUrl;
        }

        return $default;
    }

    protected function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder > 0) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/')) ?: '';
    }

    protected function httpClient(): \Illuminate\Http\Client\PendingRequest
    {
        return app()->isLocal()
            ? Http::withoutVerifying()
            : Http::withOptions([]);
    }
}
