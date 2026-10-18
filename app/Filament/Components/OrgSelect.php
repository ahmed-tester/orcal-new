<?php

namespace App\Filament\Components;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class OrgSelect
{
    /**
     * Reusable Company Select form field.
     * Handles visibility, auto-defaulting, multi-tenancy requirements and cascading resets.
     *
     * @param string $name
     * @param string|null $label
     * @param array|string|callable|null $afterUpdated Field name(s) to reset on company change, or custom closure.
     * @return Select
     */
    public static function company(
        string $name = 'company_id',
        ?string $label = null,
        mixed $afterUpdated = ['branch_id']
    ): Select {
        $field = Select::make($name)
            ->label($label ?? __('organization.companies.single'))
            ->relationship('company', 'trade_name', function ($query) {
                $user = auth()->user();
                if (! $user || $user->isSuperAdmin()) {
                    return;
                }
                $accessibleIds = \App\Services\CompanyFormService::getAuthorizedCompanyIds($user);
                $query->whereIn('id', ! empty($accessibleIds) ? $accessibleIds : [0]);
            })
            ->searchable()
            ->preload()
            ->default(fn () => static::getDefaultCompanyId())
            ->disabled(fn () => ! (auth()->user()?->hasMultipleCompanies() ?? false))
            ->required()
            ->dehydrated()
            ->reactive();

        if (is_callable($afterUpdated)) {
            $field->afterStateUpdated($afterUpdated);
        } elseif (!empty($afterUpdated)) {
            $fieldsToReset = (array) $afterUpdated;
            $field->afterStateUpdated(function (callable $set) use ($fieldsToReset) {
                foreach ($fieldsToReset as $f) {
                    $set($f, null);
                }
            });
        }

        return $field;
    }

    /**
     * Reusable Branch Select form field.
     * Filters branches dynamically according to the selected company or logged-in user's company.
     * Accessible & selectable by managers / super admins / owners, locked for single-branch employees.
     *
     * @param string $name
     * @param string|null $label
     * @param string $companyField
     * @param bool $required
     * @return Select
     */
    public static function branch(
        string $name = 'branch_id',
        ?string $label = null,
        string $companyField = 'company_id',
        bool $required = false
    ): Select {
        $field = Select::make($name)
            ->label($label ?? __('organization.branches.single'))
            ->options(fn (callable $get) => static::getCompanyBranchOptions($get, $companyField))
            ->searchable()
            ->preload()
            ->default(fn (callable $get) => static::getDefaultBranchId($get, $companyField))
            ->disabled(fn () => ! (auth()->user()?->canAccessAllBranches() ?? false))
            ->dehydrated();

        if ($required) {
            $field->required();
        }

        return $field;
    }

    /**
     * Resolve default branch ID for current user and selected company.
     */
    public static function getDefaultBranchId(callable $get, string $companyField = 'company_id'): ?int
    {
        $user = auth()->user();
        $companyId = $get($companyField) ?? $user?->company_id ?? $user?->employee?->company_id ?? static::getDefaultCompanyId();

        // If user is restricted to a branch, verify it belongs to this company
        if ($user && ! $user->canAccessAllBranches() && $user->branch_id) {
            $userBranch = Branch::where('company_id', $companyId)->where('id', $user->branch_id)->first();
            if ($userBranch) {
                return $userBranch->id;
            }
        }

        return Branch::where('company_id', $companyId)->first()?->id;
    }

    /**
     * Build options array [id => "Branch Name"] for company branches.
     */
    public static function getCompanyBranchOptions(callable $get, string $companyField = 'company_id'): array
    {
        $user = auth()->user();
        $companyId = $get($companyField) ?? $user?->company_id ?? $user?->employee?->company_id ?? static::getDefaultCompanyId();

        $query = Branch::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($user && ! $user->canAccessAllBranches() && $user->branch_id) {
            $query->where('id', $user->branch_id);
        }

        return $query->pluck('name', 'id')->toArray();
    }

    /**
     * Reusable Employee Select form field.
     * Scoped to selected company, supports selecting by ID or Name, with auto-filling phone, identity, etc.
     */
    public static function employee(
        string $name = 'employee_id',
        ?string $label = null,
        string $companyField = 'company_id',
        string $valueKey = 'id',
        ?string $autoFillPhone = null,
        ?string $autoFillIdentity = null,
        ?string $autoFillEmail = null,
        ?string $autoFillName = null,
        mixed $customAfterUpdated = null,
        bool $required = true
    ): Select {
        $field = Select::make($name)
            ->label($label ?? __('employees.single'))
            ->searchable()
            ->preload()
            ->reactive()
            ->options(fn ($get) => static::getCompanyEmployeeOptions($get, $companyField, $valueKey));

        if ($valueKey === 'id') {
            $field->default(fn () => auth()->user()?->employee?->id);
        }

        // Configure Reactive Auto-fill on Selection Change
        $field->afterStateUpdated(function ($state, callable $set, callable $get) use ($valueKey, $companyField, $autoFillPhone, $autoFillIdentity, $autoFillEmail, $autoFillName, $customAfterUpdated) {
            $companyId = $get($companyField) ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id ?? static::getDefaultCompanyId();
            $employee = static::resolveEmployee($state, $valueKey, $companyId);

            static::autoFillEmployeeData($employee, $set, $autoFillPhone, $autoFillIdentity, $autoFillEmail, $autoFillName);

            if (is_callable($customAfterUpdated)) {
                $customAfterUpdated($state, $set, $get);
            }
        });

        if ($required) {
            $field->required();
        }

        return $field;
    }

