<?php

namespace App\Services\AI;

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

class GeminiProcessorService
{
    /**
     * Number of PDF pages sent to Gemini in a single request.
     * 2 pages per batch gives the perfect balance: ~35 records per request,
     * low image payload size (~750KB), 5-7s execution, and eliminates premature
     * AI stops on dense PDF statements.
     */
    public const PDF_PAGES_PER_BATCH = 2;

    /**
     * Models that encountered a hard quota error (e.g. limit: 0) during this process lifecycle,
     * so subsequent batches avoid making unnecessary failing HTTP requests to them.
     */
    protected static array $exhaustedModels = [];

    protected GeminiToolRegistry $registry;

    public function __construct(?GeminiToolRegistry $registry = null)
    {
        $this->registry = $registry ?? new GeminiToolRegistry;
    }

    /**
     * Process file, extract data using Gemini AI and immediately execute local tool inserts.
     *
     * @param  string  $filePath  Storage relative path to the uploaded file
     * @param  string  $disk  Disk name ('public' or 'local')
     * @param  array|null  $allowedTools  Specific tools allowed for Gemini
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
     */
    public function analyzeFile(string $filePath, string $disk = 'public', ?array $allowedTools = null): array
    {
        // Batches may require multiple sequential Gemini calls for large files.
        set_time_limit(1800);
        $apiKey = Setting::getGeminiApiKey();

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => __('smart_import.service.no_api_key'),
            ];
        }

        $fullPath = Storage::disk($disk)->path($filePath);
        if (! file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => __('smart_import.service.file_not_found'),
            ];
        }

        $mimeType = mime_content_type($fullPath) ?: 'text/plain';

        $instructionPrompt = $this->buildInstructionPrompt();
        $toolsPayload = $this->registry->getDeclarationsForGemini($allowedTools);

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        // Spreadsheet/CSV/text content is analyzed in batches: Gemini stops emitting
        // tool calls after a few dozen in a single response, which silently dropped
        // records on large files. PDFs are analyzed in small page batches for the
        // same reason, and so each request stays well below server timeouts.
        $tabularText = $this->extractTabularText($fullPath, $extension);
        if ($tabularText === null && (str_starts_with($mimeType, 'text/') || str_contains($mimeType, 'csv') || str_contains($mimeType, 'json'))) {
            $tabularText = file_get_contents($fullPath);
        }

        $chunks = [];
        if ($tabularText !== null) {
            foreach ($this->chunkTextForBatches($tabularText) as $textBatch) {
                $chunks[] = ['type' => 'text', 'content' => $textBatch];
            }
        } elseif ($extension === 'pdf' || str_contains($mimeType, 'pdf')) {
            $pageCount = $this->getPdfPageCount($fullPath);

            if ($pageCount !== null && $pageCount > 1) {
                for ($from = 1; $from <= $pageCount; $from += self::PDF_PAGES_PER_BATCH) {
                    $to = min($from + self::PDF_PAGES_PER_BATCH - 1, $pageCount);

                    $chunks[] = [
                        'type' => 'pdf_pages',
                        'from' => $from,
                        'to' => $to,
                        'total_pages' => $pageCount,
                        'page_count' => $to - $from + 1,
                    ];
                }
            } else {
                $chunks[] = [
                    'type' => 'binary',
                    'data' => base64_encode(file_get_contents($fullPath)),
                    'mime_type' => 'application/pdf',
                ];
            }
        } else {
            $chunks[] = [
                'type' => 'binary',
                'data' => base64_encode(file_get_contents($fullPath)),
                'mime_type' => $mimeType,
            ];
        }

        $hadTruncation = false;
        $detectedRecords = [];
        $toolsSummary = [];
        $failedPages = 0;
        $pageErrors = [];
        $lastCandidates = [];

        foreach ($chunks as $chunk) {
            if ($chunk['type'] === 'pdf_pages') {
                $batch = $this->analyzePdfPages($filePath, $disk, $allowedTools, $chunk['from'], $chunk['to']);

                if (empty($batch['success'])) {
                    // Keep whatever was extracted from the previous batches instead
                    // of discarding the whole run because one page failed.
                    $failedPages += $chunk['page_count'];
                    $pageErrors[] = $batch['message'] ?? __('smart_import.service.unknown_failure');
                    Log::warning('[SmartImport] PDF batch failed, continuing with the next one: '.end($pageErrors));

                    continue;
                }

                $hadTruncation = $hadTruncation || ! empty($batch['truncated']);

                foreach ($batch['records'] as $record) {
                    $detectedRecords[] = $record;
                    $this->registerToolSummary($toolsSummary, $record);
                }

                continue;
            }

            $parts = [['text' => $instructionPrompt]];
            $expectedRows = 0;

            if ($chunk['type'] === 'text') {
                $lines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim($chunk['content'])), fn ($l) => trim($l) !== ''));
                $expectedRows = max(1, count($lines) - 1); // exclude header row

                $batchNotice = __('smart_import.service.text_batch_notice', ['count' => $expectedRows])."\n\n";
                $nonce = "\n\n<!-- batch_ref: ".uniqid().'_'.time().' -->';
                $parts[] = ['text' => $batchNotice.__('smart_import.service.file_content_prefix').$chunk['content'].$nonce];
            } else {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $chunk['mime_type'] ?? $mimeType,
                        'data' => $chunk['data'],
                    ],
                ];
            }

            $call = $this->callGemini($apiKey, $parts, $toolsPayload);
            if (empty($call['success'])) {
                // Keep whatever was extracted from the previous batches instead of
                // discarding the whole run because a single page failed.
                $failedPages += $chunk['page_count'] ?? 1;
                $pageErrors[] = $call['message'] ?? __('smart_import.service.unknown_failure');
                Log::warning('[SmartImport] Batch failed, continuing with the next one: '.end($pageErrors));

                continue;
            }

            // Retry if Gemini prematurely stopped (e.g. emitted only 1 or 2 calls for a batch of 15+ rows)
            $functionCallCount = count(array_filter($call['candidates'] ?? [], fn ($p) => isset($p['functionCall'])));
            if ($chunk['type'] === 'text' && $expectedRows >= 10 && $functionCallCount <= 2) {
                SmartImportLogger::warning('gemini.premature_stop_retry', [
                    'expected' => $expectedRows,
                    'got' => $functionCallCount,
                ]);

                $retryParts = $parts;
                $retryParts[] = [
                    'text' => "\n\n⚠️ URGENT INSTRUCTION: Your previous response stopped prematurely after emitting only {$functionCallCount} calls for {$expectedRows} table rows. You MUST extract every single row and call the appropriate tool for all {$expectedRows} records without stopping! Nonce: ".uniqid().'_'.time(),
                ];

                $retryCall = $this->callGemini($apiKey, $retryParts, $toolsPayload);
                if (! empty($retryCall['success'])) {
                    $retryCallCount = count(array_filter($retryCall['candidates'] ?? [], fn ($p) => isset($p['functionCall'])));
                    if ($retryCallCount > $functionCallCount) {
                        $call = $retryCall;
                    }
                }
            }

            $hadTruncation = $hadTruncation || $call['finish_reason'] === 'MAX_TOKENS';
            $lastCandidates = $call['candidates'];

            foreach ($lastCandidates as $part) {
                if (! isset($part['functionCall'])) {
                    continue;
                }

                $record = $this->buildExtractedRecord($part);
                $detectedRecords[] = $record;
                $this->registerToolSummary($toolsSummary, $record);
            }
        }

        Log::info('Gemini batched analysis completed: '.count($detectedRecords).' records across '.count($chunks).' batch(es), failed pages: '.$failedPages.', truncation: '.($hadTruncation ? 'yes' : 'no'));

        if (empty($detectedRecords)) {
            if (! empty($pageErrors)) {
                return [
                    'success' => false,
                    'message' => $pageErrors[0],
                ];
            }

            $reasonText = '';
            foreach ($lastCandidates as $part) {
                if (! empty($part['text'])) {
                    $reasonText .= trim($part['text']).' ';
                }
            }
            $reasonText = trim($reasonText);

            if (! empty($reasonText)) {
                Log::info('Gemini explained why no records were detected: '.$reasonText);
            }

            return [
                'success' => false,
                'message' => ! empty($reasonText)
                    ? __('smart_import.service.no_records_found').' — ('.Str::limit($reasonText, 250).')'
                    : __('smart_import.service.no_records_found'),
                'details' => $lastCandidates ?? [],
            ];
        }

        $totalPages = array_sum(array_map(fn (array $chunk): int => $chunk['page_count'] ?? 1, $chunks));

        return $this->buildAnalysisResult($filePath, $disk, $detectedRecords, $failedPages, $totalPages, $hadTruncation, $pageErrors);
    }

    /**
     * Build the analysis payload used by the preview screen from accumulated records.
     */
    public function buildAnalysisResult(
        string $filePath,
        string $disk,
        array $records,
        int $failedPages = 0,
        int $totalPages = 0,
        bool $truncated = false,
        array $errors = [],
    ): array {
        if (empty($records)) {
            return [
                'success' => false,
                'message' => $errors[0] ?? __('smart_import.service.no_records_found'),
            ];
        }

        $toolsSummary = [];
        foreach ($records as $record) {
            $this->registerToolSummary($toolsSummary, $record);
        }

        $warnings = [];
        if ($failedPages > 0) {
            $warnings[] = __('smart_import.service.pdf_page_failed', [
                'failed' => $failedPages,
                'total' => $totalPages,
            ]);
        }
        if ($truncated) {
            $warnings[] = __('smart_import.service.truncated_warning');
        }

        return [
            'success' => true,
            'file_path' => $filePath,
            'disk' => $disk,
            'total_count' => count($records),
            'records' => $records,
            'tools_summary' => array_values($toolsSummary),
            'first_tool' => array_key_first($toolsSummary),
            'warning' => empty($warnings) ? null : implode(' ', $warnings),
        ];
    }

    /**
     * Send one Gemini request carrying tool declarations, with model fallbacks and retry logic.
     *
     * @param  array<string, mixed>  $logContext  Extra data (run id, page range...) written to the test channel
     * @return array{success: bool, message?: string, finish_reason: string, candidates: array}
     */
    protected function callGemini(string $apiKey, array $parts, array $toolsPayload, array $logContext = []): array
    {
        $primaryModel = Setting::getGeminiModel() ?: 'gemini-3.5-flash';
        $allModels = [
            $primaryModel,
            'gemini-3.5-flash-lite',
            'gemini-3.5-flash',
            'gemini-3.6-flash',
            'gemini-3.7-flash',
            'gemini-3.8-flash',
            'gemini-4-argon',
        ];

        $modelsToTry = array_values(array_unique(array_filter(
            $allModels,
            fn ($m) => ! empty($m) && ! isset(self::$exhaustedModels[$m])
        )));

        if (empty($modelsToTry)) {
            $modelsToTry = array_values(array_unique(array_filter([$primaryModel, 'gemini-3.5-flash-lite', 'gemini-3.5-flash'])));
        }

        $response = null;
        $lastError = __('smart_import.service.unknown_failure');

        $declarationCount = count($toolsPayload[0]['function_declarations'] ?? []);
        $inlinePages = count(array_filter($parts, fn (array $part): bool => isset($part['inline_data'])));

        foreach ($modelsToTry as $currentModel) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$apiKey}";

            // Lite models do not support thinkingConfig or very large output token budgets
            $isLite = str_contains($currentModel, 'lite');
            $generationConfig = [
                'temperature' => 0.1,
            ];
            if (! $isLite) {
                $generationConfig['maxOutputTokens'] = 65536;
                $generationConfig['thinkingConfig'] = ['thinkingBudget' => 0];
            }

            // Allow up to 3 attempts for momentary spikes (high demand is temporary according to Google)
            $maxAttempts = ($currentModel === $primaryModel) ? 3 : 2;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $startedAt = microtime(true);

                SmartImportLogger::info('gemini.request', $logContext + [
                    'model' => $currentModel,
                    'attempt' => $attempt,
                    'tools' => $declarationCount,
                    'inline_docs' => $inlinePages,
                    'payload_kb' => round(strlen((string) json_encode($parts)) / 1024, 1),
                    'thinking' => isset($generationConfig['thinkingConfig']),
                ]);

                try {
                    $requestBody = [
                        'contents' => [
                            [
                                'parts' => $parts,
                            ],
                        ],
                        'tools' => $toolsPayload,
                        'generationConfig' => $generationConfig,
                    ];

                    $response = Http::timeout(180)->connectTimeout(20)->post($endpoint, $requestBody);

                    // If model rejects tool_config or generationConfig (thinkingConfig/maxOutputTokens), retry with bare config
                    if ($response->status() === 400) {
                        SmartImportLogger::warning('gemini.safe_config_fallback', $logContext + ['model' => $currentModel]);

                        $generationConfig = ['temperature' => 0.1];

                        $response = Http::timeout(180)->connectTimeout(20)->post($endpoint, [
                            'contents' => [
                                [
                                    'parts' => $parts,
                                ],
                            ],
                            'tools' => $toolsPayload,
                            'generationConfig' => $generationConfig,
                        ]);
                    }

                    $this->logGeminiResponse($response, $currentModel, round(microtime(true) - $startedAt, 2), $logContext);

                    if ($response->successful()) {
                        break 2; // Success! Break out of attempts and models loop
                    }

                    $lastError = $response->json('error.message') ?? 'HTTP '.$response->status();

                    // If hard daily quota is exceeded, do not sleep & retry the same model
                    $isHardQuota = str_contains(strtolower($lastError), 'quota exceeded') || str_contains(strtolower($lastError), 'exceeded your current quota');

                    if ($isHardQuota) {
                        self::$exhaustedModels[$currentModel] = true;
                        SmartImportLogger::warning('gemini.quota_exceeded', $logContext + ['model' => $currentModel, 'error' => $lastError]);
                        break; // Skip to next model immediately
                    }

                    // If temporary spike in demand, sleep and retry
                    if ($attempt < $maxAttempts && (str_contains(strtolower($lastError), 'demand') || $response->status() === 503 || $response->status() === 429)) {
                        $waitTime = $attempt * 2; // 2s then 4s
                        SmartImportLogger::warning('gemini.retry_after_demand', $logContext + ['model' => $currentModel, 'wait' => $waitTime, 'attempt' => $attempt]);

                        sleep($waitTime);

                        continue;
                    }

                    SmartImportLogger::warning('gemini.model_failed', $logContext + ['model' => $currentModel, 'error' => $lastError]);
                    break;
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();

                    SmartImportLogger::error('gemini.exception', $logContext + [
                        'model' => $currentModel,
                        'seconds' => round(microtime(true) - $startedAt, 2),
                        'error' => $lastError,
                    ]);
                    break;
                }
            }
        }

        if (! $response || $response->failed()) {
            Log::error('Gemini API Error after fallbacks: '.$lastError);

            SmartImportLogger::error('gemini.failed', $logContext + ['error' => $lastError]);

            return [
                'success' => false,
                'message' => __('smart_import.service.connection_failed', ['error' => $lastError]),
                'finish_reason' => 'ERROR',
                'candidates' => [],
            ];
        }

        $responseData = $response->json();

        return [
            'success' => true,
            'finish_reason' => $responseData['candidates'][0]['finishReason'] ?? 'UNKNOWN',
            'candidates' => $responseData['candidates'][0]['content']['parts'] ?? [],
        ];
    }

    /**
     * Write what Gemini actually answered: how many tool calls it emitted and
     * how much of the output budget it used.
     */
    protected function logGeminiResponse($response, string $model, float $seconds, array $logContext): void
    {
        $data = $response->json() ?? [];
        $candidate = $data['candidates'][0] ?? [];
        $responseParts = $candidate['content']['parts'] ?? [];

        $calls = 0;
        $text = '';

        foreach ($responseParts as $part) {
            if (isset($part['functionCall'])) {
                $calls++;
            }

            if (! empty($part['text'])) {
                $text .= trim($part['text']).' ';
            }
        }

        SmartImportLogger::info('gemini.response', $logContext + [
            'model' => $model,
            'status' => $response->status(),
            'seconds' => $seconds,
            'finish' => $candidate['finishReason'] ?? null,
            'calls' => $calls,
            'text' => $text !== '' ? Str::limit(trim($text), 300) : null,
            'usage' => $data['usageMetadata'] ?? null,
            'error' => $response->failed() ? ($data['error']['message'] ?? 'HTTP '.$response->status()) : null,
        ]);
    }

    /**
     * Split extracted file text into row batches, repeating the header/first row
     * in every batch so the column mapping does not get lost between batches.
     *
     * @return array<string>
     */
    protected function chunkTextForBatches(string $text, int $rowsPerBatch = 30): array
    {
        $lines = array_values(array_filter(
            preg_split('/\r\n|\r|\n/', trim($text)),
            fn (string $line) => trim($line) !== ''
        ));

        if (count($lines) <= $rowsPerBatch) {
            return [implode("\n", $lines)];
        }

        $header = $lines[0];
        $dataLines = array_slice($lines, 1);
        $chunks = [];
        foreach (array_chunk($dataLines, $rowsPerBatch) as $batch) {
            array_unshift($batch, $header);
            $chunks[] = implode("\n", $batch);
        }

        return $chunks;
    }

    /**
     * Execute extracted records locally and save into database.
     */
    public function executeExtractedRecords(array $records, array $extraContext = []): array
    {
        @set_time_limit(600);
        $executedResults = [];
        $successCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;

        SmartImportLogger::info('import.execution_started', [
            'records_count' => count($records),
            'extra_context' => $extraContext,
        ]);

        $duplicateKeywords = __('smart_import.service.duplicate_keywords');
        if (! is_array($duplicateKeywords)) {
            $duplicateKeywords = ['Duplicate', 'Already'];
        }

        // Installments must exist before the payments that reference them, so the
        // payment tool can find its target installment by date instead of guessing.
        $records = $this->sortRecordsForExecution($records);

        foreach ($records as $item) {
            $funcName = $item['tool'];
            $arguments = $item['arguments'] ?? [];
            if (! empty($extraContext)) {
                $arguments = array_merge($extraContext, $arguments);

                // The company picked by the user on the import page is authoritative
                // and must not be overridden by a company name guessed from the file.
                if (! empty($extraContext['company_id']) && array_key_exists('company_id', $arguments)) {
                    $arguments['company_id'] = (int) $extraContext['company_id'];
                }
            }

            // Execute local tool method
            $executionResult = $this->registry->executeTool($funcName, $arguments);
            $resultMsg = $executionResult['message'] ?? '';
            $isSuccess = ! empty($executionResult['success']);

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

            $itemCategory = $item['category'] ?? (__('smart_import.categories.'.$funcName, [], null) ?: __('smart_import.categories.default'));
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

        SmartImportLogger::info('import.execution', [
            'total' => $totalCount,
            'success' => $successCount,
            'duplicate' => $duplicateCount,
            'error' => $errorCount,
            'failures' => collect($executedResults)
                ->where('status', 'error')
                ->take(50)
                ->map(fn (array $row): string => trim($row['title']).' :: '.($row['result']['message'] ?? ''))
                ->values()
                ->all(),
        ]);

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
     * Keep the AI order but move dependent records (payments/collections) after
     * the parent records (installments/debts) so references resolve correctly.
     */
    protected function sortRecordsForExecution(array $records): array
    {
        $priority = [
            'create_installment_payment' => 1,
            'create_debt_collection' => 1,
        ];

        $indexed = [];
        foreach ($records as $index => $record) {
            $indexed[] = [
                'index' => $index,
                'priority' => $priority[$record['tool'] ?? ''] ?? 0,
                'record' => $record,
            ];
        }

        usort($indexed, fn (array $a, array $b): int => [$a['priority'], $a['index']] <=> [$b['priority'], $b['index']]);

        return array_column($indexed, 'record');
    }

    /**
     * Get icon for destination tool.
     */
    public static function getToolIcon(string $funcName): string
    {
        return match ($funcName) {
            'create_saudi_employee' => '🇸🇦',
            'create_foreign_employee' => '🌍',
            'create_customer_record' => '👥',
            'create_company_record' => '🏢',
            'create_treasury_transaction' => '💰',
            'create_invoice_record' => '🧾',
            'create_invoice_type' => '🧾',
            'create_service_record' => '📦',
            'create_installment_record' => '💳',
            'create_installment_payment' => '💵',
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

    /**
     * Extract tabular text from spreadsheets (XLSX, CSV) and Word documents (DOCX).
     */
    protected function extractTabularText(string $fullPath, string $extension): ?string
    {
        try {
            if ($extension === 'xlsx' && class_exists(Reader::class)) {
                $options = new Options;
                $reader = new Reader($options);
                $reader->open($fullPath);
                $lines = [];
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $cells = array_map(function ($val) {
                            if ($val instanceof \DateTimeInterface) {
                                return $val->format('Y-m-d');
                            }

                            return trim((string) $val);
                        }, $row->toArray());
                        if (empty(array_filter($cells))) {
                            continue;
                        }
                        $lines[] = implode(' | ', $cells);
                    }
                    // Only the first worksheet is processed for now.
                    break;
                }
                $reader->close();

                return implode("\n", $lines);
            }

            if ($extension === 'csv') {
                $lines = [];
                if (($handle = fopen($fullPath, 'r')) !== false) {
                    while (($data = fgetcsv($handle, 5000, ',')) !== false) {
                        if (empty(array_filter($data))) {
                            continue;
                        }
                        $lines[] = implode(' | ', array_map('trim', $data));
                    }
                    fclose($handle);
                }

                return implode("\n", $lines);
            }

            if (in_array($extension, ['docx', 'doc'])) {
                return $this->extractDocxText($fullPath);
            }
        } catch (\Throwable $e) {
            Log::warning('[SmartImport] Could not parse document: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Extract text content from a DOCX file by reading its XML structure.
     * Tables are converted to pipe-separated rows; paragraphs become plain lines.
     */
    protected function extractDocxText(string $fullPath): ?string
    {
        $zip = new \ZipArchive;
        if ($zip->open($fullPath) !== true) {
            Log::warning('[SmartImport] Could not open DOCX as ZIP: '.$fullPath);

            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xml) {
            Log::warning('[SmartImport] No word/document.xml found in DOCX: '.$fullPath);

            return null;
        }

        // Strip the BOM if present
        $xml = preg_replace('/^\xEF\xBB\xBF/', '', $xml);

        $dom = new \DOMDocument;
        $dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $lines = [];

        // Process body children in document order (tables and paragraphs)
        $body = $xpath->query('//w:body')->item(0);
        if (! $body) {
            return null;
        }

        foreach ($body->childNodes as $node) {
            if ($node->nodeName === 'w:tbl') {
                // Extract table rows as pipe-separated values
                $rows = $xpath->query('w:tr', $node);
                foreach ($rows as $row) {
                    $cells = [];
                    $cellNodes = $xpath->query('w:tc', $row);
                    foreach ($cellNodes as $cell) {
                        $cellText = trim($this->extractNodeText($xpath, $cell));
                        $cells[] = $cellText;
                    }
                    if (! empty(array_filter($cells))) {
                        $lines[] = implode(' | ', $cells);
                    }
                }
            } elseif ($node->nodeName === 'w:p') {
                // Extract paragraph text
                $text = trim($this->extractNodeText($xpath, $node));
                if ($text !== '') {
                    $lines[] = $text;
                }
            }
        }

        if (empty($lines)) {
            return null;
        }

        Log::info('[SmartImport] Extracted '.count($lines).' lines from DOCX file.');

        return implode("\n", $lines);
    }

    /**
     * Recursively extract all text (w:t) from a DOM node.
     */
    protected function extractNodeText(\DOMXPath $xpath, \DOMNode $node): string
    {
        $texts = $xpath->query('.//w:t', $node);
        $parts = [];
        foreach ($texts as $t) {
            $parts[] = $t->textContent;
        }

        return implode('', $parts);
    }

    /**
     * Build the base instruction prompt sent with every Gemini request.
     */
    protected function buildInstructionPrompt(): string
    {
        $prompt = __('smart_import.service.instruction_prompt');

        $companyNames = Company::pluck('trade_name')->filter()->values()->toArray();
        if (! empty($companyNames)) {
            $prompt .= "\n\n".__('smart_import.service.company_context_instruction', [
                'companies' => implode(' | ', $companyNames),
            ]);
        }

        return $prompt;
    }

    /**
     * Analyze one batch of PDF pages. Public so the import page can run a
     * single batch per HTTP request instead of one long-running request.
     *
     * @param  array<string, mixed>  $logContext  Extra data (run id, batch number...) written to the test channel
     * @return array{success: bool, message?: string, records?: array<int, array<string, mixed>>, truncated?: bool}
     */
    public function analyzePdfPages(string $filePath, string $disk, ?array $allowedTools, int $from, int $to, array $logContext = []): array
    {
        $apiKey = Setting::getGeminiApiKey();

        if (empty($apiKey)) {
            return ['success' => false, 'message' => __('smart_import.service.no_api_key')];
        }

        $fullPath = Storage::disk($disk)->path($filePath);
        if (! file_exists($fullPath)) {
            return ['success' => false, 'message' => __('smart_import.service.file_not_found')];
        }

        $totalPages = $this->getPdfPageCount($fullPath) ?? $to;

        $parts = [[
            'text' => $this->buildInstructionPrompt()."\n\n".__('smart_import.service.pdf_page_notice', [
                'from' => $from,
                'to' => $to,
                'total' => $totalPages,
            ]),
        ]];

        // For batches that do not start at page 1, attach page 1 as a reference
        // page so the model always sees the statement's column headers and can
        // tell the installments (debt) column apart from the settled column.
        if ($from > 1) {
            $reference = $this->renderPdfPages($fullPath, 1, 1);
            if (! empty($reference[0])) {
                $parts[] = [
                    'text' => __('smart_import.service.reference_page_notice'),
                ];
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => 'application/pdf',
                        'data' => $reference[0],
                    ],
                ];
            }
        }

        foreach ($this->renderPdfPages($fullPath, $from, $to) as $pageData) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => 'application/pdf',
                    'data' => $pageData,
                ],
            ];
        }

        $call = $this->callGemini(
            $apiKey,
            $parts,
            $this->registry->getDeclarationsForGemini($allowedTools),
            $logContext + ['pages' => $from === $to ? (string) $from : "{$from}-{$to}", 'total_pages' => $totalPages],
        );

        if (empty($call['success'])) {
            SmartImportLogger::error('import.pdf_batch_failed', $logContext + [
                'pages' => $from === $to ? (string) $from : "{$from}-{$to}",
                'message' => $call['message'] ?? null,
            ]);

            $isQuota = str_contains(strtolower($call['message'] ?? ''), 'quota');

            return [
                'success' => false,
                'message' => $call['message'],
                'quota_exceeded' => $isQuota,
            ];
        }

        $records = [];
        foreach ($call['candidates'] as $part) {
            if (isset($part['functionCall'])) {
                $records[] = $this->buildExtractedRecord($part);
            }
        }

        // Detect premature stop on multi-page batches (e.g. emitted only 1 or 2 calls for 2+ pages)
        $pageSpan = $to - $from + 1;
        if ($pageSpan >= 2 && count($records) <= 2) {
            SmartImportLogger::warning('gemini.pdf_premature_stop_retry', $logContext + [
                'pages' => "{$from}-{$to}",
                'got_records' => count($records),
            ]);

            $retryCall = $this->callGemini(
                $apiKey,
                $parts,
                $this->registry->getDeclarationsForGemini($allowedTools),
                $logContext + ['pages' => $from === $to ? (string) $from : "{$from}-{$to}", 'total_pages' => $totalPages, 'retry' => true],
            );

            if (! empty($retryCall['success'])) {
                $retryRecords = [];
                foreach ($retryCall['candidates'] as $part) {
                    if (isset($part['functionCall'])) {
                        $retryRecords[] = $this->buildExtractedRecord($part);
                    }
                }

                if (count($retryRecords) > count($records)) {
                    $call = $retryCall;
                    $records = $retryRecords;
                }
            }
        }

        SmartImportLogger::info('import.pdf_batch_done', $logContext + [
            'pages' => $from === $to ? (string) $from : "{$from}-{$to}",
            'records' => count($records),
            'finish' => $call['finish_reason'],
            'truncated' => $call['finish_reason'] === 'MAX_TOKENS',
        ]);

        return [
            'success' => true,
            'records' => $records,
            'truncated' => $call['finish_reason'] === 'MAX_TOKENS',
        ];
    }

    /**
     * Number of pages of a PDF, or null when it cannot be read.
     */
    public function getPdfPageCount(string $pdfPath): ?int
    {
        try {
            $mpdf = new Mpdf;
            $pageCount = $mpdf->setSourceFile($pdfPath);
            unset($mpdf);

            return $pageCount > 0 ? $pageCount : null;
        } catch (\Throwable $e) {
            Log::warning('[SmartImport] Could not read PDF page count: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Render a page range into standalone single-page PDFs (base64 encoded),
     * so Gemini only receives the pages of the current batch.
     *
     * @return array<int, string>
     */
    protected function renderPdfPages(string $pdfPath, int $from, int $to): array
    {
        $pages = [];

        for ($pageNo = $from; $pageNo <= $to; $pageNo++) {
            $mpdf = new Mpdf;
            $mpdf->setSourceFile($pdfPath);
            $mpdf->useTemplate($mpdf->importPage($pageNo));
            $pages[] = base64_encode($mpdf->Output('', Destination::STRING_RETURN));

            unset($mpdf);
        }

        return $pages;
    }

    /**
     * Normalize a single Gemini functionCall part into a detected record.
     *
     * @return array{tool: string, category: string, icon: string, title: mixed, arguments: array<string, mixed>}
     */
    protected function buildExtractedRecord(array $part): array
    {
        $funcName = $part['functionCall']['name'];
        $arguments = $part['functionCall']['args'] ?? [];

        $itemTitle = $arguments['name'] ?? $arguments['title'] ?? $arguments['beneficiary_name'] ?? $arguments['debt_identifier'] ?? $arguments['sub_item'] ?? $arguments['fund_name'] ?? $arguments['subject'] ?? $arguments['employee_identifier'] ?? $arguments['member_identifier'] ?? $arguments['device_number'] ?? $arguments['customer_name'] ?? $arguments['invoice_number'] ?? $arguments['description'] ?? __('smart_import.service.unnamed_record');

        return [
            'tool' => $funcName,
            'category' => __('smart_import.categories.'.$funcName, [], null) ?: __('smart_import.categories.default'),
            'icon' => self::getToolIcon($funcName),
            'title' => $itemTitle,
            'arguments' => $arguments,
        ];
    }

    /**
     * Accumulate the per-tool counters shown in the preview.
     */
    protected function registerToolSummary(array &$toolsSummary, array $record): void
    {
        $funcName = $record['tool'];

        if (! isset($toolsSummary[$funcName])) {
            $toolsSummary[$funcName] = [
                'tool' => $funcName,
                'name' => $record['category'],
                'target_label' => __('smart_import.targets.'.$funcName, [], null) ?: $record['category'],
                'icon' => $record['icon'],
                'count' => 0,
            ];
        }

        $toolsSummary[$funcName]['count']++;
    }
}
