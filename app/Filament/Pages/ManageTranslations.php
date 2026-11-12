<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\File;
use Livewire\WithPagination;

class ManageTranslations extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.manage-translations';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdminOrOwner() || $user->can('manage_translations'));
    }

    public string $search = '';
    public string $selectedLocale = 'ar';
    public string $fileFilter = 'all';
    public int $perPage = 25;
    public int $currentPage = 1;

    /**
     * Edited values — only holds the CURRENT PAGE keys to keep POST payload small
     *
     * @var array<string, string>
     */
    public array $editedValues = [];

    /**
     * Pending (dirty) edits accumulated across page changes, kept in session
     *
     * @var array<string, string>
     */
    protected array $pendingEdits = [];

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.system_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('translations.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('translations.title');
    }

    public function mount(): void
    {
        $this->pendingEdits = session('translation_pending_edits', []);
        $this->syncEditedValuesToCurrentPage();
    }

    /**
     * Flush pending edits from editedValues into pendingEdits,
     * then repopulate editedValues with only the current page items.
     */
    protected function syncEditedValuesToCurrentPage(): void
    {
        // Merge any current editedValues into pendingEdits
        foreach ($this->editedValues as $key => $val) {
            $this->pendingEdits[$key] = $val;
        }

        // Build the current page slice
        $pageItems = $this->getCurrentPageItems();

        // Populate editedValues with only current-page keys
        $this->editedValues = [];
        foreach ($pageItems as $item) {
            $uk = $item['unique_key'];
            // Use pending edit if exists, otherwise original value
            $this->editedValues[$uk] = $this->pendingEdits[$uk] ?? $item['value'];
        }

        // Persist pending edits in session
        session(['translation_pending_edits' => $this->pendingEdits]);
    }

    /**
     * Return the items for the current page (after filtering + pagination).
     *
     * @return array<int, array{unique_key: string, source: string, key: string, full_key: string, value: string}>
     */
    protected function getCurrentPageItems(): array
    {
        $all = $this->getFilteredAllRaw();
        $total = count($all);
        $lastPage = max(1, (int) ceil($total / $this->perPage));

        if ($this->currentPage > $lastPage) {
            $this->currentPage = $lastPage;
        }
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }

        $offset = ($this->currentPage - 1) * $this->perPage;

        return array_slice($all, $offset, $this->perPage);
    }

    // ── Lifecycle hooks ──────────────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
        $this->syncEditedValuesToCurrentPage();
    }

    public function updatedFileFilter(): void
    {
        $this->currentPage = 1;
        $this->syncEditedValuesToCurrentPage();
    }

    public function updatedSelectedLocale(): void
    {
        $this->currentPage = 1;
        $this->fileFilter = 'all';
        // Clear pending edits when switching locale
        $this->pendingEdits = [];
        session()->forget('translation_pending_edits');
        $this->syncEditedValuesToCurrentPage();
    }

    public function updatedPerPage(): void
    {
        $this->currentPage = 1;
        $this->syncEditedValuesToCurrentPage();
    }

    // ── Data accessors ───────────────────────────────────────────────

    /**
     * Get list of unique translation files in current locale
     *
     * @return array<string>
     */
    public function getAvailableFilesProperty(): array
    {
        $files = [];
        $langPath = base_path('lang/' . $this->selectedLocale);
        if (File::isDirectory($langPath)) {
            foreach (File::files($langPath) as $file) {
                $files[] = $file->getFilename();
            }
        }
        sort($files);
        return $files;
    }

    /**
     * @return array<int, array{unique_key: string, source: string, key: string, full_key: string, value: string}>
     */
    public function getAllTranslations(): array
    {
        $results = [];
        $locale = $this->selectedLocale;

        // Load from PHP Files in lang/{locale}/
        $langPath = base_path('lang/' . $locale);
        if (File::isDirectory($langPath)) {
            $files = File::files($langPath);
            foreach ($files as $file) {
                $fileName = $file->getFilename();
                $baseName = $file->getBasename('.php');

                try {
                    $translations = include $file->getPathname();
                    if (is_array($translations)) {
                        $flattened = $this->flattenArray($translations, $baseName);
                        foreach ($flattened as $k => $v) {
                            if (is_string($v) || is_numeric($v)) {
                                $uniqueKey = md5($fileName . ':::' . $k);
                                $results[] = [
                                    'unique_key' => $uniqueKey,
                                    'source' => $fileName,
                                    'key' => $k,
                                    'full_key' => 'file::' . $fileName . '::' . $k,
                                    'value' => (string) $v,
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Ignore syntax errors in translation files if any
                }
            }
        }

        return $results;
    }

    /**
     * Flatten nested array into dot notation
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    // ── Filtering (raw = no side-effects) ────────────────────────────

    /**
     * Get ALL filtered items (before pagination)
     */
    protected function getFilteredAllRaw(): array
    {
        $all = $this->getAllTranslations();

        if ($this->fileFilter !== 'all') {
            $all = array_filter($all, fn($item) => $item['source'] === $this->fileFilter);
        }

        if (filled($this->search)) {
            $s = mb_strtolower(trim($this->search));
            $all = array_filter($all, function ($item) use ($s) {
                $val = mb_strtolower($this->pendingEdits[$item['unique_key']] ?? $this->editedValues[$item['unique_key']] ?? $item['value']);
                $key = mb_strtolower($item['key']);
                return str_contains($val, $s) || str_contains($key, $s) || str_contains(mb_strtolower($item['source']), $s);
            });
        }

        return array_values($all);
    }

    // ── Computed properties for the view ──────────────────────────────

    /**
     * Get statistics for current locale
     *
     * @return array{total: int, files_count: int}
     */
    public function getStatsProperty(): array
    {
        $all = $this->getAllTranslations();
        return [
            'total' => count($all),
            'files_count' => count($this->availableFiles),
        ];
    }

    /**
     * Get paginated items for view
     */
    public function getFilteredTranslationsProperty()
    {
        return $this->getCurrentPageItems();
    }

    /**
     * Get pagination metadata for view
     */
    public function getPaginationDataProperty(): array
    {
        $all = $this->getFilteredAllRaw();
        $total = count($all);
        $lastPage = max(1, (int) ceil($total / $this->perPage));
        $page = min($this->currentPage, $lastPage);
        $from = $total > 0 ? (($page - 1) * $this->perPage) + 1 : 0;
        $to = min($page * $this->perPage, $total);

        return [
            'total' => $total,
            'lastPage' => $lastPage,
            'currentPage' => $page,
            'from' => $from,
            'to' => $to,
            'perPage' => $this->perPage,
        ];
    }

    // ── Pagination actions ───────────────────────────────────────────

    public function goToPage(int $page): void
    {
        $this->currentPage = $page;
        $this->syncEditedValuesToCurrentPage();
    }

    public function previousPage(): void
    {
        $this->currentPage = max(1, $this->currentPage - 1);
        $this->syncEditedValuesToCurrentPage();
    }

    public function nextPage(): void
    {
        $data = $this->paginationData;
        $this->currentPage = min($data['lastPage'], $this->currentPage + 1);
        $this->syncEditedValuesToCurrentPage();
    }

    // ── Save ─────────────────────────────────────────────────────────

    /**
     * Save all edited translations to PHP Files
     */
    public function saveAll(): void
    {
        try {
            // Flush current page edits into pendingEdits
            foreach ($this->editedValues as $key => $val) {
                $this->pendingEdits[$key] = $val;
            }

            $locale = $this->selectedLocale;
            $all = $this->getAllTranslations();

            // Identify which keys were actually changed
            $changedItems = [];
            $modifiedFiles = [];
            foreach ($all as $item) {
                $uk = $item['unique_key'];
                if (isset($this->pendingEdits[$uk]) && (string) $this->pendingEdits[$uk] !== (string) $item['value']) {
                    $changedItems[$uk] = (string) $this->pendingEdits[$uk];
                    $modifiedFiles[$item['source']] = true;
                }
            }

            if (empty($changedItems)) {
                Notification::make()
                    ->title(__('translations.no_changes_to_save'))
                    ->info()
                    ->send();
                return;
            }

            // Group file updates only for modified files
            $filesData = [];

            foreach ($all as $item) {
                $source = $item['source'];
                if (!isset($modifiedFiles[$source])) {
                    continue;
                }

                $uniqueKey = $item['unique_key'];
                $currentValue = $changedItems[$uniqueKey] ?? $item['value'];
                $baseName = pathinfo($source, PATHINFO_FILENAME);
                $keyWithoutFile = preg_replace('/^' . preg_quote($baseName, '/') . '\./', '', $item['key']);

                if (!isset($filesData[$source])) {
                    $filePath = base_path('lang/' . $locale . '/' . $source);
                    $filesData[$source] = File::exists($filePath) ? (include $filePath) : [];
                    if (!is_array($filesData[$source])) {
                        $filesData[$source] = [];
                    }
                }

                data_set($filesData[$source], $keyWithoutFile, $currentValue);
            }

            // Write back to PHP files
            $failedFiles = [];
            foreach ($filesData as $source => $data) {
                $filePath = base_path('lang/' . $locale . '/' . $source);
                $content = "<?php\n\nreturn " . $this->varExportPretty($data) . ";\n";
                try {
                    File::put($filePath, $content);
                } catch (\Throwable $writeErr) {
                    $failedFiles[] = $source;
                }
            }

            if (count($failedFiles) > 0) {
                Notification::make()
                    ->title(__('translations.save_permission_error'))
                    ->body(__('translations.save_permission_error_body', ['files' => implode(', ', $failedFiles)]))
                    ->danger()
                    ->persistent()
                    ->send();
                return;
            }

            // Clear pending edits after successful save
            $this->pendingEdits = [];
            session()->forget('translation_pending_edits');
            $this->syncEditedValuesToCurrentPage();

            Notification::make()
                ->title(__('translations.save_success'))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('translations.save_error'))
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Pretty print array as PHP code
     */
    protected function varExportPretty(array $expression): string
    {
        $export = var_export($expression, true);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(["["] + $array));
        return $export;
    }
}