    /**
     * Build options array [key => "Name (EMP-1001)"] for company employees.
     */
    protected static function getCompanyEmployeeOptions(callable $get, string $companyField, string $valueKey = 'id'): array
    {
        $companyId = $get($companyField) ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id ?? static::getDefaultCompanyId();
        $query = Employee::with(['user', 'detail']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get()->mapWithKeys(function ($emp) use ($valueKey) {
            $val = $valueKey === 'id' ? $emp->id : ($emp->user?->name ?? (string)$emp->id);
            $lbl = $emp->user?->name ?? "موظف #{$emp->id}";
            return [$val => $lbl];
        })->toArray();
    }

    /**
     * Resolve Employee Eloquent model by ID or Name.
     */
    protected static function resolveEmployee(mixed $state, string $valueKey, ?int $companyId): ?Employee
    {
        if (blank($state)) {
            return null;
        }

        if ($valueKey === 'id') {
            return Employee::with(['user', 'detail'])->find($state);
        }

        return Employee::with(['user', 'detail'])
            ->whereHas('user', fn ($q) => $q->where('name', $state))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->first() ?? Employee::with(['user', 'detail'])->where('employee_number', $state)->first();
    }

    /**
     * Apply reactive auto-fill fields (Phone, National ID/Iqama, Email, Name) from resolved Employee model.
     */
    protected static function autoFillEmployeeData(
        ?Employee $emp,
        callable $set,
        ?string $phoneField = null,
        ?string $idField = null,
        ?string $emailField = null,
        ?string $nameField = null
    ): void {
        if ($phoneField) {
            $set($phoneField, $emp?->user?->phone);
        }

        if ($idField) {
            $identity = $emp ? ($emp->detail?->national_id ?? $emp->detail?->iqama_number ?? $emp->employee_number) : null;
            $set($idField, $identity);
        }

        if ($emailField) {
            $set($emailField, $emp?->user?->email);
        }

        if ($nameField) {
            $set($nameField, $emp?->user?->name);
        }
    }

    /**
     * Reusable Table Column for Company.
     */
    public static function tableColumnCompany(string $name = 'company.trade_name', ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? __('organization.companies.single'))
            ->sortable()
            ->searchable()
            ->visible(fn () => auth()->user()?->hasMultipleCompanies() ?? false);
    }

    /**
     * Reusable Table Column for Branch.
     */
    public static function tableColumnBranch(string $name = 'branch.name', ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? __('organization.branches.single'))
            ->sortable()
            ->searchable()
            ->toggleable();
    }

    /**
     * Reusable Table Filter for Company.
     */
    public static function tableFilterCompany(string $name = 'company_id', ?string $label = null): SelectFilter
    {
        return SelectFilter::make($name)
            ->label($label ?? __('organization.companies.single'))
            ->relationship('company', 'trade_name', function ($query) {
                $user = auth()->user();
                if ($user && !$user->isSuperAdmin()) {
                    $companyIds = \App\Services\CompanyFormService::getAuthorizedCompanyIds($user);
                    $query->whereIn('id', !empty($companyIds) ? $companyIds : [0]);
                }
            })
            ->searchable()
            ->preload()
            ->visible(fn () => auth()->user()?->hasMultipleCompanies() ?? false);
    }

    /**
     * Reusable Table Filter for Branch.
     */
    public static function tableFilterBranch(string $name = 'branch_id', ?string $label = null): SelectFilter
    {
        return SelectFilter::make($name)
            ->label($label ?? __('organization.branches.single'))
            ->relationship('branch', 'name', function ($query) {
                $user = auth()->user();
                if ($user && !$user->isSuperAdmin()) {
                    $companyIds = \App\Services\CompanyFormService::getAuthorizedCompanyIds($user);
                    $query->whereIn('company_id', !empty($companyIds) ? $companyIds : [0]);
                    if (!$user->canAccessAllBranches() && $user->branch_id) {
                        $query->where('id', $user->branch_id);
                    }
                }
            })
            ->searchable()
            ->preload();
    }

    /**
     * Resolve default company ID based on current user's role and ownership.
     */
    public static function getDefaultCompanyId(): ?int
    {
        $user = auth()->user();
        if (! $user) {
            return Company::first()?->id;
        }

        if ($user->isSuperAdmin()) {
            return Company::first()?->id;
        }

        if ($user->isOwner()) {
            $owned = $user->getOwnedCompanyIds();
            return ! empty($owned) ? reset($owned) : Company::first()?->id;
        }

        return $user->company_id ?? $user->employee?->company_id ?? Company::first()?->id;
    }

    /**
     * Resolve default Company model with relations preloaded.
     */
    public static function getDefaultCompany(): ?Company
    {
        $id = static::getDefaultCompanyId();
        if (! $id) {
            return null;
        }

        return Company::with(['owner.user', 'owners.user'])->find($id);
    }
}
