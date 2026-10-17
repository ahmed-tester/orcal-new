<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiProcessorService
{
    protected GeminiToolRegistry $registry;

    public function __construct(?GeminiToolRegistry $registry = null)
    {
        $this->registry = $registry ?? new GeminiToolRegistry();
    }

    /**
     * معالجة ملف واستخراج البيانات عبر Gemini Tool Calling
     *
     * @param string $filePath المسار النسبي للملف في الـ Storage
     * @param string $disk الديسك المستخدم (مثل 'public' أو 'local')
     * @param array|null $allowedTools الأدوات المسموح لـ Gemini باستخدامها
     * @return array
     */
    public function processFile(string $filePath, string $disk = 'public', ?array $allowedTools = null): array
    {
        $apiKey = Setting::getGeminiApiKey();

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => __('smart_import.service.no_api_key'),
            ];
        }

        $fullPath = Storage::disk($disk)->path($filePath);
        if (!file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => __('smart_import.service.file_not_found'),
            ];
        }

        $mimeType = mime_content_type($fullPath) ?: 'text/plain';

        // إعداد أجزاء المحتوى (Parts)
        $parts = [];
        $instructionPrompt = __('smart_import.service.instruction_prompt');

        $parts[] = ['text' => $instructionPrompt];

        // إذا كان ملفاً نصياً أو CSV، نرسل النص مباشرة
        if (str_starts_with($mimeType, 'text/') || str_contains($mimeType, 'csv') || str_contains($mimeType, 'json')) {
            $textContent = file_get_contents($fullPath);
            $parts[] = ['text' => __('smart_import.service.file_content_prefix') . $textContent];
        } else {
            // إذا كان PDF أو صورة، نرسلها كـ inline_data بصيغة Base64
            $fileData = base64_encode(file_get_contents($fullPath));
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $fileData,
                ]
            ];
        }

        $toolsPayload = $this->registry->getDeclarationsForGemini($allowedTools);
        $primaryModel = Setting::getGeminiModel();
        $modelsToTry = array_unique(array_filter([$primaryModel, 'gemini-3.5-flash', 'gemini-3.6-flash', 'gemini-3.7-flash', 'gemini-3.8-flash', 'gemini-3.5-flash-lite']));

        $response = null;
        $lastError = 'فشل غير معروف';

        foreach ($modelsToTry as $currentModel) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$apiKey}";
            try {
                $response = Http::timeout(60)->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => $parts
                        ]
                    ],
                    'tools' => $toolsPayload,
                ]);

                if ($response->successful()) {
                    break;
                }

                $lastError = $response->json('error.message') ?? "HTTP " . $response->status();
                Log::warning("Gemini model {$currentModel} failed ({$lastError}), trying next fallback model...");
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("Gemini model {$currentModel} exception ({$lastError}), trying next fallback model...");
            }
        }

        if (!$response || $response->failed()) {
            Log::error('Gemini API Error after fallbacks: ' . $lastError);
            return [
                'success' => false,
                'message' => __('smart_import.service.connection_failed', ['error' => $lastError]),
            ];
        }

        $responseData = $response->json();
        $candidates = $responseData['candidates'][0]['content']['parts'] ?? [];

        $executedResults = [];
        $successCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;

        // تتبع واستدعاء كل الميثودز التي طلبها Gemini
        foreach ($candidates as $part) {
            if (isset($part['functionCall'])) {
                $funcName = $part['functionCall']['name'];
                $arguments = $part['functionCall']['args'] ?? [];

                // تنفيذ الميثود المحلية الآمنة
                $executionResult = $this->registry->executeTool($funcName, $arguments);
                $resultMsg = $executionResult['message'] ?? '';
                $isSuccess = !empty($executionResult['success']);
                $isDuplicate = str_contains($resultMsg, 'مكرر') || str_contains($resultMsg, 'مسجل مسبقاً') || str_contains($resultMsg, 'مسجلة مسبقاً') || str_contains($resultMsg, 'Duplicate') || str_contains($resultMsg, 'Already');

                if ($isSuccess) {
                    $successCount++;
                    $status = 'success';
                } elseif ($isDuplicate) {
                    $duplicateCount++;
                    $status = 'duplicate';
                } else {
                    $errorCount++;
                    $status = 'error';
                }

                // تجهيز عنوان مختصر وتفاصيل العرض
                $itemTitle = $arguments['name'] ?? $arguments['title'] ?? $arguments['beneficiary_name'] ?? $arguments['debt_identifier'] ?? $arguments['sub_item'] ?? $arguments['fund_name'] ?? $arguments['subject'] ?? $arguments['employee_identifier'] ?? $arguments['member_identifier'] ?? $arguments['device_number'] ?? $arguments['customer_name'] ?? $arguments['invoice_number'] ?? $arguments['description'] ?? __('smart_import.service.unnamed_record');
                $itemCategory = __('smart_import.categories.' . $funcName, [], null) ?: __('smart_import.categories.default');

                $itemIcon = match($funcName) {
                    'create_saudi_employee' => '🇸🇦',
                    'create_foreign_employee' => '🌍',
                    'create_customer_record' => '👥',
                    'create_company_record' => '🏢',
                    'create_treasury_transaction' => '💰',
                    'create_invoice_record' => '🧾',
                    'create_invoice_type' => '🧾',
                    'create_service_record' => '📦',
                    'create_installment_record' => '💳',
                    'create_installment_type' => '💳',
                    'create_payroll_record' => '📑',
                    'disburse_payroll_salary' => '💵',
                    'create_attendance_record' => '⏱️',
                    'create_attendance_device' => '📟',
                    'create_leave_record' => '🏖️',
                    'create_leave_type' => '🏖️',
                    'create_violation_record' => '⚠️',
                    'create_violation_transaction' => '💵',
                    'create_violation_category' => '📁',
                    'create_violation_type' => '🏷️',
                    'create_violation_regulation' => '📖',
                    'create_employee_reward' => '🎁',
                    'create_daily_journal' => '📊',
                    'create_task_record' => '📌',
                    'create_supervision_shift' => '👮‍♂️',
                    'create_payment_collection_item' => '🏷️',
                    'create_pos_device' => '💳',
                    'create_branch_record' => '📍',
                    'create_department_record' => '🗂️',
                    'create_job_title_record' => '💼',
                    'create_loan_fund' => '🏛️',
                    'create_loan_fund_member' => '🏦',
                    'create_loan_fund_loan' => '📋',
                    'create_loan_fund_payment' => '💸',
                    'create_rosca_fund' => '🤝',
                    'create_rosca_fund_member' => '🤝',
                    'create_rosca_payment' => '🪙',
                    'create_offer_record' => '📄',
                    'create_owner_record' => '👑',
                    'create_user_record' => '👤',
                    'create_ticket_record' => '🎫',
                    'create_faq_record' => '❓',
                    'create_weekly_note_record' => '📝',
                    'create_debt_record' => '💳',
                    'create_debt_collection' => '💵',
                    default => '📄'
                };

                $executedResults[] = [
                    'tool' => $funcName,
                    'category' => $itemCategory,
                    'icon' => $itemIcon,
                    'title' => $itemTitle,
                    'status' => $status,
                    'arguments' => $arguments,
                    'result' => $executionResult,
                ];
            }
        }

        if (empty($executedResults)) {
            return [
                'success' => false,
                'message' => __('smart_import.service.no_records_found'),
                'details' => $candidates,
            ];
        }

        $totalCount = count($executedResults);
        $failedCount = $duplicateCount + $errorCount;

        if ($successCount > 0 && $failedCount === 0) {
            $summaryMessage = __('smart_import.service.all_success', ['count' => $successCount]);
        } elseif ($successCount > 0 && $duplicateCount > 0 && $errorCount === 0) {
            $summaryMessage = __('smart_import.service.partial_duplicates', ['success' => $successCount, 'duplicate' => $duplicateCount]);
        } elseif ($successCount > 0) {
            $summaryMessage = __('smart_import.service.mixed_results', ['success' => $successCount, 'duplicate' => $duplicateCount, 'error' => $errorCount]);
        } elseif ($duplicateCount > 0 && $errorCount === 0) {
            $summaryMessage = __('smart_import.service.all_duplicates', ['count' => $duplicateCount]);
        } else {
            $summaryMessage = __('smart_import.service.validation_error');
        }

        return [
            'success' => $successCount > 0 || $duplicateCount > 0,
            'total_processed' => $totalCount,
            'success_count' => $successCount,
            'duplicate_count' => $duplicateCount,
            'error_count' => $errorCount,
            'failed_count' => $failedCount,
            'results' => $executedResults,
            'message' => $summaryMessage,
        ];
    }
}
