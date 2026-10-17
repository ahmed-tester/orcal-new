<?php

namespace App\Models;

use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'commercial_registration_issue_date',
            'commercial_registration_expiry',
            'municipality_license_issue_date',
            'municipality_license_expiry',
            'civil_defense_license_issue_date',
            'civil_defense_license_expiry',
        ];
    }

    protected $fillable = [
        'company_number',
        'company_type',
        'parent_id',
        'trade_name',
        'trade_name_en',
        'logo',
        'address',
        'address_en',
        'postal_code',
        'phone',
        'email',
        'additional_info',
        'commercial_registration_number',
        'commercial_registration_issue_date',
        'commercial_registration_expiry',
        'municipality_license_number',
        'municipality_license_issue_date',
        'municipality_license_expiry',
        'civil_defense_license_number',
        'civil_defense_license_issue_date',
        'civil_defense_license_expiry',
        'interior_ministry_number',
        'labor_ministry_number',
        'social_insurance_number',
        'zakat_number',
        'chamber_of_commerce_number',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'commercial_registration_issue_date' => 'date',
            'commercial_registration_expiry' => 'date',
            'municipality_license_issue_date' => 'date',
            'municipality_license_expiry' => 'date',
            'civil_defense_license_issue_date' => 'date',
            'civil_defense_license_expiry' => 'date',
            'status' => \App\Enums\GeneralStatus::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            if (empty($company->company_number)) {
                $company->company_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'company_number',
                    prefix: 'COM',
                );
            }

            foreach ($company->getAttributes() as $key => $value) {
                if ($value === '') {
                    $company->{$key} = null;
                }
            }
        });

        static::updating(function (Company $company) {
            foreach ($company->getAttributes() as $key => $value) {
                if ($value === '') {
                    $company->{$key} = null;
                }
            }
        });

        static::saved(function (Company $company) {
            if ($company->parent_id) {
                \App\Models\Branch::updateOrCreate(
                    [
                        'company_id' => $company->parent_id,
                        'name' => $company->trade_name,
                    ],
                    [
                        'address' => $company->address,
                        'phone' => $company->phone,
                        'email' => $company->email,
                        'status' => $company->status ?? 'active',
                    ]
                );
            }
        });
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function subCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    public function owner(): HasOne
    {
        return $this->hasOne(Owner::class);
    }

    public function owners(): HasMany
    {
        return $this->hasMany(Owner::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CompanyAttachment::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function jobTitles(): HasMany
    {
        return $this->hasMany(JobTitle::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function weeklyNotes(): HasMany
    {
        return $this->hasMany(WeeklyNote::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    public function violationTransactions(): HasMany
    {
        return $this->hasMany(ViolationTransaction::class);
    }
}
