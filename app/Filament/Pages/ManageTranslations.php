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
     * Associative array of edited values (keyed by unique item key)
     *
     * @var array<string, string>
     */
    public array $editedValues = [];

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
        $this->loadTranslations();
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    public function updatedFileFilter(): void
    {
        $this->currentPage = 1;
    }

    public function updatedSelectedLocale(): void
    {
        $this->currentPage = 1;
        $this->fileFilter = 'all';
        $this->loadTranslations();
    }

    protected function loadTranslations(): void
    {
        $all = $this->getAllTranslations();
        $this->editedValues = [];
        foreach ($all as $item) {
            $this->editedValues[$item['unique_key']] = $item['value'];
        }
    }

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

    /**
     * Save all edited translations to PHP Files
     */
    public function saveAll(): void
    {
        $locale = $this->selectedLocale;
        $all = $this->getAllTranslations();

        // Group file updates
        $filesData = [];

        foreach ($all as $item) {
            $uniqueKey = $item['unique_key'];
            $currentValue = $this->editedValues[$uniqueKey] ?? $item['value'];
            $source = $item['source']; // e.g. admin.php
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
        foreach ($filesData as $source => $data) {
            $filePath = base_path('lang/' . $locale . '/' . $source);
            $content = "<?php\n\nreturn " . $this->varExportPretty($data) . ";\n";
            File::put($filePath, $content);
        }

        Notification::make()
            ->title(__('translations.save_success'))
            ->success()
            ->send();
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
     * Get ALL filtered items (before pagination)
     */
    protected function getFilteredAll(): array
    {
        $all = $this->getAllTranslations();

        // Filter by specific file
        if ($this->fileFilter !== 'all') {
            $all = array_filter($all, fn($item) => $item['source'] === $this->fileFilter);
        }

        if (filled($this->search)) {
            $s = mb_strtolower(trim($this->search));
            $all = array_filter($all, function ($item) use ($s) {
                $val = mb_strtolower($this->editedValues[$item['unique_key']] ?? $item['value']);
                $key = mb_strtolower($item['key']);
                return str_contains($val, $s) || str_contains($key, $s) || str_contains(mb_strtolower($item['source']), $s);
            });
        }

        return array_values($all);
    }

    /**
     * Get paginated items for view
     */
    public function getFilteredTranslationsProperty()
    {
        $all = $this->getFilteredAll();
        $total = count($all);
        $lastPage = max(1, (int) ceil($total / $this->perPage));

        // Clamp current page
        if ($this->currentPage > $lastPage) {
            $this->currentPage = $lastPage;
        }
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }

        $offset = ($this->currentPage - 1) * $this->perPage;

        return array_slice($all, $offset, $this->perPage);
    }

    /**
     * Get pagination metadata for view
     */
    public function getPaginationDataProperty(): array
    {
        $all = $this->getFilteredAll();
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

    public function goToPage(int $page): void
    {
        $this->currentPage = $page;
    }

    public function previousPage(): void
    {
        $this->currentPage = max(1, $this->currentPage - 1);
    }

    public function nextPage(): void
    {
        $data = $this->paginationData;
        $this->currentPage = min($data['lastPage'], $this->currentPage + 1);
    }

    public function updatedPerPage(): void
    {
        $this->currentPage = 1;
    }
}
