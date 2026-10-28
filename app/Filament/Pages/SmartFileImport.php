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
use Illuminate\Support\Facades\Log;
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

        return ! empty($exts) ? implode(' · ', array_map('strtoupper', $exts)) : 'PDF · JPG · PNG · TXT';
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
        if ($this->isProcessing || $this->isConfirming) {
            return;
        }

        $formData = $this->form->getState();
        $filePath = $formData['uploaded_file'] ?? null;
        $targetTool = $formData['target_tool'] ?? 'all';
        $companyId = $formData['company_id'] ?? $this->selectedCompanyId ?? null;

        if (! $filePath) {
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

        if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'pdf') {
            $totalPages = $processor->getPdfPageCount($fullPath) ?? 0;

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
        ];

        Cache::put($this->runCacheKey($state['id']), $state, now()->addHours(2));

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

        // Re-entrancy guard: a Gemini batch can take 30-80s while the page polls
        // more frequently. Without this guard the poll re-invokes the batch while
        // it is in flight, running the same pages twice and corrupting the state.
        if (! Cache::add($this->batchLockKey($this->runId), 1, now()->addMinutes(5))) {
            return; // a batch is already running for this run; wait for the next poll
        }

        try {
            $state = Cache::get($this->runCacheKey($this->runId));

            if (! is_array($state)) {
                $this->isProcessing = false;
                $this->runId = null;

                Notification::make()
                    ->title(__('smart_import.notifications.error_title'))
                    ->body(__('smart_import.notifications.generic_error'))
                    ->danger()
                    ->send();

                return;
            }

            $this->runBatch($state);
        } finally {
            Cache::forget($this->batchLockKey($this->runId));
        }
    }

    protected function runBatch(array $state): void
    {
        $processor = new GeminiProcessorService;

        $processor = new GeminiProcessorService;

        // Non-paginated files (spreadsheets, images, single-page PDFs) are
        // analyzed in one pass, exactly as before.
        if (empty($state['ranges'])) {
            try {
                $analysis = $processor->analyzeFile($state['file_path'], $state['disk'], $state['tools']);
            } catch (\Throwable $e) {
                Log::error('[SmartImport] Analysis failed: '.$e->getMessage());
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
            Log::error('[SmartImport] PDF batch crashed: '.$e->getMessage());
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
            Log::warning('[SmartImport] PDF batch failed: '.end($state['errors']));

            // If quota is exhausted on the API key, abort immediately to avoid wasting user's time
            if (! empty($batch['quota_exceeded'])) {
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
        Cache::forget($this->runCacheKey($state['id']));

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

        $this->runId = null;
        $this->isProcessing = false;
        $this->processedPages = $state['total_pages'] ?? $this->processedPages;

        if (empty($analysis['success'])) {
            Notification::make()
                ->title(__('smart_import.notifications.error_title'))
                ->body($analysis['message'] ?? __('smart_import.notifications.generic_error'))
                ->danger()
                ->duration(8000)
                ->send();

            return;
        }

        if (! empty($analysis['warning'])) {
            Notification::make()
                ->title(__('smart_import.notifications.error_title'))
                ->body($analysis['warning'])
                ->warning()
                ->duration(10000)
                ->send();
        }

        // Smart auto-detection: show the preview so the user can confirm or re-route.
        if (($state['target_tool'] ?? 'all') === 'all') {
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

        $result = (new GeminiProcessorService)->executeExtractedRecords($analysis['records'], $extraContext);

        $this->processingReport = $result;

        $this->sendExecutionNotification($result);
    }

    protected function runCacheKey(string $runId): string
    {
        return 'smart-import-run:'.$runId;
    }

    protected function batchLockKey(string $runId): string
    {
        return 'smart-import-batch-lock:'.$runId;
    }

    /**
     * Confirm inserting extracted data into AI-detected destination.
     */
    public function confirmAndInsert(): void
    {
        if (empty($this->pendingAnalysis) || empty($this->pendingAnalysis['records'])) {
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
        if (empty($this->alternativeTool) || empty($this->pendingAnalysis['file_path'])) {
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
