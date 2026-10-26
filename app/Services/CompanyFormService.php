<?php

namespace App\Services;

use App\Enums\GeneralStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\InvoiceType;
use App\Models\JobTitle;
use App\Models\Service;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class CompanyFormService
{
    /**
     * Get authorized company IDs for a given user (or currently authenticated user).
     *
     * @return array<int>
     */
    public static function getAuthorizedCompanyIds(?User $user = null): array
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return [];
        }

        return $user->getAccessibleCompanyIds();
    }

    /**
     * Get default company ID for the user if single authorized company exists.
     */
    public static function getDefaultCompanyId(?User $user = null): ?int
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        $ids = static::getAuthorizedCompanyIds($user);

        return count($ids) === 1 ? (int) reset($ids) : null;
    }

    /**
     * Normalize Arabic string for robust fuzzy matching and comparison.
     */
    public static function normalizeArabicName(string $name): string
    {
        // 1. Remove diacritics / Tashkeel & Tatweel (Kashida)
        $normalized = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $name);

        // 2. Normalize hamzas, alefs, taa marbouta, yaa
        $normalized = str_replace(
            ['أ', 'إ', 'آ', 'ٱ', 'ء', 'ئ', 'ؤ'],
            ['ا', 'ا', 'ا', 'ا', '', 'ي', 'و'],
            $normalized
        );
        $normalized = str_replace('ة', 'ه', $normalized);
        $normalized = str_replace('ى', 'ي', $normalized);

        // 3. Lowercase English characters
        $normalized = mb_strtolower($normalized, 'UTF-8');

        // 4. Strip common institutional prefixes (including normalized forms)
        $prefixes = [
            'مؤسسة', 'موسسة', 'مؤسسه', 'موسسه',
            'شركة', 'شركه',
            'مكتب',
            'مجموعة', 'مجموعه',
            'فرع',
            'مصنع',
            'وكالة', 'وكاله',
            'establishment', 'est', 'company', 'co', 'corp', 'group', 'branch',
        ];
        $pattern = '/^('.implode('|', $prefixes).')\s+/u';
        $normalized = preg_replace($pattern, '', $normalized);

        // 5. Remove "ال" (definite article) at word beginnings when beneficial
        $words = preg_split('/\s+/u', trim($normalized));
        $cleanedWords = [];
        foreach ($words as $word) {
            if (mb_strlen($word) > 3 && (str_starts_with($word, 'ال') || str_starts_with($word, 'وال') || str_starts_with($word, 'فال') || str_starts_with($word, 'بال'))) {
                $word = preg_replace('/^(ال|وال|فال|بال)/u', '', $word);
            }
            if (! empty($word)) {
                $cleanedWords[] = $word;
            }
        }
        $normalized = implode(' ', $cleanedWords);

        // 6. Clean punctuation, symbols and redundant whitespace
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized);

        return trim($normalized);
    }

    /** Runtime cache of scoped company candidates keyed by scope signature. */
    protected static array $scopedCompanyCache = [];

    /** Runtime cache of normalized trade names keyed by company id. */
    protected static array $normalizedNamesCache = [];

    /**
     * Find an existing company matching the given company name using exact and fuzzy Arabic matching.
     *
     * @return array{company_id: int, ambiguous: bool}|null Array with the matched company id and
     *                                                      whether another candidate scored equally close.
     */
    public static function findMatchingCompanyId(string $companyName, ?User $user = null): ?array
    {
        $raw = trim($companyName);
        if (empty($raw)) {
            return null;
        }

        $user = $user ?? auth()->user();
        $query = Company::query();
        if ($user && ! $user->isSuperAdmin()) {
            $authorizedIds = static::getAuthorizedCompanyIds($user);
            if (! empty($authorizedIds)) {
                $query->whereIn('id', $authorizedIds);
            }
        }

        // 1. Exact match (trade_name, trade_name_en, company_number)
        $matched = (clone $query)->where(function ($q) use ($raw) {
            $q->where('trade_name', $raw)
                ->orWhere('trade_name_en', $raw)
                ->orWhere('company_number', $raw);
        })->first();

        if ($matched) {
            return ['company_id' => (int) $matched->id, 'ambiguous' => false];
        }

        // 2. Strip common Arabic prefixes & direct LIKE search
        $cleaned = preg_replace('/^(مؤسسة|شركة|مكتب|مجموعة|فرع|مصنع)\s+/u', '', $raw);
        $cleaned = trim($cleaned);
        if (! empty($cleaned) && $cleaned !== $raw) {
            $matched = (clone $query)->where(function ($q) use ($cleaned) {
                $q->where('trade_name', 'like', "%{$cleaned}%")
                    ->orWhere('trade_name_en', 'like', "%{$cleaned}%");
            })->first();

            if ($matched) {
                return ['company_id' => (int) $matched->id, 'ambiguous' => false];
            }
        }

        // 3. Advanced Fuzzy Matching (multibyte-aware Levenshtein + similarity)
        $targetNormalized = static::normalizeArabicName($raw);
        if (empty($targetNormalized)) {
            return null;
        }

        $targetLen = mb_strlen($targetNormalized);

        // Safety guard: a one-letter difference between very short names
        // (e.g. نور / نود) is far too risky to trust automatically.
        if ($targetLen < 4) {
            return null;
        }

        $candidates = [];
        foreach (static::getScopedCompanies($user) as $company) {
            $existingNormalized = static::cachedNormalizedName((int) $company->id, $company->trade_name ?? '');

            if (empty($existingNormalized)) {
                continue;
            }

            // Direct normalized equality or containment
            if ($targetNormalized === $existingNormalized
                || str_contains($targetNormalized, $existingNormalized)
                || str_contains($existingNormalized, $targetNormalized)) {
                return ['company_id' => (int) $company->id, 'ambiguous' => false];
            }

            $lev = static::mbLevenshtein($targetNormalized, $existingNormalized);
            $len = max($targetLen, mb_strlen($existingNormalized));
            $percent = round((1 - ($lev / max($len, 1))) * 100, 1);

            // Tolerance: one-letter difference for short names, up to two for longer names
            // or an equivalent similarity ratio.
            $isTolerantLev = ($len <= 8 && $lev <= 1) || ($len > 8 && $lev <= 2);

            if ($percent >= 88.0 || $isTolerantLev) {
                $candidates[] = ['company_id' => (int) $company->id, 'lev' => $lev];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn (array $a, array $b) => $a['lev'] <=> $b['lev']);
        $best = $candidates[0];

        // Ambiguity guard: another candidate is equally (or almost equally) close,
        // so the match must not be trusted silently.
        $ambiguous = count($candidates) > 1 && $candidates[1]['lev'] === $best['lev'];

        return ['company_id' => $best['company_id'], 'ambiguous' => $ambiguous];
    }

    /**
     * Multibyte-aware Levenshtein distance (native levenshtein() counts bytes, which
     * breaks Arabic matching since every Arabic character is two bytes).
     */
    public static function mbLevenshtein(string $first, string $second): int
    {
        $first = mb_str_split($first, 1, 'UTF-8');
        $second = mb_str_split($second, 1, 'UTF-8');

        $horizontal = count($first);
        $vertical = count($second);
        if ($horizontal === 0) {
            return $vertical;
        }
        if ($vertical === 0) {
            return $horizontal;
        }

        $previousRow = range(0, $horizontal);
        for ($row = 1; $row <= $vertical; $row++) {
            $currentRow = [$row];
            for ($col = 1; $col <= $horizontal; $col++) {
                $cost = ($first[$col - 1] === $second[$row - 1]) ? 0 : 1;
                $currentRow[$col] = min(
                    $previousRow[$col] + 1,
                    $currentRow[$col - 1] + 1,
                    $previousRow[$col - 1] + $cost
                );
            }
            $previousRow = $currentRow;
        }

        return (int) end($previousRow);
    }

    /**
     * Get scoped company candidates (id + trade_name) once per process,
     * instead of querying and re-normalizing for every imported row.
     *
     * @return array<int, Company>
     */
    protected static function getScopedCompanies(?User $user): array
    {
        if ($user && ! $user->isSuperAdmin()) {
            $authorizedIds = static::getAuthorizedCompanyIds($user);
            $scopeKey = 'ids:'.implode(',', ! empty($authorizedIds) ? $authorizedIds : [0]);
        } else {
            $scopeKey = 'all';
        }

        if (! isset(static::$scopedCompanyCache[$scopeKey])) {
            $query = Company::query()->select('id', 'trade_name');
            if ($scopeKey !== 'all') {
                $ids = static::getAuthorizedCompanyIds($user);
                $query->whereIn('id', ! empty($ids) ? $ids : [0]);
            }
            static::$scopedCompanyCache[$scopeKey] = $query->get()->all();
        }

        return static::$scopedCompanyCache[$scopeKey];
    }

    /**
     * Normalize a trade name once and reuse it across all imported rows.
     */
    protected static function cachedNormalizedName(int $companyId, string $name): string
    {
        if (! array_key_exists($companyId, static::$normalizedNamesCache)) {
            static::$normalizedNamesCache[$companyId] = static::normalizeArabicName($name);
        }

        return static::$normalizedNamesCache[$companyId];
    }

    /**
     * Resolve existing company, or auto-create a new company (without owner) if name is provided,
     * or return null company_id if no company is mentioned in file.
     *
     * @return array{company_id: ?int, company_name: ?string, status: string}
     */
    public static function resolveOrCreateCompanyByName(?string $companyName, ?int $fallbackCompanyId = null, ?User $user = null): array
    {
        $raw = trim($companyName ?? '');

        // If no company name is provided in the file:
        if (empty($raw)) {
            if (! empty($fallbackCompanyId)) {
                $fallback = Company::find($fallbackCompanyId);
                if ($fallback) {
                    return [
                        'company_id' => (int) $fallback->id,
                        'company_name' => $fallback->trade_name,
                        'status' => 'fallback',
                    ];
                }
            }

            // Rule: If not in file and no fallback, create employee without company!
            return [
                'company_id' => null,
                'company_name' => null,
                'status' => 'none',
            ];
        }

        // 1. Search for existing or fuzzy-matched company
        $match = static::findMatchingCompanyId($raw, $user);
        if (! empty($match['company_id'])) {
            $company = Company::find($match['company_id']);

            return [
                'company_id' => (int) $match['company_id'],
                'company_name' => $company?->trade_name ?? $raw,
                'status' => ! empty($match['ambiguous']) ? 'ambiguous' : 'matched',
            ];
        }

        // 2. Company not found -> Auto-create new company without owner
        try {
            $newCompany = Company::create([
                'trade_name' => $raw,
                'status' => GeneralStatus::ACTIVE,
            ]);

            return [
                'company_id' => (int) $newCompany->id,
                'company_name' => $newCompany->trade_name,
                'status' => 'created',
            ];
        } catch (\Throwable $e) {
            Log::error('[SmartImport] Failed to auto-create company', [
                'company_name' => $raw,
                'error' => $e->getMessage(),
            ]);

            // In case of any unexpected creation error, fallback safely to the
            // explicitly selected company (or nothing) with its real name.
            $fallback = ! empty($fallbackCompanyId) ? Company::find($fallbackCompanyId) : null;

            return [
                'company_id' => (int) $fallback?->id,
                'company_name' => $fallback?->trade_name,
                'status' => 'error',
            ];
        }
    }

    /**
     * Create a scoped, reactive Company Select component.
     */
    public static function makeCompanySelect(
        string $name = 'company_id',
        ?string $label = null,
        bool $required = true
    ): Select {
        return Select::make($name)
            ->label($label ?? __('employees.fields.company'))
            ->relationship(
                name: 'company',
                titleAttribute: 'trade_name',
                modifyQueryUsing: function (Builder $query) {
                    $user = auth()->user();
                    if ($user && ! $user->isSuperAdmin()) {
                        $companyIds = static::getAuthorizedCompanyIds($user);
                        $query->whereIn('companies.id', ! empty($companyIds) ? $companyIds : [0]);
                    }
                }
            )
            ->default(fn () => static::getDefaultCompanyId())
            ->live()
            ->afterStateUpdated(function (callable $set) {
                $set('branch_id', null);
                $set('department_id', null);
                $set('job_title_id', null);
                $set('invoice_type_id', null);
                $set('employee_id', null);
            })
            ->searchable()
            ->preload()
            ->required($required);
    }

    /**
     * Create a scoped InvoiceType Select component dependent on the selected company.
     */
    public static function makeInvoiceTypeSelect(
        string $name = 'invoice_type_id',
        ?string $label = null,
        string $companyFieldName = 'company_id',
        bool $required = false
    ): Select {
        return Select::make($name)
            ->label($label ?? __('invoices.fields.invoice_type'))
            ->options(function (callable $get) use ($companyFieldName) {
                $user = auth()->user();
                $companyId = $get($companyFieldName);

                $query = InvoiceType::query()
                    ->where('status', GeneralStatus::ACTIVE->value);

                if ($companyId) {
                    $query->where(function ($q) use ($companyId) {
                        $q->where('company_id', $companyId)
                            ->orWhereNull('company_id');
                    });
                } elseif ($user && ! $user->isSuperAdmin()) {
                    $companyIds = static::getAuthorizedCompanyIds($user);
                    $query->where(function ($q) use ($companyIds) {
                        $q->whereIn('company_id', ! empty($companyIds) ? $companyIds : [0])
                            ->orWhereNull('company_id');
                    });
                }

                return $query->pluck('name', 'id');
            })
            ->searchable()
            ->preload()
            ->required($required);
    }

    /**
     * Create a scoped Branch Select component dependent on the selected company.
     */
    public static function makeBranchSelect(
        string $name = 'branch_id',
        ?string $label = null,
        string $companyFieldName = 'company_id',
        bool $required = false
    ): Select {
        return Select::make($name)
            ->label($label ?? __('employees.fields.branch'))
            ->options(function (callable $get) use ($companyFieldName) {
                $user = auth()->user();
                $companyId = $get($companyFieldName);

                $query = Branch::query()->where('status', 'active');

                // If user is restricted to a specific branch
                if ($user && ! $user->canAccessAllBranches() && $user->branch_id) {
                    return $query->where('id', $user->branch_id)->pluck('name', 'id');
                }

                if ($companyId) {
                    $query->where('company_id', $companyId);
                } elseif ($user && ! $user->isSuperAdmin()) {
                    $companyIds = static::getAuthorizedCompanyIds($user);
                    $query->whereIn('company_id', ! empty($companyIds) ? $companyIds : [0]);
                }

                return $query->pluck('name', 'id');
            })
            ->default(function () {
                $user = auth()->user();
                if ($user && ! $user->canAccessAllBranches() && $user->branch_id) {
                    return $user->branch_id;
                }

                return null;
            })
            ->searchable()
            ->preload()
            ->required($required);
    }

    /**
     * Create a scoped Department Select component dependent on the selected company.
     */
    public static function makeDepartmentSelect(
        string $name = 'department_id',
        ?string $label = null,
        string $companyFieldName = 'company_id',
        bool $required = false
    ): Select {
        return Select::make($name)
            ->label($label ?? __('employees.fields.department'))
            ->options(function (callable $get) use ($companyFieldName) {
                $user = auth()->user();
                $companyId = $get($companyFieldName);

                $query = Department::query()->where('status', 'active');

                $query->where(function ($q) use ($companyId, $user) {
                    $q->where('is_global', true);

                    if ($companyId) {
                        $q->orWhere('company_id', $companyId)
                            ->orWhereHas('companies', fn ($sub) => $sub->where('companies.id', $companyId));
                    } elseif ($user && ! $user->isSuperAdmin()) {
                        $companyIds = static::getAuthorizedCompanyIds($user);
                        $q->orWhereIn('company_id', ! empty($companyIds) ? $companyIds : [0])
                            ->orWhereHas('companies', fn ($sub) => $sub->whereIn('companies.id', ! empty($companyIds) ? $companyIds : [0]));
                    }
                });

                return $query->pluck('name', 'id');
            })
            ->searchable()
            ->preload()
            ->required($required);
    }

    /**
     * Create a scoped JobTitle Select component dependent on the selected company.
     */
    public static function makeJobTitleSelect(
        string $name = 'job_title_id',
        ?string $label = null,
        string $companyFieldName = 'company_id',
        bool $required = false
    ): Select {
        return Select::make($name)
            ->label($label ?? __('employees.fields.job_title'))
            ->options(function (callable $get) use ($companyFieldName) {
                $user = auth()->user();
                $companyId = $get($companyFieldName);

                $query = JobTitle::query()->where('status', 'active');

                if ($companyId) {
                    $query->where('company_id', $companyId);
                } elseif ($user && ! $user->isSuperAdmin()) {
                    $companyIds = static::getAuthorizedCompanyIds($user);
                    $query->whereIn('company_id', ! empty($companyIds) ? $companyIds : [0]);
                }

                return $query->orderBy('sort_order')->pluck('name', 'id');
            })
            ->searchable()
            ->preload()
            ->required($required);
    }

    /**
     * Get available service options (company-specific + general services).
     */
    public static function getAvailableServiceOptions(mixed $companyId = null, mixed $selectedId = null): array
    {
        $user = auth()->user();
        $query = Service::query();

        $scopeCondition = function (Builder $q) use ($companyId, $user) {
            $q->where('status', GeneralStatus::ACTIVE->value);

            if ($companyId) {
                $q->where(function (Builder $c) use ($companyId) {
                    $c->where('company_id', $companyId)
                        ->orWhereNull('company_id');
                });
            } elseif ($user && ! $user->isSuperAdmin()) {
                $companyIds = static::getAuthorizedCompanyIds($user);
                $q->where(function (Builder $c) use ($companyIds) {
                    $c->whereIn('company_id', ! empty($companyIds) ? $companyIds : [0])
                        ->orWhereNull('company_id');
                });
            }
        };

        if ($selectedId) {
            $query->where(function (Builder $q) use ($selectedId, $scopeCondition) {
                $q->where('id', $selectedId)
                    ->orWhere($scopeCondition);
            });
        } else {
            $scopeCondition($query);
        }

        return $query->get()->mapWithKeys(function ($service) {
            $suffix = $service->company_id ? '' : ' (عامة)';

            return [$service->id => $service->name.$suffix];
        })->toArray();
    }

    /**
     * Create a scoped, searchable Employee Select component.
     */
    public static function makeEmployeeSelect(
        string $name = 'employee_id',
        ?string $label = null,
        bool $required = true,
        string $relationshipName = 'employee',
        ?string $companyFieldName = null
    ): Select {
        return Select::make($name)
            ->label($label ?? __('employees.single'))
            ->relationship(
                name: $relationshipName,
                titleAttribute: 'id',
                modifyQueryUsing: function (Builder $query, callable $get) use ($companyFieldName) {
                    $user = auth()->user();
                    $companyId = $companyFieldName ? $get($companyFieldName) : null;

                    $query->with('user')
                        ->where('status', 'active');

                    if ($user && method_exists($user, 'canAccessAllBranches') && ! $user->canAccessAllBranches() && $user->branch_id) {
                        $query->where('branch_id', $user->branch_id);
                    }

                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    } elseif ($user && ! $user->isSuperAdmin()) {
                        $companyIds = static::getAuthorizedCompanyIds($user);
                        $query->whereIn('company_id', ! empty($companyIds) ? $companyIds : [0]);
                    }
                },
            )
            ->getSearchResultsUsing(function (Select $component, ?string $search) use ($companyFieldName) {
                $user = auth()->user();
                $companyId = $companyFieldName ? $component->evaluate(fn (callable $get) => $get($companyFieldName)) : null;

                $query = Employee::query()
                    ->where('status', 'active')
                    ->when($user && method_exists($user, 'canAccessAllBranches') && ! $user->canAccessAllBranches() && $user->branch_id, function ($q) use ($user) {
                        $q->where('branch_id', $user->branch_id);
                    })
                    ->when($companyId, function ($query, $cid) {
                        $query->where('company_id', $cid);
                    }, function ($query) use ($user) {
                        if ($user && ! $user->isSuperAdmin()) {
                            $companyIds = static::getAuthorizedCompanyIds($user);
                            $query->whereIn('company_id', ! empty($companyIds) ? $companyIds : [0]);
                        }
                    });

                return static::employeeSearchResults($search, $query);
            })
            ->getOptionLabelFromRecordUsing(fn ($record) => ($record->user?->name ?? "موظف #{$record->id}").($record->employee_number ? " ({$record->employee_number})" : '')
            )
            ->searchable()
            ->preload()
            ->required($required);
    }

    /**
     * Search employees by name or employee number and return select options [id => label].
     * Pass a pre-scoped $query to apply the same filters as the select's relationship query.
     */
    public static function employeeSearchResults(?string $search, ?Builder $query = null): array
    {
        return ($query ?? Employee::query())
            ->with('user')
            ->where(function (Builder $q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($sub) => $sub->where('name', 'like', "%{$search}%"));
            })
            ->limit(50)
            ->get()
            ->mapWithKeys(fn ($record) => [
                $record->id => ($record->user?->name ?? "موظف #{$record->id}").($record->employee_number ? " ({$record->employee_number})" : ''),
            ])
            ->toArray();
    }
}
