<?php

namespace App\Providers;

use App\Enums\UserType;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
      


        $this->app->singleton(
            \App\Interfaces\SmsServiceInterface::class,
            \App\Services\Notifications\SmsService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function run(): void
    {
        //
    }

    public function boot(): void
    {
        // Super Admin and Owners have full unrestricted access to everything
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'isSuperAdminOrOwner') && $user->isSuperAdminOrOwner()) {
                return true;
            }

            return null;
        });

        // Register Policies
        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
        Gate::policy(\App\Models\Company::class, \App\Policies\CompanyPolicy::class);
        Gate::policy(\App\Models\Branch::class, \App\Policies\BranchPolicy::class);
        Gate::policy(\App\Models\Department::class, \App\Policies\DepartmentPolicy::class);
        Gate::policy(\App\Models\JobTitle::class, \App\Policies\JobTitlePolicy::class);
        Gate::policy(\App\Models\Owner::class, \App\Policies\OwnerPolicy::class);
        Gate::policy(\App\Models\SaudiEmployee::class, \App\Policies\SaudiEmployeePolicy::class);
        Gate::policy(\App\Models\ForeignEmployee::class, \App\Policies\ForeignEmployeePolicy::class);
        Gate::policy(\App\Models\Attendance::class, \App\Policies\AttendancePolicy::class);
        Gate::policy(\App\Models\Leave::class, \App\Policies\LeavePolicy::class);
        Gate::policy(\App\Models\LeaveType::class, \App\Policies\LeaveTypePolicy::class);
        Gate::policy(\App\Models\Customer::class, \App\Policies\CustomerPolicy::class);
        Gate::policy(\App\Models\Service::class, \App\Policies\ServicePolicy::class);
        Gate::policy(\App\Models\Invoice::class, \App\Policies\InvoicePolicy::class);
        Gate::policy(\App\Models\InvoiceType::class, \App\Policies\InvoiceTypePolicy::class);
        Gate::policy(\App\Models\Offer::class, \App\Policies\OfferPolicy::class);
        Gate::policy(\App\Models\Task::class, \App\Policies\TaskPolicy::class);
        Gate::policy(\App\Models\TaskAssignment::class, \App\Policies\TaskAssignmentPolicy::class);
        Gate::policy(\App\Models\WeeklyNote::class, \App\Policies\WeeklyNotePolicy::class);
        Gate::policy(\App\Models\Setting::class, \App\Policies\SettingPolicy::class);

        // Finance & Treasury Policies
        Gate::policy(\App\Models\PaymentCollectionItem::class, \App\Policies\PaymentCollectionItemPolicy::class);
        Gate::policy(\App\Models\PosDevice::class, \App\Policies\PosDevicePolicy::class);
        Gate::policy(\App\Models\TreasuryTransaction::class, \App\Policies\TreasuryTransactionPolicy::class);
        Gate::policy(\App\Models\DailyJournal::class, \App\Policies\DailyJournalPolicy::class);
        Gate::policy(\App\Models\EmployeeReward::class, \App\Policies\EmployeeRewardPolicy::class);

        // Installments & Payroll Policies
        Gate::policy(\App\Models\Installment::class, \App\Policies\InstallmentPolicy::class);
        Gate::policy(\App\Models\InstallmentPayment::class, \App\Policies\InstallmentPaymentPolicy::class);
        Gate::policy(\App\Models\InstallmentType::class, \App\Policies\InstallmentTypePolicy::class);
        Gate::policy(\App\Models\AttendanceDevice::class, \App\Policies\AttendanceDevicePolicy::class);
        Gate::policy(\App\Models\PayrollRecord::class, \App\Policies\PayrollRecordPolicy::class);

        // FAQ Policy
        Gate::policy(\App\Models\Faq::class, \App\Policies\FaqPolicy::class);

        // Global Select Searchable Configuration for all Filament Forms and Tables
        \Filament\Forms\Components\Select::configureUsing(function (\Filament\Forms\Components\Select $select): void {
            $select->searchable()->preload();
        });

        \Filament\Tables\Filters\SelectFilter::configureUsing(function (\Filament\Tables\Filters\SelectFilter $filter): void {
            $filter->searchable()->preload();
        });

        // Global Automatic Hijri Date Hint for all DatePickers in the entire system
        \Filament\Forms\Components\DatePicker::configureUsing(function (\Filament\Forms\Components\DatePicker $datePicker): void {
            $datePicker->hint(function ($state) {
                if (blank($state)) {
                    return null;
                }
                try {
                    return '📅 هـ: ' . \App\Services\HijriDateService::toHijri($state);
                } catch (\Throwable $e) {
                    return null;
                }
            })->hintColor('primary');
        });

        // Authorize LogViewer for Super Admins and Owners
        rescue(function () {
            if (app()->bound('log-viewer') || class_exists(\Opcodes\LogViewer\LogViewerServiceProvider::class)) {
                \Opcodes\LogViewer\Facades\LogViewer::auth(function ($request) {
                    $user = $request->user();
                    return $user && ($user->isSuperAdminOrOwner() || $user->hasRole('super_admin'));
                });
            }
        }, null, false);
    }
}
