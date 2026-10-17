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

    public function mount(): void
    {
        $this->form->fill();
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
                            ->required(),

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

        if (!$filePath) {
            Notification::make()
                ->title(__('smart_import.notifications.select_file'))
                ->warning()
                ->send();
            return;
        }

        $this->isProcessing = true;
        $this->processingReport = null;

        $allowedTools = $targetTool === 'all' ? null : [$targetTool];

        $processor = new GeminiProcessorService();
        $result = $processor->processFile($filePath, 'public', $allowedTools);

        $this->isProcessing = false;
        $this->processingReport = $result;

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
