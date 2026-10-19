<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

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

        if (!$user) {
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
        if (!$user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        $ids = static::getAuthorizedCompanyIds($user);
        return count($ids) === 1 ? (int) reset($ids) : null;
    }

    /**
     * Resolve company ID from a name string (from AI extraction) or fallback to provided/default company.
     *
     * @param string|null $companyName Name or partial name extracted by AI
     * @param int|null $fallbackCompanyId Explicitly selected company ID from UI
     * @param User|null $user
     * @return int
     */
    public static function resolveCompanyIdByName(?string $companyName, ?int $fallbackCompanyId = null, ?User $user = null): int
    {
        $user = $user ?? auth()->user();

        if (!empty($companyName)) {
            $raw = trim($companyName);

            $query = Company::query();
            if ($user && !$user->isSuperAdmin()) {
                $authorizedIds = static::getAuthorizedCompanyIds($user);
                if (!empty($authorizedIds)) {
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
                return (int) $matched->id;
            }

            // 2. Strip common Arabic prefixes (مؤسسة / شركة / مكتب / مجموعة)
            $cleaned = preg_replace('/^(مؤسسة|شركة|مكتب|مجموعة)\s+/u', '', $raw);
            $cleaned = trim($cleaned);

            if (!empty($cleaned)) {
                $matched = (clone $query)->where(function ($q) use ($cleaned) {
                    $q->where('trade_name', 'like', "%{$cleaned}%")
                      ->orWhere('trade_name_en', 'like', "%{$cleaned}%");
                })->first();

                if ($matched) {
                    return (int) $matched->id;
                }
            }

            // 3. Normalized letters matching (أ / إ / آ -> ا ، ة -> ه ، ى -> ي)
            $normalize = fn (string $s) => str_replace(['أ', 'إ', 'آ', 'ة', 'ى', 'ـ', ' ', '-'], ['ا', 'ا', 'ا', 'ه', 'ي', '', '', ''], $s);
            $targetNorm = $normalize($cleaned ?: $raw);

            if (!empty($targetNorm)) {
                $companies = (clone $query)->select('id', 'trade_name')->get();
                foreach ($companies as $c) {
                    $cNorm = $normalize($c->trade_name ?? '');
                    if (!empty($cNorm)) {
                        if (str_contains($cNorm, $targetNorm) || str_contains($targetNorm, $cNorm)) {
                            return (int) $c->id;
                        }
                    }
                }
            }
        }

        // Fallback to provided company ID (selected by admin in UI)
        if (!empty($fallbackCompanyId)) {
            return (int) $fallbackCompanyId;
        }

        // Ultimate fallback: user's default company or first company
        return (int) ($user?->company_id ?? Company::first()?->id ?? 1);
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
                    if ($user && !$user->isSuperAdmin()) {
                        $companyIds = static::getAuthorizedCompanyIds($user);
                        $query->whereIn('companies.id', !empty($companyIds) ? $companyIds : [0]);
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

                $query = \App\Models\InvoiceType::query()
                    ->where('status', \App\Enums\GeneralStatus::ACTIVE->value);

                if ($companyId) {
                    $query->where(function ($q) use ($companyId) {
                        $q->where('company_id', $companyId)
                          ->orWhereNull('company_id');
                    });
                } elseif ($user && !$user->isSuperAdmin()) {
                    $companyIds = static::getAuthorizedCompanyIds($user);
                    $query->where(function ($q) use ($companyIds) {
                        $q->whereIn('company_id', !empty($companyIds) ? $companyIds : [0])
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
                if ($user && !$user->canAccessAllBranches() && $user->branch_id) {
                    return $query->where('id', $user->branch_id)->pluck('name', 'id');
                }

                if ($companyId) {
                    $query->where('company_id', $companyId);
                } elseif ($user && !$user->isSuperAdmin()) {
                    $companyIds = static::getAuthorizedCompanyIds($user);
                    $query->whereIn('company_id', !empty($companyIds) ? $companyIds : [0]);
                }

                return $query->pluck('name', 'id');
            })
            ->default(function () {
                $user = auth()->user();
                if ($user && !$user->canAccessAllBranches() && $user->branch_id) {
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
                    } elseif ($user && !$user->isSuperAdmin()) {
                        $companyIds = static::getAuthorizedCompanyIds($user);
                        $q->orWhereIn('company_id', !empty($companyIds) ? $companyIds : [0])
                          ->orWhereHas('companies', fn ($sub) => $sub->whereIn('companies.id', !empty($companyIds) ? $companyIds : [0]));
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
                } elseif ($user && !$user->isSuperAdmin()) {
                    $companyIds = static::getAuthorizedCompanyIds($user);
                    $query->whereIn('company_id', !empty($companyIds) ? $companyIds : [0]);
                }

                return $query->orderBy('sort_order')->pluck('name', 'id');
            })
            ->searchable()
            ->preload()
            ->required($required);
    }
}
