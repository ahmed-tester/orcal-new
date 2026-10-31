<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Setting;
use App\Services\AI\GeminiProcessorService;
use App\Services\AI\SmartImportLogger;
use App\Services\CompanyFormService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SmartFileImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.smart-file-import';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdminOrOwner() || $user->can('manage_smart_import'));
    }

    public ?array $data = [];

    public ?array $processingReport = null;

    public bool $isProcessing = false;

    // Pending analysis confirmation state for smart auto-detection
    public ?array $pendingAnalysis = null;

    public ?string $alternativeTool = null;

    public ?int $selectedCompanyId = null;

    public bool $isConfirming = false;

    // Current import run: long extractions are split over several short requests
    // so the browser/server never has to keep one request alive for minutes.
    public ?string $runId = null;

    public int $processedPages = 0;

    public int $totalPages = 0;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.system_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('smart_import.nav_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('smart_import.page_title');
    }

    public function getAllowedExtensionsTextProperty(): string
    {
        $exts = Setting::getAllowedUploadExtensions();

        return ! empty($exts) ? implode(' • ', array_map('strtoupper', $exts)) : 'PDF • JPG • PNG • TXT';
    }

    public function getMaxFileSizeTextProperty(): string
    {
        $kb = Setting::getMaxUploadSizeKb();

        return ($kb >= 1024)
            ? round($kb / 1024, 1).' '.__('smart_import.fields.unit_mb')
            : $kb.' '.__('smart_import.fields.unit_kb');
    }

    public function getCompaniesProperty(): array
    {
        $user = auth()->user();
        $query = Company::query();
        if ($user && ! $user->isSuperAdmin()) {
            $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            $query->whereIn('id', ! empty($companyIds) ? $companyIds : [0]);
        }

        return $query->pluck('trade_name', 'id')->toArray();
    }

    public function mount(): void
    {
        $this->selectedCompanyId = null;
        $this->form->fill([
            'target_tool' => 'all',
            'company_id' => null,
        ]);

        SmartImportLogger::info('page.mounted', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'user_email' => auth()->user()?->email,
            'ip' => request()->ip(),
            'user_agent' => Str::limit(request()->userAgent() ?? '', 100),
        ]);

        $this->restorePendingRunIfAvailable();
    }

    public function updated($property, $value = null): void
    {
        if (str_starts_with((string) $property, 'data.')) {
            $field = substr((string) $property, 5);
            $context = [
                'field' => $field,
                'value' => is_scalar($value) ? $value : (is_array($value) ? array_keys($value) : gettype($value)),
                'user_id' => auth()->id(),
            ];

            if ($field === 'uploaded_file' && is_string($value) && $value !== '') {
                $disk = Storage::disk('public');
                $context['exists_on_disk'] = $disk->exists($value);
                if ($context['exists_on_disk']) {
                    $context['size_bytes'] = $disk->size($value);
                    $context['size_kb'] = round($context['size_bytes'] / 1024, 2);
                }
            }

            SmartImportLogger::info('page.form_field_updated', $context);
        }
    }

    /**
     * Restore pending or recently completed extraction run if browser was refreshed or reconnected.
     */
    protected function restorePendingRunIfAvailable(): void
    {
        $userId = auth()->id();
        $activeRunId = Cache::get($this->userActiveRunKey($userId));
        if (! $activeRunId) {
            SmartImportLogger::info('page.no_active_run_to_restore', [
                'user_id' => $userId,
            ]);

            return;
        }

        $state = Cache::get($this->runCacheKey($activeRunId));
        if (! is_array($state)) {
            SmartImportLogger::warning('page.active_run_cache_missing', [
                'user_id' => $userId,
                'run_id' => $activeRunId,
            ]);
            Cache::forget($this->userActiveRunKey($userId));

            return;
        }

        SmartImportLogger::info('page.run_restored_from_cache', [
            'user_id' => $userId,
            'run_id' => $activeRunId,
            'status' => $state['status'] ?? 'unknown',
            'target_tool' => $state['target_tool'] ?? null,
            'processed_pages' => $state['processed_pages'] ?? 0,
            'total_pages' => $state['total_pages'] ?? 0,
            'records_count' => count($state['records'] ?? []),
            'has_analysis' => ! empty($state['analysis']),
        ]);

        if (($state['status'] ?? null) === 'completed' && ! empty($state['analysis'])) {
            $this->runId = $activeRunId;
            $this->pendingAnalysis = $state['analysis'];
            $this->alternativeTool = $state['analysis']['first_tool'] ?? 'create_saudi_employee';
            $this->selectedCompanyId = $state['company_id'] ?? null;
            $this->processedPages = $state['total_pages'] ?? 0;
            $this->totalPages = $state['total_pages'] ?? 0;
            $this->isProcessing = false;
        } elseif (($state['status'] ?? null) === 'in_progress' || empty($state['status'])) {
            $this->runId = $activeRunId;
            $this->processedPages = $state['processed_pages'] ?? 0;
            $this->totalPages = $state['total_pages'] ?? 0;
            $this->selectedCompanyId = $state['company_id'] ?? null;
            $this->isProcessing = true;
        }
    }

    public function form(Schema $schema): Schema
    {
        $allowedMimes = array_merge(
            Setting::getAllowedUploadMimeTypes(),
            ['text/plain', 'text/csv', 'application/vnd.ms-excel']
        );
        $allowedMimes = array_values(array_unique($allowedMimes));
        $maxSizeKb = Setting::getMaxUploadSizeKb();

        return $schema
            ->components([
                Section::make(__('smart_import.section_title'))
                    ->description(__('smart_import.section_desc'))
                    ->schema([
                        Select::make('target_tool')
                            ->label(__('smart_import.target_label'))
                            ->options(__('smart_import.targets'))
                            ->default('all')
                            ->searchable()
                            ->required()
                            ->live(),

                        Select::make('company_id')
                            ->label(__('smart_import.confirmation.target_company_label'))
                            ->helperText(__('smart_import.confirmation.company_assigned_hint'))
                            ->options(fn () => $this->companies)
                            ->placeholder(__('smart_import.confirmation.select_company_placeholder'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->visible(fn (callable $get) => in_array($get('target_tool'), ['create_saudi_employee', 'create_foreign_employee'])),

                        FileUpload::make('uploaded_file')
                            ->label(__('smart_import.file_label'))
                            ->helperText(__('smart_import.file_helper', [
                                'extensions' => $this->allowedExtensionsText,
                                'size' => $this->maxFileSizeText,
                            ]))
                            ->acceptedFileTypes($allowedMimes)
                            ->maxSize($maxSizeKb)
                            ->disk('public')
                            ->directory('smart-imports')
                            ->visibility('public'),
                    ]),
            ])
            ->statePath('data');
    }

    public function processFile(): void
    {
        SmartImportLogger::info('page.process_file_clicked', [
            'user_id' => auth()->id(),
            'is_processing' => $this->isProcessing,
            'is_confirming' => $this->isConfirming,
            'current_run_id' => $this->runId,
            'form_state' => $this->form->getState(),
        ]);

        if ($this->isProcessing || $this->isConfirming) {
            SmartImportLogger::warning('page.process_blocked_already_running', [
                'is_processing' => $this->isProcessing,
                'is_confirming' => $this->isConfirming,
                'run_id' => $this->runId,
            ]);

            return;
        }

        $formData = $this->form->getState();
        $filePath = $formData['uploaded_file'] ?? null;
        $targetTool = $formData['target_tool'] ?? 'all';
        $companyId = $formData['company_id'] ?? $this->selectedCompanyId ?? null;

        if (! $filePath) {
            SmartImportLogger::warning('page.process_blocked_no_file_uploaded', [
                'form_data' => $formData,
            ]);

            Notification::make()
                ->title(__('smart_import.notifications.select_file'))
                ->warning()
                ->send();

            return;
        }

        if (! empty($companyId)) {
            $this->selectedCompanyId = (int) $companyId;
        }

        $this->processingReport = null;
        $this->pendingAnalysis = null;

        $this->startRun($filePath, $targetTool);
    }

    /**
     * Start a new extraction run for the given file and destination.
     */
    protected function startRun(string $filePath, string $targetTool): void
    {
        $processor = new GeminiProcessorService;

        $ranges = [];
        $totalPages = 0;
        $fullPath = Storage::disk('public')->path($filePath);
        $fileExists = file_exists($fullPath);
        $fileSize = $fileExists ? filesize($fullPath) : 0;
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        SmartImportLogger::info('import.start_run_pre_check', [
            'file_path' => $filePath,
            'full_path' => $fullPath,
            'file_exists' => $fileExists,
            'file_size_kb' => round($fileSize / 1024, 2),
            'extension' => $extension,
            'target_tool' => $targetTool,
            'company_id' => $this->selectedCompanyId,
        ]);

        if (! $fileExists) {
            SmartImportLogger::error('import.file_missing_on_disk', [
                'file_path' => $filePath,
                'full_path' => $fullPath,
            ]);

            Notification::make()
                ->title(__('smart_import.notifications.error_title'))
                ->body(__('smart_import.service.file_not_found'))
                ->danger()
                ->send();

            return;
        }

        if ($extension === 'pdf') {
            $totalPages = $processor->getPdfPageCount($fullPath) ?? 0;

            SmartImportLogger::info('import.pdf_page_count_detected', [
                'full_path' => $fullPath,
                'total_pages' => $totalPages,
            ]);

            if ($totalPages > 1) {
                for ($from = 1; $from <= $totalPages; $from += GeminiProcessorService::PDF_PAGES_PER_BATCH) {
                    $ranges[] = [
                        'from' => $from,
                        'to' => min($from + GeminiProcessorService::PDF_PAGES_PER_BATCH - 1, $totalPages),
                    ];
                }
            }
        }

        $state = [
            'id' => (string) Str::uuid(),
            'file_path' => $filePath,
            'disk' => 'public',
            'target_tool' => $targetTool,
            'tools' => $targetTool === 'all' ? null : $this->expandAllowedTools($targetTool),
            'company_id' => $this->selectedCompanyId,
            'ranges' => $ranges,
            'index' => 0,
            'records' => [],
            'failed_pages' => 0,
            'errors' => [],
            'truncated' => false,
            'total_pages' => $totalPages,
            'processed_pages' => 0,
            'started_at' => microtime(true),
            'status' => 'in_progress',
        ];

        Cache::put($this->runCacheKey($state['id']), $state, now()->addHours(2));
        Cache::put($this->userActiveRunKey(auth()->id()), $state['id'], now()->addHours(2));
        Cache::forget($this->batchLockKey($state['id']));

        SmartImportLogger::info('import.run_started', [
            'run' => $state['id'],
            'file' => $filePath,
            'target' => $targetTool,
            'tools' => $state['tools'] === null ? 'auto' : implode(',', $state['tools']),
            'company_id' => $state['company_id'],
            'pages' => $totalPages,
            'batches' => count($ranges),
            'user_id' => auth()->id(),
        ]);

        $this->runId = $state['id'];
        $this->isProcessing = true;
        $this->processedPages = 0;
        $this->totalPages = $totalPages;

        $this->processNextBatch();
    }

    /**
     * Analyze the next page batch. Called repeatedly by the polling element
     * on the view, and once directly when the run starts.
     */
    public function processNextBatch(): void
    {
        if (! $this->isProcessing || empty($this->runId)) {
            return;
        }

        $runId = $this->runId;

        // Re-entrancy guard: a Gemini batch can take 15-30s while the page polls
        // every few seconds. Without this guard the poll re-invokes the batch while
        // it is in flight, running the same pages twice and corrupting the state.
        if (! Cache::add($this->batchLockKey($runId), 1, now()->addMinutes(2))) {
            SmartImportLogger::info('import.batch_locked_waiting_next_poll', [
                'run' => $runId,
                'poll_time' => now()->toIso8601String(),
            ]);

            return;
        }

        try {
            $state = Cache::get($this->runCacheKey($runId));

            if (! is_array($state)) {
                SmartImportLogger::error('import.state_missing_from_cache', [
                    'run' => $runId,
                    'user_id' => auth()->id(),
                ]);

                $this->isProcessing = false;
                $this->runId = null;
                Cache::forget($this->userActiveRunKey(auth()->id()));

                Notification::make()
                    ->title(__('smart_import.notifications.error_title'))
                    ->body(__('smart_import.notifications.generic_error'))
                    ->danger()
                    ->send();

                return;
            }

            if (($state['status'] ?? null) === 'completed') {
                SmartImportLogger::info('import.poll_found_completed_run', [
                    'run' => $runId,
                    'records' => count($state['records'] ?? []),
                ]);

                $this->applyCompletedRun($state);

                return;
            }

            $this->runBatch($state);
        } finally {
            Cache::forget($this->batchLockKey($runId));
        }
    }

    protected function runBatch(array $state): void
    {
        $processor = new GeminiProcessorService;

        // Non-paginated files (spreadsheets, images, single-page PDFs) are
        // analyzed in one pass, exactly as before.
        if (empty($state['ranges'])) {
            SmartImportLogger::info('import.single_pass_started', [
                'run' => $state['id'],
                'file' => $state['file_path'],
                'tools' => $state['tools'] === null ? 'auto' : implode(',', $state['tools']),
            ]);

            try {
                $analysis = $processor->analyzeFile($state['file_path'], $state['disk'], $state['tools']);
            } catch (\Throwable $e) {
                SmartImportLogger::error('import.single_pass_crashed', [
                    'run' => $state['id'],
                    'error' => $e->getMessage(),
                ]);
                $analysis = ['success' => false, 'message' => $e->getMessage()];
            }

            $this->completeRun($state, $analysis);

            return;
        }

        $range = $state['ranges'][$state['index']];
        $pageCount = $range['to'] - $range['from'] + 1;
        $startedAt = microtime(true);

        SmartImportLogger::info('import.batch_started', [
            'run' => $state['id'],
            'batch' => $state['index'] + 1,
            'of' => count($state['ranges']),
            'pages' => $range['from'] === $range['to'] ? (string) $range['from'] : "{$range['from']}-{$range['to']}",
            'tools' => $state['tools'] === null ? 'auto' : implode(',', $state['tools']),
            'records_so_far' => count($state['records']),
        ]);

        try {
            $batch = $processor->analyzePdfPages(
                $state['file_path'],
                $state['disk'],
                $state['tools'],
                $range['from'],
                $range['to'],
                ['run' => $state['id'], 'batch' => $state['index'] + 1],
            );
        } catch (\Throwable $e) {
            SmartImportLogger::error('import.batch_crashed', [
                'run' => $state['id'],
                'batch' => $state['index'] + 1,
                'error' => $e->getMessage(),
            ]);
            $batch = ['success' => false, 'message' => $e->getMessage()];
        }

        SmartImportLogger::info('import.batch_finished', [
            'run' => $state['id'],
            'batch' => $state['index'] + 1,
            'pages' => $range['from'] === $range['to'] ? (string) $range['from'] : "{$range['from']}-{$range['to']}",
            'seconds' => round(microtime(true) - $startedAt, 2),
            'ok' => (bool) ($batch['success'] ?? false),
            'records' => count($batch['records'] ?? []),
            'message' => $batch['message'] ?? null,
        ]);

        if (empty($batch['success'])) {
            $state['failed_pages'] += $pageCount;
            $state['errors'][] = $batch['message'] ?? __('smart_import.service.unknown_failure');
            SmartImportLogger::warning('import.batch_reported_failure', [
                'run' => $state['id'],
                'batch' => $state['index'] + 1,
                'error' => end($state['errors']),
            ]);

            // If quota is exhausted on the API key, abort immediately to avoid wasting user's time
            if (! empty($batch['quota_exceeded'])) {
                SmartImportLogger::warning('import.batch_quota_exhausted_aborting', [
                    'run' => $state['id'],
                    'batch' => $state['index'] + 1,
                ]);

                $analysis = $processor->buildAnalysisResult(
                    $state['file_path'],
                    $state['disk'],
                    $state['records'],
                    $state['failed_pages'],
                    $state['total_pages'],
                    $state['truncated'],
                    $state['errors'],
                );

                $this->completeRun($state, $analysis);

                return;
            }
        } else {
            $state['records'] = array_merge($state['records'], $batch['records']);
            $state['truncated'] = $state['truncated'] || ! empty($batch['truncated']);
        }

        $state['index']++;
        $state['processed_pages'] += $pageCount;
        $this->processedPages = $state['processed_pages'];

        if ($state['index'] >= count($state['ranges'])) {
            $analysis = $processor->buildAnalysisResult(
                $state['file_path'],
                $state['disk'],
                $state['records'],
                $state['failed_pages'],
                $state['total_pages'],
                $state['truncated'],
                $state['errors'],
            );

            $this->completeRun($state, $analysis);

            return;
        }

        Cache::put($this->runCacheKey($state['id']), $state, now()->addHours(2));
    }

    /**
     * Finish a run: show the preview, or execute the records in the chosen destination.
     */
    protected function completeRun(array $state, array $analysis): void
    {
        $state['status'] = 'completed';
        $state['analysis'] = $analysis;
        Cache::put($this->runCacheKey($state['id']), $state, now()->addHours(2));

        SmartImportLogger::info('import.run_finished', [
            'run' => $state['id'],
            'file' => $state['file_path'],
            'pages' => $state['total_pages'] ?? 0,
            'failed_pages' => $state['failed_pages'] ?? 0,
            'records' => $analysis['success'] ? ($analysis['total_count'] ?? 0) : 0,
            'truncated' => $state['truncated'] ?? false,
            'errors' => $state['errors'] ?? [],
            'message' => $analysis['message'] ?? null,
            'seconds_total' => isset($state['started_at']) ? round(microtime(true) - $state['started_at'], 1) : null,
        ]);

        $this->applyCompletedRun($state);
    }

    protected function applyCompletedRun(array $state): void
    {
        $analysis = $state['analysis'] ?? [];
        $this->isProcessing = false;
        $this->processedPages = $state['total_pages'] ?? $this->processedPages;

        if (empty($analysis['success'])) {
            SmartImportLogger::warning('import.run_completed_with_error', [
                'run' => $state['id'],
                'message' => $analysis['message'] ?? 'Unknown error',
                'errors' => $state['errors'] ?? [],
            ]);

            $this->runId = null;
            Cache::forget($this->runCacheKey($state['id']));
            Cache::forget($this->userActiveRunKey(auth()->id()));

            Notification::make()
                ->title(__('smart_import.notifications.error_title'))
                ->body($analysis['message'] ?? __('smart_import.notifications.generic_error'))
                ->danger()
                ->duration(8000)
                ->send();

            return;
        }

        if (! empty($analysis['warning'])) {
            SmartImportLogger::warning('import.run_completed_with_warning', [
                'run' => $state['id'],
                'warning' => $analysis['warning'],
            ]);

            Notification::make()
                ->title(__('smart_import.notifications.error_title'))
                ->body($analysis['warning'])
                ->warning()
                ->duration(10000)
                ->send();
        }

        // Smart auto-detection: show the preview so the user can confirm or re-route.
        if (($state['target_tool'] ?? 'all') === 'all') {
            SmartImportLogger::info('import.showing_auto_detection_preview', [
                'run' => $state['id'],
                'first_tool' => $analysis['first_tool'] ?? null,
                'total_count' => $analysis['total_count'] ?? 0,
                'tools_summary' => $analysis['tools_summary'] ?? [],
            ]);

            $this->pendingAnalysis = $analysis;
            $this->alternativeTool = $analysis['first_tool'] ?? 'create_saudi_employee';

            $destName = $analysis['tools_summary'][0]['name'] ?? '';
            Notification::make()
                ->title(__('smart_import.confirmation.analyzed_notice_title'))
                ->body(__('smart_import.confirmation.analyzed_notice_body', [
                    'destination' => $destName,
                    'count' => $analysis['total_count'] ?? 0,
                ]))
                ->info()
                ->duration(7000)
                ->send();

            return;
        }

        $extraContext = [];
        if (! empty($state['company_id'])) {
            $extraContext['company_id'] = (int) $state['company_id'];
        }

        SmartImportLogger::info('import.direct_tool_execution_started', [
            'run' => $state['id'],
            'target_tool' => $state['target_tool'] ?? 'unknown',
            'records_count' => count($analysis['records'] ?? []),
            'company_id' => $state['company_id'] ?? null,
        ]);

        $result = (new GeminiProcessorService)->executeExtractedRecords($analysis['records'], $extraContext);

        SmartImportLogger::info('import.direct_tool_execution_finished', [
            'run' => $state['id'],
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? null,
        ]);

        Cache::forget($this->runCacheKey($state['id']));
        Cache::forget($this->userActiveRunKey(auth()->id()));
        $this->runId = null;

        $this->processingReport = $result;

        $this->sendExecutionNotification($result);
    }

    protected function runCacheKey(?string $runId): string
    {
        return 'smart-import-run:'.($runId ?? 'unknown');
    }

    protected function userActiveRunKey(int|string|null $userId): string
    {
        return 'smart-import-user-active-run:'.($userId ?? 'guest');
    }

    protected function batchLockKey(?string $runId): string
    {
        return 'smart-import-batch-lock:'.($runId ?? 'unknown');
    }

    /**
     * Confirm inserting extracted data into AI-detected destination.
     */
    public function confirmAndInsert(): void
    {
        SmartImportLogger::info('page.confirm_and_insert_clicked', [
            'user_id' => auth()->id(),
            'run_id' => $this->runId,
            'records_count' => count($this->pendingAnalysis['records'] ?? []),
            'selected_company_id' => $this->selectedCompanyId,
        ]);

        if (empty($this->pendingAnalysis) || empty($this->pendingAnalysis['records'])) {
            SmartImportLogger::warning('page.confirm_and_insert_blocked_no_records', [
                'run_id' => $this->runId,
            ]);

            Notification::make()
                ->title(__('smart_import.notifications.generic_error'))
                ->danger()
                ->send();

            return;
        }

        $this->isConfirming = true;
        @set_time_limit(600);
        $processor = new GeminiProcessorService;
        $extraContext = [];
        if (! empty($this->selectedCompanyId)) {
            $extraContext['company_id'] = (int) $this->selectedCompanyId;
        }

        $result = $processor->executeExtractedRecords($this->pendingAnalysis['records'], $extraContext);

        SmartImportLogger::info('page.confirm_and_insert_finished', [
            'run_id' => $this->runId,
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? null,
        ]);

        if ($this->runId) {
            Cache::forget($this->runCacheKey($this->runId));
        }
        Cache::forget($this->userActiveRunKey(auth()->id()));
        $this->runId = null;

        $this->isConfirming = false;
        $this->pendingAnalysis = null;
        $this->processingReport = $result;

        $this->sendExecutionNotification($result);
    }

    /**
     * Apply alternative tool chosen by user and re-process file.
     */
    public function applyAlternativeTool(): void
    {
        SmartImportLogger::info('page.apply_alternative_tool_clicked', [
            'user_id' => auth()->id(),
            'alternative_tool' => $this->alternativeTool,
            'file_path' => $this->pendingAnalysis['file_path'] ?? null,
        ]);

        if (empty($this->alternativeTool) || empty($this->pendingAnalysis['file_path'])) {
            SmartImportLogger::warning('page.apply_alternative_tool_blocked_missing_data', [
                'alternative_tool' => $this->alternativeTool,
                'has_file' => ! empty($this->pendingAnalysis['file_path']),
            ]);

            Notification::make()
                ->title(__('smart_import.confirmation.select_alt_warning'))
                ->warning()
                ->send();

            return;
        }

        $filePath = $this->pendingAnalysis['file_path'];
        $chosenTool = $this->alternativeTool;

        $this->pendingAnalysis = null;
        $this->processingReport = null;

        $this->startRun($filePath, $chosenTool);
    }

    /**
     * Cancel pending analysis and reset component state.
     */
    public function cancelPendingAnalysis(): void
    {
        SmartImportLogger::info('page.cancel_pending_analysis_clicked', [
            'user_id' => auth()->id(),
            'run_id' => $this->runId,
        ]);

        if ($this->runId) {
            Cache::forget($this->runCacheKey($this->runId));
        }
        Cache::forget($this->userActiveRunKey(auth()->id()));
        $this->runId = null;
        $this->pendingAnalysis = null;
        $this->alternativeTool = null;
    }

    /**
     * Send result notification to user.
     */
    protected function sendExecutionNotification(array $result): void
    {
        if (! empty($result['success'])) {
            Notification::make()
                ->title(__('smart_import.notifications.success_title'))
                ->body($result['message'] ?? '')
                ->success()
                ->duration(7000)
                ->send();
        } else {
            Notification::make()
                ->title(__('smart_import.notifications.error_title'))
                ->body($result['message'] ?? __('smart_import.notifications.generic_error'))
                ->danger()
                ->duration(8000)
                ->send();
        }
    }

    /**
     * Expand allowed tools to include paired payment/collection tools.
     */
    protected function expandAllowedTools(string $tool): array
    {
        $tools = [$tool];
        if ($tool === 'create_installment_record') {
            $tools[] = 'create_installment_payment';
        } elseif ($tool === 'create_debt_record') {
            $tools[] = 'create_debt_collection';
        }

        return $tools;
    }
}
