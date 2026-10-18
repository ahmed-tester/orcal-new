<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar_url',
        'password',
        'user_type',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => \App\Enums\UserType::class,
        ];
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'active';
    }

    public function owner()
    {
        return $this->hasOne(Owner::class);
    }

    public function owners()
    {
        return $this->hasMany(Owner::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function getBranchIdAttribute(): ?int
    {
        return $this->employee?->branch_id;
    }

    public function getCompanyIdAttribute(): ?int
    {
        if ($this->isOwner()) {
            return $this->owners()->value('company_id');
        }

        return $this->employee?->company_id;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin')
            || $this->user_type === \App\Enums\UserType::SUPER_ADMIN;
    }

    public function isOwner(): bool
    {
        return $this->user_type === \App\Enums\UserType::OWNER
            || $this->owners()->exists();
    }

    public function getOwnedCompanyIds(): array
    {
        return $this->owners()->pluck('company_id')->filter()->unique()->toArray();
    }

    public function isSuperAdminOrOwner(): bool
    {
        return $this->isSuperAdmin() || $this->isOwner();
    }

    public function getAccessibleCompanyIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Company::pluck('id')->toArray();
        }

        if ($this->isOwner()) {
            return $this->getOwnedCompanyIds();
        }

        $accessibleIds = [];

        // المنشأة الأساسية المسجل عليها الموظف
        if ($this->company_id) {
            $accessibleIds[] = (int) $this->company_id;
        }

        // دمج منشآت القسم العام/المشترك التابع له الموظف
        if ($this->employee?->department) {
            $departmentCompanyIds = $this->employee->department->getAssignedCompanyIds();
            if (!empty($departmentCompanyIds)) {
                $accessibleIds = array_merge($accessibleIds, array_map('intval', $departmentCompanyIds));
            }
        }

        return array_values(array_unique(array_filter($accessibleIds)));
    }

    public function hasMultipleCompanies(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isOwner()) {
            return count($this->getOwnedCompanyIds()) > 1;
        }

        return count($this->getAccessibleCompanyIds()) > 1;
    }

    public function canAccessAllBranches(): bool
    {
        return $this->isSuperAdminOrOwner()
            || $this->can('view_any_branch')
            || $this->branch_id === null;
    }

    public function canAccessAllCompanies(): bool
    {
        if ($this->isSuperAdmin() || $this->isOwner()) {
            return true;
        }

        if ($this->employee?->department?->isGlobal()) {
            $totalCount = Company::count();
            if ($totalCount === 0) {
                return false;
            }

            $accessibleCount = count($this->getAccessibleCompanyIds());
            return $accessibleCount >= $totalCount;
        }

        return false;
    }
}
