<?php

namespace App\Services\AI;

use App\Models\Setting;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeminiProcessorService
{
    protected GeminiToolRegistry $registry;

    public function __construct(?GeminiToolRegistry $registry = null)
    {
        $this->registry = $registry ?? new GeminiToolRegistry();
    }

    /**
     * Process file, extract data using Gemini AI and immediately execute local tool inserts.
     *
     * @param string $filePath Storage relative path to the uploaded file
     * @param string $disk Disk name ('public' or 'local')
     * @param array|null $allowedTools Specific tools allowed for Gemini
     * @return array
     */
    public function processFile(string $filePath, string $disk = 'public', ?array $allowedTools = null, array $extraContext = []): array
    {
        $analysis = $this->analyzeFile($filePath, $disk, $allowedTools);

        if (empty($analysis['success'])) {
            return $analysis;
        }

        return $this->executeExtractedRecords($analysis['records'], $extraContext);
    }

    /**
     * Analyze file using Gemini AI to detect matching tables and extract records without saving to database.
     *
     * @param string $filePath
     * @param string $disk
     * @param array|null $allowedTools
     * @return array
     */
    public function analyzeFile(string $filePath, string $disk = 'public', ?array $allowedTools = null): array
    {
        // Extend PHP execution time limit for AI analysis (multiple model fallbacks)
        set_time_limit(300);
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

        // Prepare request parts
        $parts = [];
        $instructionPrompt = __('smart_import.service.instruction_prompt');
        $companyNames = Company::pluck('trade_name')->filter()->values()->toArray();
        if (!empty($companyNames)) {
            $instructionPrompt .= "\n\n" . __('smart_import.service.company_context_instruction', [
                'companies' => implode(' | ', $companyNames)
            ]);
        }

        $parts[] = ['text' => $instructionPrompt];

        // Send text content directly for text, csv, and json files
        if (str_starts_with($mimeType, 'text/') || str_contains($mimeType, 'csv') || str_contains($mimeType, 'json')) {
            $textContent = file_get_contents($fullPath);
            $parts[] = ['text' => __('smart_import.service.file_content_prefix') . $textContent];
        } else {
            // Send binary files (PDF/images) as Base64 inline data
            $fileData = base64_encode(file_get_contents($fullPath));
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $fileData,
                ]
            ];
        }

        $toolsPayload = $this->registry->getDeclarationsForGemini($allowedTools);
        $primaryModel = Setting::getGeminiModel() ?: 'gemini-3.5-flash';
        $modelsToTry = array_unique(array_filter([
            $primaryModel,
            'gemini-3.5-flash',
            'gemini-3.5-flash-lite',
            'gemini-3.0-flash',
            'gemini-3.0-pro',
        ]));

        $response = null;
        $lastError = __('smart_import.service.unknown_failure');

        foreach ($modelsToTry as $currentModel) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$apiKey}";
            
            // Allow up to 3 attempts for momentary spikes (high demand is temporary according to Google)
            $maxAttempts = ($currentModel === $primaryModel) ? 3 : 2;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $response = Http::timeout(180)->connectTimeout(20)->post($endpoint, [
                        'contents' => [
                            [
                                'parts' => $parts
                            ]
                        ],
                        'tools' => $toolsPayload,
                        'generationConfig' => [
                            'maxOutputTokens' => 65536,
                            'temperature' => 0.1,
                        ],
                    ]);

                    if ($response->successful()) {
                        break 2; // Success! Break out of attempts and models loop
                    }

                    $lastError = $response->json('error.message') ?? "HTTP " . $response->status();

                    // If temporary spike in demand, sleep and retry
                    if ($attempt < $maxAttempts && (str_contains(strtolower($lastError), 'demand') || $response->status() === 503 || $response->status() === 429)) {
                        $waitTime = $attempt * 2; // 2s then 4s
                        Log::info("Gemini model {$currentModel} busy (spike in demand), waiting {$waitTime}s before retry #{$attempt}...");
                        sleep($waitTime);
                        continue;
                    }

                    Log::warning("Gemini model {$currentModel} failed ({$lastError}), trying next fallback model...");
                    break;
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    Log::warning("Gemini model {$currentModel} exception ({$lastError}), trying next fallback model...");
                    break;
                }
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
        $finishReason = $responseData['candidates'][0]['finishReason'] ?? 'UNKNOWN';
        $candidates = $responseData['candidates'][0]['content']['parts'] ?? [];

        $detectedRecords = [];
        $toolsSummary = [];

        foreach ($candidates as $part) {
            if (isset($part['functionCall'])) {
                $funcName = $part['functionCall']['name'];
                $arguments = $part['functionCall']['args'] ?? [];

                $itemTitle = $arguments['name'] ?? $arguments['title'] ?? $arguments['beneficiary_name'] ?? $arguments['debt_identifier'] ?? $arguments['sub_item'] ?? $arguments['fund_name'] ?? $arguments['subject'] ?? $arguments['employee_identifier'] ?? $arguments['member_identifier'] ?? $arguments['device_number'] ?? $arguments['customer_name'] ?? $arguments['invoice_number'] ?? $arguments['description'] ?? __('smart_import.service.unnamed_record');
                $itemCategory = __('smart_import.categories.' . $funcName, [], null) ?: __('smart_import.categories.default');
                $itemIcon = self::getToolIcon($funcName);

                $detectedRecords[] = [
                    'tool' => $funcName,
                    'category' => $itemCategory,
                    'icon' => $itemIcon,
                    'title' => $itemTitle,
                    'arguments' => $arguments,
                ];

                if (!isset($toolsSummary[$funcName])) {
                    $targetLabel = __('smart_import.targets.' . $funcName, [], null) ?: $itemCategory;
                    $toolsSummary[$funcName] = [
                        'tool' => $funcName,
                        'name' => $itemCategory,
                        'target_label' => $targetLabel,
                        'icon' => $itemIcon,
                        'count' => 0,
                    ];
                }
                $toolsSummary[$funcName]['count']++;
            }
        }

        Log::info("Gemini analysis completed. Finish reason: {$finishReason}, extracted records count: " . count($detectedRecords));

        if (empty($detectedRecords)) {
            $reasonText = '';
            foreach ($candidates as $part) {
                if (!empty($part['text'])) {
                    $reasonText .= trim($part['text']) . ' ';
                }
            }
            $reasonText = trim($reasonText);

            if (!empty($reasonText)) {
                Log::info('Gemini explained why no records were detected: ' . $reasonText);
            }

            return [
                'success' => false,
                'message' => !empty($reasonText) 
                    ? __('smart_import.service.no_records_found') . ' — (' . Str::limit($reasonText, 250) . ')'
                    : __('smart_import.service.no_records_found'),
                'details' => $candidates,
            ];
        }

        return [
            'success' => true,
            'file_path' => $filePath,
            'disk' => $disk,
            'total_count' => count($detectedRecords),
            'records' => $detectedRecords,
            'tools_summary' => array_values($toolsSummary),
            'first_tool' => array_key_first($toolsSummary),
        ];
    }

    /**
     * Execute extracted records locally and save into database.
     *
     * @param array $records
     * @return array
     */
    public function executeExtractedRecords(array $records, array $extraContext = []): array
    {
        $executedResults = [];
        $successCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;

        $duplicateKeywords = __('smart_import.service.duplicate_keywords');
        if (!is_array($duplicateKeywords)) {
            $duplicateKeywords = ['Duplicate', 'Already'];
        }

        foreach ($records as $item) {
            $funcName = $item['tool'];
            $arguments = $item['arguments'] ?? [];
            if (!empty($extraContext)) {
                $arguments = array_merge($extraContext, $arguments);
            }

            // Execute local tool method
            $executionResult = $this->registry->executeTool($funcName, $arguments);
            $resultMsg = $executionResult['message'] ?? '';
            $isSuccess = !empty($executionResult['success']);

            $isDuplicate = false;
            foreach ($duplicateKeywords as $keyword) {
                if (str_contains($resultMsg, $keyword)) {
                    $isDuplicate = true;
                    break;
                }
            }

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

            $itemCategory = $item['category'] ?? (__('smart_import.categories.' . $funcName, [], null) ?: __('smart_import.categories.default'));
            $itemIcon = $item['icon'] ?? self::getToolIcon($funcName);
            $itemTitle = $item['title'] ?? ($arguments['name'] ?? __('smart_import.service.unnamed_record'));

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

        if (empty($executedResults)) {
            return [
                'success' => false,
                'message' => __('smart_import.service.no_records_found'),
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

    /**
     * Get icon for destination tool.
     */
    public static function getToolIcon(string $funcName): string
    {
        return match($funcName) {
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
    }
}

