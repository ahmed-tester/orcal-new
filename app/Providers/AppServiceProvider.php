<?php

namespace App\Providers;

use App\Filament\Navigation\OrderedNavigationManager;
use App\Interfaces\SmsServiceInterface;
use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DailyJournal;
use App\Models\Debt;
use App\Models\DebtCollection;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeReward;
use App\Models\Faq;
use App\Models\ForeignEmployee;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\InstallmentType;
use App\Models\Invoice;
use App\Models\InvoiceType;
use App\Models\JobTitle;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\LoanFundMemberPayment;
use App\Models\Offer;
use App\Models\OfficialLetter;
use App\Models\Owner;
use App\Models\PaymentCollectionItem;
use App\Models\PayrollRecord;
use App\Models\PosDevice;
use App\Models\SaudiEmployee;
use App\Models\Service;
use App\Models\ServiceTransaction;
use App\Models\Setting;
use App\Models\SupervisionShift;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Models\WeeklyNote;
use App\Policies\AttendanceDevicePolicy;
use App\Policies\AttendancePolicy;
use App\Policies\BranchPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DailyJournalPolicy;
use App\Policies\DebtCollectionPolicy;
use App\Policies\DebtPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\EmployeeRewardPolicy;
use App\Policies\FaqPolicy;
use App\Policies\ForeignEmployeePolicy;
use App\Policies\InstallmentPaymentPolicy;
use App\Policies\InstallmentPolicy;
use App\Policies\InstallmentTypePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\InvoiceTypePolicy;
use App\Policies\JobTitlePolicy;
use App\Policies\LeavePolicy;
use App\Policies\LeaveTypePolicy;
use App\Policies\LoanFundMemberPaymentPolicy;
use App\Policies\OfferPolicy;
use App\Policies\OfficialLetterPolicy;
use App\Policies\OwnerPolicy;
use App\Policies\PaymentCollectionItemPolicy;
use App\Policies\PayrollRecordPolicy;
use App\Policies\PosDevicePolicy;
use App\Policies\RolePolicy;
use App\Policies\SaudiEmployeePolicy;
use App\Policies\ServicePolicy;
use App\Policies\ServiceTransactionPolicy;
use App\Policies\SettingPolicy;
use App\Policies\SupervisionShiftPolicy;
use App\Policies\TaskAssignmentPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TreasuryTransactionPolicy;
use App\Policies\UserPolicy;
use App\Policies\WeeklyNotePolicy;
use App\Services\HijriDateService;
use App\Services\Notifications\SmsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Navigation\NavigationManager;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogViewerServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            SmsServiceInterface::class,
            SmsService::class
        );

        $this->app->scoped(
            NavigationManager::class,
            OrderedNavigationManager::class,
        );

        $this->app->scoped(
            NavigationManager::class,
            OrderedNavigationManager::class,
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

        // if (request()->is('*upload-file*')) {
        //     $file = request()->file('files.0') ?? request()->file('uploaded_file');
        //     logger()->info('Upload diagnostic', [
        //         'file_exists' => $file !== null,
        //         'is_valid' => $file?->isValid(),
        //         'error_code' => $file?->getError(),
        //         'error_message' => $file?->getErrorMessage(),
        //         'upload_max_filesize' => ini_get('upload_max_filesize'),
        //         'post_max_size' => ini_get('post_max_size'),
        //         'upload_tmp_dir' => ini_get('upload_tmp_dir'),
        //         'temp_dir' => sys_get_temp_dir(),
        //     ]);
        // }

        // Super Admin and Owners have full unrestricted access to everything
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'isSuperAdminOrOwner') && $user->isSuperAdminOrOwner()) {
                return true;
            }

            return null;
        });

        // Register Policies
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(JobTitle::class, JobTitlePolicy::class);
        Gate::policy(Owner::class, OwnerPolicy::class);
        Gate::policy(SaudiEmployee::class, SaudiEmployeePolicy::class);
        Gate::policy(ForeignEmployee::class, ForeignEmployeePolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(Leave::class, LeavePolicy::class);
        Gate::policy(LeaveType::class, LeaveTypePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(InvoiceType::class, InvoiceTypePolicy::class);
        Gate::policy(Offer::class, OfferPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskAssignment::class, TaskAssignmentPolicy::class);
        Gate::policy(WeeklyNote::class, WeeklyNotePolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);

        // Finance & Treasury Policies
        Gate::policy(PaymentCollectionItem::class, PaymentCollectionItemPolicy::class);
        Gate::policy(PosDevice::class, PosDevicePolicy::class);
        Gate::policy(TreasuryTransaction::class, TreasuryTransactionPolicy::class);
        Gate::policy(DailyJournal::class, DailyJournalPolicy::class);
        Gate::policy(EmployeeReward::class, EmployeeRewardPolicy::class);

        // Installments & Payroll Policies
        Gate::policy(Installment::class, InstallmentPolicy::class);
        Gate::policy(InstallmentPayment::class, InstallmentPaymentPolicy::class);
        Gate::policy(InstallmentType::class, InstallmentTypePolicy::class);
        Gate::policy(AttendanceDevice::class, AttendanceDevicePolicy::class);
        Gate::policy(PayrollRecord::class, PayrollRecordPolicy::class);

        // FAQ Policy
        Gate::policy(Faq::class, FaqPolicy::class);

        // Core Employee & Additional Module Policies
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Debt::class, DebtPolicy::class);
        Gate::policy(DebtCollection::class, DebtCollectionPolicy::class);
        Gate::policy(ServiceTransaction::class, ServiceTransactionPolicy::class);
        Gate::policy(SupervisionShift::class, SupervisionShiftPolicy::class);
        Gate::policy(OfficialLetter::class, OfficialLetterPolicy::class);
        Gate::policy(LoanFundMemberPayment::class, LoanFundMemberPaymentPolicy::class);

        // Global Select Searchable Configuration for all Filament Forms and Tables
        Select::configureUsing(function (Select $select): void {
            $select->searchable()->preload();
        });

        SelectFilter::configureUsing(function (SelectFilter $filter): void {
            $filter->searchable()->preload();
        });

        // Global Automatic Hijri Date Hint for all DatePickers in the entire system
        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $datePicker->hint(function ($state) {
                if (blank($state)) {
                    return null;
                }
                try {
                    return '📅 هـ: '.HijriDateService::toHijri($state);
                } catch (\Throwable $e) {
                    return null;
                }
            })->hintColor('primary');
        });

        // Authorize LogViewer for Super Admins and Owners
        rescue(function () {
            if (app()->bound('log-viewer') || class_exists(LogViewerServiceProvider::class)) {
                LogViewer::auth(function ($request) {
                    $user = $request->user();

                    return $user && ($user->isSuperAdminOrOwner() || $user->hasRole('super_admin'));
                });
            }
        }, null, false);
    }
}
