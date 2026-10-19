<?php

namespace App\Filament\Pages;

use App\Services\AI\GeminiProcessorService;
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

use App\Models\Setting;
use App\Models\Company;
use App\Services\CompanyFormService;

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
        return !empty($exts) ? implode(' · ', array_map('strtoupper', $exts)) : 'PDF · JPG · PNG · TXT';
    }

    public function getMaxFileSizeTextProperty(): string
    {
        $kb = Setting::getMaxUploadSizeKb();
        return ($kb >= 1024) 
            ? round($kb / 1024, 1) . ' ' . __('smart_import.fields.unit_mb') 
            : $kb . ' ' . __('smart_import.fields.unit_kb');
    }

    public function getCompaniesProperty(): array
    {
        $user = auth()->user();
        $query = Company::query();
        if ($user && !$user->isSuperAdmin()) {
            $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            $query->whereIn('id', !empty($companyIds) ? $companyIds : [0]);
        }
        return $query->pluck('trade_name', 'id')->toArray();
    }

    public function mount(): void
    {
        $this->selectedCompanyId = auth()->user()?->company_id ?? Company::first()?->id;
        $this->form->fill([
            'target_tool' => 'all',
            'company_id' => $this->selectedCompanyId,
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
                            ->default(fn () => $this->selectedCompanyId ?? auth()->user()?->company_id ?? Company::first()?->id)
                            ->searchable()
                            ->preload()
                            ->required(fn (callable $get) => in_array($get('target_tool'), ['create_saudi_employee', 'create_foreign_employee']))
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
        $formData = $this->form->getState();
        $filePath = $formData['uploaded_file'] ?? null;
        $targetTool = $formData['target_tool'] ?? 'all';
        $companyId = $formData['company_id'] ?? $this->selectedCompanyId ?? null;

        if (!$filePath) {
            Notification::make()
                ->title(__('smart_import.notifications.select_file'))
                ->warning()
                ->send();
            return;
        }

        if (!empty($companyId)) {
            $this->selectedCompanyId = (int) $companyId;
        }

        $this->isProcessing = true;
        $this->processingReport = null;
        $this->pendingAnalysis = null;

        $processor = new GeminiProcessorService();

        // If smart auto-detection is selected, analyze file first and ask for user confirmation
        if ($targetTool === 'all') {
            $analysis = $processor->analyzeFile($filePath, 'public', null);
            $this->isProcessing = false;

            if (empty($analysis['success'])) {
                Notification::make()
                    ->title(__('smart_import.notifications.error_title'))
                    ->body($analysis['message'] ?? __('smart_import.notifications.generic_error'))
                    ->danger()
                    ->duration(8000)
                    ->send();
                return;
            }

            // Save analysis result to display to user for confirmation or re-routing
            $this->pendingAnalysis = $analysis;
            $this->alternativeTool = $analysis['first_tool'] ?? 'create_saudi_employee';
            $this->selectedCompanyId = $companyId ?? $this->selectedCompanyId ?? auth()->user()?->company_id ?? Company::first()?->id;

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

        // If the user selected a specific target tool from the start
        $allowedTools = [$targetTool];
        $extraContext = [];
        if (!empty($this->selectedCompanyId)) {
            $extraContext['company_id'] = (int) $this->selectedCompanyId;
        }

        $result = $processor->processFile($filePath, 'public', $allowedTools, $extraContext);

        $this->isProcessing = false;
        $this->processingReport = $result;

        $this->sendExecutionNotification($result);
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
        $processor = new GeminiProcessorService();
        $extraContext = [];
        if (!empty($this->selectedCompanyId)) {
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

        $this->isConfirming = true;
        $processor = new GeminiProcessorService();

        if ($chosenTool === 'all') {
            $analysis = $processor->analyzeFile($filePath, 'public', null);
            $this->isConfirming = false;

            if (empty($analysis['success'])) {
                Notification::make()
                    ->title(__('smart_import.notifications.error_title'))
                    ->body($analysis['message'] ?? __('smart_import.notifications.generic_error'))
                    ->danger()
                    ->send();
                return;
            }

            $this->pendingAnalysis = $analysis;
            $this->alternativeTool = $analysis['first_tool'] ?? 'create_saudi_employee';
            return;
        }

        // Reprocess file and insert directly into chosen destination
        $extraContext = [];
        if (!empty($this->selectedCompanyId)) {
            $extraContext['company_id'] = (int) $this->selectedCompanyId;
        }

        $result = $processor->processFile($filePath, 'public', [$chosenTool], $extraContext);

        $this->isConfirming = false;
        $this->pendingAnalysis = null;
        $this->processingReport = $result;

        $this->sendExecutionNotification($result);
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
        if (!empty($result['success'])) {
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
}
