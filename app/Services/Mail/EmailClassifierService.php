<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Models\Mail\MailCategory;
use App\Models\Mail\MailExclusionKeyword;
use App\Models\Mail\MailImportanceKeyword;
use App\Models\Mail\MailTrustedDomain;
use App\Models\Mail\MailTrustedEmail;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailClassifierService
{
    private const DEFAULT_MODEL = 'gemini-flash-lite-latest';

    private const DEPRECATED_MODELS = [
        'gemini-2.0-flash',
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite',
        'gemini-1.5-flash',
        'gemini-1.5-pro',
    ];

    /**
     * Classify an incoming email across the 3-level pipeline.
     *
     * @return array{
     *     is_important: bool,
     *     category: MailCategory,
     *     confidence: int,
     *     summary: string,
     *     priority: MailPriority,
     *     status: MailMessageStatus,
     *     classification_reason: string
     * }
     */
    public function classify(string $senderEmail, string $subject, string $body, ?int $companyId = null): array
    {
        $traceId = substr((string) Str::uuid(), 0, 8);
        $startedAt = microtime(true);

        Log::info('Mail classifier: started', [
            'trace_id' => $traceId,
            'company_id' => $companyId,
            'sender_domain' => $this->extractSenderDomain($senderEmail),
            'subject_length' => mb_strlen($subject),
            'body_length' => mb_strlen($body),
            'active_trusted_emails' => $this->scopeRuleQuery(MailTrustedEmail::query(), $companyId)->where('is_active', true)->count(),
            'active_trusted_domains' => $this->scopeRuleQuery(MailTrustedDomain::query(), $companyId)->where('is_active', true)->count(),
            'active_exclusion_keywords' => $this->scopeRuleQuery(MailExclusionKeyword::query(), $companyId)->where('is_active', true)->count(),
            'active_importance_keywords' => $this->scopeRuleQuery(MailImportanceKeyword::query(), $companyId)->where('is_active', true)->count(),
        ]);

        $categoryService = app(MailCategoryService::class);

        $whitelistMatch = $this->checkWhitelist($senderEmail, $companyId);
        if ($whitelistMatch !== null) {
            $categoryAvailable = $categoryService->isActive('official');
            $result = [
                'is_important' => $categoryAvailable,
                'category' => $categoryAvailable ? 'official' : MailCategory::SYSTEM_OTHER_SLUG,
                'confidence' => 100,
                'summary' => $this->generateFastSummary($subject, $body),
                'priority' => MailPriority::HIGH,
                'status' => $categoryAvailable ? MailMessageStatus::NEW : MailMessageStatus::UNDER_REVIEW,
                'classification_reason' => $whitelistMatch,
            ];
            $this->logClassificationResult($traceId, $startedAt, 'whitelist', $result);

            return $result;
        }

        $fullText = mb_strtolower($subject.' '.$body);

        $exclusionMatch = $this->checkExclusionKeywords($fullText, $companyId);
        if ($exclusionMatch !== null) {
            $categoryAvailable = $categoryService->isActive('marketing');
            $result = [
                'is_important' => false,
                'category' => $categoryAvailable ? 'marketing' : MailCategory::SYSTEM_OTHER_SLUG,
                'confidence' => 90,
                'summary' => $this->generateFastSummary($subject, $body),
                'priority' => MailPriority::LOW,
                'status' => $categoryAvailable ? MailMessageStatus::REJECTED : MailMessageStatus::UNDER_REVIEW,
                'classification_reason' => __('mail_inbox.classifier.reasons.exclusion_keyword', ['keyword' => $exclusionMatch]),
            ];
            $this->logClassificationResult($traceId, $startedAt, 'exclusion_keyword', $result, [
                'matched_keyword' => $exclusionMatch,
            ]);

            return $result;
        }

        $importanceMatch = $this->checkImportanceKeywords($fullText, $companyId);
        if ($importanceMatch !== null) {
            $detectedCategory = $this->detectCategoryFromKeyword($importanceMatch);
            $categoryAvailable = $categoryService->isActive($detectedCategory);
            $result = [
                'is_important' => $categoryAvailable,
                'category' => $categoryAvailable ? $detectedCategory : MailCategory::SYSTEM_OTHER_SLUG,
                'confidence' => 85,
                'summary' => $this->generateFastSummary($subject, $body),
                'priority' => MailPriority::HIGH,
                'status' => $categoryAvailable ? MailMessageStatus::NEW : MailMessageStatus::UNDER_REVIEW,
                'classification_reason' => __('mail_inbox.classifier.reasons.importance_keyword', ['keyword' => $importanceMatch]),
            ];
            $this->logClassificationResult($traceId, $startedAt, 'importance_keyword', $result, [
                'matched_keyword' => $importanceMatch,
            ]);

            return $result;
        }

        return $this->analyzeWithGemini($senderEmail, $subject, $body, $traceId, $startedAt);
    }

    /**
     * Redact sensitive credentials and identifiers before sending text to external AI.
     */
    public function redactSensitiveData(string $text): string
    {
        $text = preg_replace('/\b(?:\d[ -]*?){13,16}\b/', (string) __('mail_inbox.classifier.redaction.card'), $text) ?? $text;

        $terms = (string) __('mail_inbox.classifier.redaction.password_terms', [], 'ar');
        $text = preg_replace('/(?i)('.$terms.')\s*[:=]\s*[^\s]+/u', '$1: '.__('mail_inbox.classifier.redaction.secret'), $text) ?? $text;

        return preg_replace('/\b[12]\d{9}\b/', (string) __('mail_inbox.classifier.redaction.national_id'), $text) ?? $text;
    }

    protected function checkWhitelist(string $senderEmail, ?int $companyId): ?string
    {
        $senderEmail = strtolower(trim($senderEmail));

        $trustedEmail = $this->scopeRuleQuery(MailTrustedEmail::query(), $companyId)
            ->where('is_active', true)
            ->whereRaw('LOWER(email) = ?', [$senderEmail])
            ->first();

        if ($trustedEmail) {
            $org = $trustedEmail->organization_name ? " ({$trustedEmail->organization_name})" : '';

            return __('mail_inbox.classifier.reasons.trusted_email', ['org' => $org]);
        }

        $trustedDomains = $this->scopeRuleQuery(MailTrustedDomain::query(), $companyId)
            ->where('is_active', true)
            ->get();

        foreach ($trustedDomains as $td) {
            if (MailTrustedDomain::matches((string) $td->domain_pattern, $senderEmail)) {
                $org = $td->organization_name ? " ({$td->organization_name})" : '';

                return __('mail_inbox.classifier.reasons.trusted_domain', [
                    'pattern' => $td->domain_pattern,
                    'org' => $org,
                ]);
            }
        }

        return null;
    }

    protected function checkExclusionKeywords(string $text, ?int $companyId): ?string
    {
        $keywords = $this->scopeRuleQuery(MailExclusionKeyword::query(), $companyId)
            ->where('is_active', true)
            ->pluck('keyword');

        foreach ($keywords as $kw) {
            if ($kw !== '' && mb_strpos($text, mb_strtolower((string) $kw)) !== false) {
                return (string) $kw;
            }
        }

        return null;
    }

    protected function checkImportanceKeywords(string $text, ?int $companyId): ?string
    {
        $keywords = $this->scopeRuleQuery(MailImportanceKeyword::query(), $companyId)
            ->where('is_active', true)
            ->orderByDesc('weight')
            ->orderBy('id')
            ->get(['id', 'keyword', 'weight']);

        foreach ($keywords as $item) {
            $kw = (string) $item->keyword;
            if ($kw !== '' && mb_strpos($text, mb_strtolower($kw)) !== false) {
                return $kw;
            }
        }

        return null;
    }

    protected function scopeRuleQuery(Builder $query, ?int $companyId): Builder
    {
        if ($companyId === null) {
            return $query->whereNull('company_id');
        }

        return $query->where(function (Builder $q) use ($companyId) {
            $q->whereNull('company_id')->orWhere('company_id', $companyId);
        });
    }

    protected function analyzeWithGemini(
        string $senderEmail,
        string $subject,
        string $body,
        string $traceId = '',
        ?float $startedAt = null
    ): array {
        $startedAt ??= microtime(true);
        $apiKey = $this->resolveApiKey();
        $model = $this->resolveModel();

        if ($apiKey === '' || $apiKey === 'your_gemini_api_key_here') {
            Log::warning('Mail classifier: Gemini API key is not configured. Routing email to under_review.', [
                'trace_id' => $traceId,
            ]);

            $result = $this->reviewFallback($subject, $body, __('mail_inbox.classifier.reasons.gemini_missing_key'));
            $this->logClassificationResult($traceId, $startedAt, 'gemini_missing_key', $result, ['model' => $model]);

            return $result;
        }

        $categoryService = app(MailCategoryService::class);
        $cacheKey = 'mail_gemini_classification_v2_'.md5(
            $model.'|'.$categoryService->geminiVersion().'|'.$senderEmail.'_'.$subject.'_'.mb_substr($body, 0, 500)
        );
        if (Cache::has($cacheKey)) {
            $cachedResult = Cache::get($cacheKey);
            $cachedCat = is_array($cachedResult) ? (string) ($cachedResult['category'] ?? '') : '';
            if (is_array($cachedResult) && $cachedCat !== '' && $categoryService->isActive($cachedCat)) {
                $this->logClassificationResult($traceId, $startedAt, 'gemini_cache', $cachedResult, [
                    'model' => $model,
                    'cache_hit' => true,
                ]);

                return $cachedResult;
            }
        }

        $dailyLimit = (int) (Setting::get('mail_gemini_daily_limit') ?? 1000);
        $todayKey = 'mail_gemini_requests_'.now()->format('Y-m-d');
        $requestsToday = (int) Cache::get($todayKey, 0);

        if ($requestsToday >= $dailyLimit) {
            Log::warning('Mail classifier: Gemini daily request limit reached. Routing email to under_review.', [
                'trace_id' => $traceId,
                'requests_today' => $requestsToday,
                'daily_limit' => $dailyLimit,
            ]);

            $result = $this->reviewFallback($subject, $body, __('mail_inbox.classifier.reasons.gemini_daily_limit'));
            $this->logClassificationResult($traceId, $startedAt, 'gemini_daily_limit', $result, ['model' => $model]);

            return $result;
        }

        $cleanBody = $this->redactSensitiveData(mb_substr(strip_tags($body), 0, 3000));
        $cleanSubject = $this->redactSensitiveData(mb_substr($subject, 0, 300));

        $prompt = (string) __('mail_inbox.classifier.prompt', [
            'sender' => $senderEmail,
            'subject' => $cleanSubject,
            'body' => $cleanBody,
            'categories' => $categoryService->geminiPromptCategoriesText(),
        ], 'ar');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(20)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            $incremented = Cache::increment($todayKey);
            $count = is_int($incremented) ? $incremented : (((int) Cache::get($todayKey, 0)) + 1);
            Cache::put($todayKey, $count, now()->endOfDay());

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $parsed = json_decode((string) $content, true);

                if (is_array($parsed) && isset($parsed['confidence'])) {
                    $isImportant = (bool) ($parsed['is_important'] ?? false);
                    $confidence = (int) ($parsed['confidence'] ?? 0);
                    $rawCategory = trim((string) ($parsed['category'] ?? ''));
                    $category = $categoryService->resolveActiveSlugFromLabel($rawCategory);
                    $summary = (string) ($parsed['summary'] ?? $this->generateFastSummary($subject, $body));
                    $priority = MailPriority::fromLabel((string) ($parsed['suggested_priority'] ?? ''));

                    $isInvalidCategoryOutput = $rawCategory === ''
                        || ($category === MailCategory::SYSTEM_OTHER_SLUG
                            && ! in_array(mb_strtolower($rawCategory), ['other', 'أخرى'], true));

                    if ($isInvalidCategoryOutput) {
                        $status = MailMessageStatus::UNDER_REVIEW;
                        $isImportant = false;
                        $reason = __('mail_inbox.classifier.reasons.gemini_low_confidence', ['confidence' => $confidence]);
                    } elseif ($confidence >= 70 && $isImportant) {
                        $status = MailMessageStatus::NEW;
                        $reason = __('mail_inbox.classifier.reasons.gemini_important', ['confidence' => $confidence]);
                    } elseif ($confidence >= 70 && ! $isImportant) {
                        $status = MailMessageStatus::REJECTED;
                        $reason = __('mail_inbox.classifier.reasons.gemini_not_important', [
                            'confidence' => $confidence,
                            'category' => $categoryService->labelFor($category),
                        ]);
                    } else {
                        $status = MailMessageStatus::UNDER_REVIEW;
                        $reason = __('mail_inbox.classifier.reasons.gemini_low_confidence', ['confidence' => $confidence]);
                    }

                    $result = [
                        'is_important' => $isImportant,
                        'category' => $category,
                        'confidence' => $confidence,
                        'summary' => $summary,
                        'priority' => $priority,
                        'status' => $status,
                        'classification_reason' => $reason,
                    ];

                    Cache::put($cacheKey, $result, now()->addDays(7));
                    $this->logClassificationResult($traceId, $startedAt, 'gemini_api', $result, [
                        'model' => $model,
                        'http_status' => $response->status(),
                        'requests_today' => $count,
                        'daily_limit' => $dailyLimit,
                        'cache_hit' => false,
                    ]);

                    return $result;
                }
            }

            Log::error('Mail classifier: Gemini API response error or invalid format.', [
                'trace_id' => $traceId,
                'model' => $model,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::error('Mail classifier: Gemini API exception.', [
                'trace_id' => $traceId,
                'message' => $e->getMessage(),
                'model' => $model,
            ]);
        }

        $fallbackResult = $this->reviewFallback($subject, $body, __('mail_inbox.classifier.reasons.gemini_failed'));
        $this->logClassificationResult($traceId, $startedAt, 'gemini_fallback', $fallbackResult, [
            'model' => $model,
        ]);

        return $fallbackResult;
    }

    /**
     * Lightweight Gemini reachability check using the shared system API key.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(?string $model = null): array
    {
        $apiKey = $this->resolveApiKey();

        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => (string) __('mail_inbox.settings.test_missing_key'),
            ];
        }

        $model = trim($model ?? $this->resolveModel());
        if (! preg_match('/^[a-zA-Z0-9.\-]{1,100}$/', $model)) {
            return ['success' => false, 'message' => (string) __('mail_inbox.settings.test_failed', ['error' => __('validation.regex', ['attribute' => __('mail_inbox.settings.mail_gemini_model')])])];
        }
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(15)->post($url, [
                'contents' => [[
                    'parts' => [[
                        'text' => 'Reply with the single word OK.',
                    ]],
                ]],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => (string) __('mail_inbox.settings.test_success', ['model' => $model]),
                ];
            }

            return [
                'success' => false,
                'message' => (string) __('mail_inbox.settings.test_failed', ['error' => $response->status()]),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => (string) __('mail_inbox.settings.test_failed', ['error' => $e->getMessage()]),
            ];
        }
    }

    protected function resolveApiKey(): string
    {
        $key = Setting::getGeminiApiKey();
        if (! empty($key)) {
            return (string) $key;
        }

        return (string) config('services.gemini.api_key', '');
    }

    protected function resolveModel(): string
    {
        $model = (string) (Setting::get('mail_gemini_model')
            ?: Setting::get('gemini_model')
            ?: config('services.gemini.model', self::DEFAULT_MODEL));

        if ($model === '' || in_array($model, self::DEPRECATED_MODELS, true)) {
            return self::DEFAULT_MODEL;
        }

        return $model;
    }

    protected function reviewFallback(string $subject, string $body, string $reason): array
    {
        return [
            'is_important' => false,
            'category' => $this->activeOrOther('inquiry'),
            'confidence' => 50,
            'summary' => $this->generateFastSummary($subject, $body),
            'priority' => MailPriority::MEDIUM,
            'status' => MailMessageStatus::UNDER_REVIEW,
            'classification_reason' => $reason,
        ];
    }

    protected function generateFastSummary(string $subject, string $body): string
    {
        $clean = trim((string) preg_replace('/\s+/', ' ', strip_tags($body)));
        $snippet = mb_substr($clean, 0, 160);

        if ($subject !== '') {
            return __('mail_inbox.classifier.fast_summary.with_subject', [
                'subject' => $subject,
                'snippet' => $snippet,
            ]);
        }

        return __('mail_inbox.classifier.fast_summary.without_subject', ['snippet' => $snippet]);
    }

    protected function detectCategoryFromKeyword(string $keyword): string
    {
        $kw = mb_strtolower($keyword);
        $hints = (array) __('mail_inbox.classifier.keyword_hints', [], 'ar');

        foreach (['complaint', 'request', 'official'] as $key) {
            foreach ((array) ($hints[$key] ?? []) as $hint) {
                if (str_contains($kw, mb_strtolower((string) $hint))) {
                    return $key;
                }
            }
        }

        return 'inquiry';
    }

    protected function activeOrOther(string $preferredSlug): string
    {
        $service = app(MailCategoryService::class);

        return $service->isActive($preferredSlug)
            ? $preferredSlug
            : MailCategory::SYSTEM_OTHER_SLUG;
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $extra
     */
    protected function logClassificationResult(
        string $traceId,
        float $startedAt,
        string $matchedLevel,
        array $result,
        array $extra = []
    ): void {
        Log::info('Mail classifier: completed', array_merge([
            'trace_id' => $traceId,
            'matched_level' => $matchedLevel,
            'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'is_important' => $result['is_important'] ?? null,
            'category' => isset($result['category']) && $result['category'] instanceof MailCategory ? $result['category']->value : ($result['category'] ?? null),
            'confidence' => $result['confidence'] ?? null,
            'priority' => isset($result['priority']) && $result['priority'] instanceof MailPriority ? $result['priority']->value : ($result['priority'] ?? null),
            'status' => isset($result['status']) && $result['status'] instanceof MailMessageStatus ? $result['status']->value : ($result['status'] ?? null),
            'reason' => $result['classification_reason'] ?? null,
        ], $extra));
    }

    protected function extractSenderDomain(string $senderEmail): string
    {
        $email = strtolower(trim($senderEmail));
        $atPos = strrpos($email, '@');

        return $atPos === false ? '' : substr($email, $atPos + 1);
    }
}
