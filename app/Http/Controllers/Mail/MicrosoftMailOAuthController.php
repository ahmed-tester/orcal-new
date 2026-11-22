<?php

namespace App\Http\Controllers\Mail;

use App\Http\Controllers\Controller;
use App\Models\Mail\MailAccount;
use App\Services\Mail\MicrosoftMailOAuthService;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MicrosoftMailOAuthController extends Controller
{
    public function redirect(
        MailAccount $account,
        Request $request,
        MicrosoftMailOAuthService $oauthService,
    ): RedirectResponse {
        Log::info('Microsoft Mail OAuth redirect requested', [
            'account_id' => $account->id,
            'user_id' => $request->user()?->id,
            'tenant' => $oauthService->tenant(),
            'client_id' => $oauthService->clientId(),
            'redirect_uri' => $oauthService->redirectUri(),
            'return_url' => $request->input('return_url'),
        ]);

        try {
            $result = $oauthService->beginAuthorization(
                $account,
                $request->user(),
                $request->input('return_url')
            );
        } catch (\Throwable $e) {
            Log::error('Microsoft Mail OAuth redirect failed', [
                'account_id' => $account->id,
                'user_id' => $request->user()?->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        Log::info('Microsoft Mail OAuth redirecting to authorize URL', [
            'account_id' => $account->id,
            'authorize_host' => parse_url($result['url'], PHP_URL_HOST),
            'authorize_path' => parse_url($result['url'], PHP_URL_PATH),
        ]);

        return redirect()->away($result['url']);
    }

    public function callback(
        Request $request,
        MicrosoftMailOAuthService $oauthService,
    ): RedirectResponse {
        Log::info('Microsoft Mail OAuth callback received', [
            'user_id' => $request->user()?->id,
            'has_code' => $request->filled('code'),
            'has_state' => $request->filled('state'),
            'error' => $request->query('error'),
            'error_description' => $request->query('error_description'),
        ]);

        $result = $oauthService->handleCallback($request, $request->user());

        Log::info('Microsoft Mail OAuth callback handled', [
            'success' => $result['success'],
            'message' => $result['message'],
            'redirect_url' => $result['redirect_url'],
        ]);

        Notification::make()
            ->title($result['message'])
            ->{$result['success'] ? 'success' : 'danger'}()
            ->send();

        return redirect()->to($result['redirect_url']);
    }
}
