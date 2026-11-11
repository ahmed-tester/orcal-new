<?php

namespace Tests\Feature;

use App\Enums\AnnualLeaveType;
use App\Enums\LeaveStatus;
use App\Filament\Resources\Leaves\Pages\LeavesSummary;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use App\Services\LeaveSummaryService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LeaveSummaryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        foreach (['0001_01_01_000000_create_users_table.php', '2026_09_17_123833_create_permission_tables.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::table('employees', fn (Blueprint $table) => $table->integer('annual_leave_days')->default(30));
        foreach (['companies', 'branches', 'departments', 'job_titles'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('trade_name')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
            });
        }
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('annual_balance')->default(30);
        });
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('actual_return_date')->nullable();
            $table->integer('days');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('reason')->nullable();
            $table->string('attachment_file')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        DB::table('leave_types')->insert(['id' => 1, 'name' => AnnualLeaveType::DEDICATED_NAME]);
        DB::table('companies')->insert([['id' => 1, 'trade_name' => 'Company One'], ['id' => 2, 'trade_name' => 'Company Two']]);
        DB::table('branches')->insert([['id' => 1, 'name' => 'Branch One', 'company_id' => 1], ['id' => 2, 'name' => 'Branch Two', 'company_id' => 1]]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Carbon::setTestNow('2026-11-10 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function staff(array $permissions = ['view_any_leave', 'change_leave_status'], int $company = 1, ?int $branch = null): User
    {
        $user = User::factory()->create(['user_type' => 'admin', 'status' => 'active']);
        DB::table('employees')->insert([
            'user_id' => $user->id, 'company_id' => $company, 'branch_id' => $branch,
            'hire_date' => '2020-01-01', 'annual_leave_days' => 30,
        ]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function leave(User $employeeUser, array $attributes = []): Leave
    {
        $id = DB::table('leaves')->insertGetId(array_merge([
            'employee_id' => $employeeUser->employee->id,
            'leave_type_id' => 1, 'start_date' => '2026-11-01', 'end_date' => '2026-11-12',
            'days' => 12, 'status' => 'pending',
        ], $attributes));

        return Leave::findOrFail($id);
    }

    public function test_cross_year_days_match_summary_details_and_export(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        $this->leave($user, ['status' => 'approved', 'start_date' => '2025-12-25', 'end_date' => '2026-01-05']);
        foreach ([2025 => 7, 2026 => 5] as $year => $expected) {
            $row = LeaveSummaryService::summaryQuery(['year' => $year])->firstOrFail();
            $this->assertSame($expected, (int) $row->total_approved_days);
            $details = LeaveSummaryService::getEmployeeLeaveDetails($row->id, $year);
            $this->assertSame($expected, $details['year_stats']['year_approved_days']);
            $this->assertSame($expected, (int) LeaveSummaryService::exportRows(['year' => $year])->first()->total_approved_days);
        }
    }

    public function test_leave_spanning_the_whole_year_is_included_in_details(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        $this->leave($user, ['status' => 'approved', 'start_date' => '2025-12-31', 'end_date' => '2027-01-01', 'days' => 367]);
        $this->assertSame(365, (int) LeaveSummaryService::summaryQuery(['year' => 2026])->first()->total_approved_days);
        $this->assertSame(365, LeaveSummaryService::getEmployeeLeaveDetails($user->employee->id, 2026)['year_stats']['year_approved_days']);
    }

    public function test_current_leave_is_independent_of_year_and_stops_on_actual_return(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        $leave = $this->leave($user, ['status' => 'approved']);
        $filters = ['year' => 2024, 'leave_filter' => 'on_leave'];
        $this->assertSame(1, LeaveSummaryService::summaryQuery($filters)->paginate(10)->total());
        $this->assertSame(1, LeaveSummaryService::getStats($filters)['on_leave_today_count']);
        $this->assertNotNull(LeaveSummaryService::getEmployeeLeaveDetails($user->employee->id, 2024)['active_leave']);
        DB::table('leaves')->where('id', $leave->id)->update(['actual_return_date' => '2026-11-10']);
        $this->assertSame(0, LeaveSummaryService::summaryQuery($filters)->count());
        $this->assertNull(LeaveSummaryService::getEmployeeLeaveDetails($user->employee->id, 2024)['active_leave']);
    }

    public function test_all_years_and_page_details_use_the_selected_period(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        $this->leave($user, ['status' => 'approved', 'start_date' => '2025-12-25', 'end_date' => '2026-01-05']);
        $page = new LeavesSummary;
        $page->mount();
        $page->year = 2025;
        $page->openDetails($user->employee->id);
        $this->assertSame(2025, $page->details['year']);
        $this->assertSame(7, $page->details['year_stats']['year_approved_days']);
        $page->year = null;
        $page->updatedYear();
        $this->assertNull($page->details['year']);
        $this->assertSame(12, $page->details['year_stats']['year_approved_days']);
        $this->assertSame('all', $page->filterState()['year']);
        $this->assertSame(12, (int) LeaveSummaryService::summaryQuery($page->filterState())->first()->total_approved_days);
    }

    public function test_update_or_balance_override_does_not_grant_approval(): void
    {
        foreach (['update_leave', 'override_leave_balance'] as $permission) {
            $user = $this->staff(['view_any_leave', $permission]);
            $this->actingAs($user);
            $leave = $this->leave($user);
            foreach ([LeaveStatus::APPROVED, LeaveStatus::REJECTED] as $status) {
                try {
                    LeaveSummaryService::changeStatus($leave->id, $status, 'Test reason');
                    $this->fail('A status decision requires change_leave_status.');
                } catch (AuthorizationException) {
                    $this->assertSame(LeaveStatus::PENDING, $leave->fresh()->status);
                }
            }
        }
    }

    public function test_approval_and_rejection_cannot_cross_company_or_branch_scope(): void
    {
        $manager = $this->staff(branch: 1);
        $this->actingAs($manager);
        foreach ([$this->staff(company: 2), $this->staff(company: 1, branch: 2)] as $other) {
            $leave = $this->leave($other);
            foreach ([LeaveStatus::APPROVED, LeaveStatus::REJECTED] as $status) {
                try {
                    LeaveSummaryService::changeStatus($leave->id, $status, 'Test reason');
                    $this->fail('Out-of-scope decisions must be denied.');
                } catch (AuthorizationException) {
                    $this->assertSame(LeaveStatus::PENDING, $leave->fresh()->status);
                }
            }
        }
    }

    public function test_decisions_record_author_and_reason_and_cannot_be_repeated(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        $leave = $this->leave($user);
        LeaveSummaryService::changeStatus($leave->id, LeaveStatus::REJECTED, 'Test rejection');
        $this->assertSame(LeaveStatus::REJECTED, $leave->fresh()->status);
        $this->assertSame($user->id, $leave->fresh()->approved_by);
        $this->assertNotNull($leave->fresh()->approved_at);
        $this->assertSame('Test rejection', $leave->fresh()->rejection_reason);
        $this->expectException(ValidationException::class);
        LeaveSummaryService::changeStatus($leave->id, LeaveStatus::APPROVED);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        $leave = $this->leave($user);
        try {
            LeaveSummaryService::changeStatus($leave->id, LeaveStatus::REJECTED, '  ');
            $this->fail('Rejection must include a reason.');
        } catch (ValidationException) {
            $this->assertSame(LeaveStatus::PENDING, $leave->fresh()->status);
        }
    }

    public function test_approval_checks_balance_and_allows_only_authorized_override(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        DB::table('employees')->where('id', $user->employee->id)->update(['hire_date' => '2026-11-10']);
        $leave = $this->leave($user);
        try {
            LeaveSummaryService::changeStatus($leave->id, LeaveStatus::APPROVED);
            $this->fail('Insufficient balance must block approval.');
        } catch (ValidationException) {
            $this->assertSame(LeaveStatus::PENDING, $leave->fresh()->status);
        }
        $user->givePermissionTo(Permission::findOrCreate('override_leave_balance', 'web'));
        LeaveSummaryService::changeStatus($leave->id, LeaveStatus::APPROVED);
        $this->assertSame(LeaveStatus::APPROVED, $leave->fresh()->status);
        $this->assertSame($user->id, $leave->fresh()->approved_by);
    }

    public function test_summary_and_statement_print_support_all_years(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        $this->leave($user, ['status' => 'approved']);
        $this->get(route('leaves.summary.print', ['year' => 'all']))->assertOk()->assertViewHas('year', null);
        $this->get(route('leaves.statement.print', ['employee' => $user->employee->id, 'year' => 'all']))
            ->assertOk()->assertViewHas('year', null);
        $this->get(route('leaves.summary.print', ['year' => 2026]))->assertOk()->assertViewHas('year', 2026);
    }

    public function test_livewire_filters_details_and_rejection_flow(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->leave($user, ['status' => 'approved', 'start_date' => '2025-12-25', 'end_date' => '2026-01-05']);
        $pending = $this->leave($user);
        $page = Livewire::test(LeavesSummary::class)
            ->assertOk()
            ->set('year', 2025)
            ->call('openDetails', $user->employee->id)
            ->assertSet('details.year', 2025)
            ->assertSet('details.year_stats.year_approved_days', 7)
            ->set('year', null)
            ->assertSet('details.year', null)
            ->assertSet('details.year_stats.year_approved_days', 12)
            ->call('startRejection', $pending->id)
            ->call('rejectLeave', $pending->id)
            ->assertHasErrors(['rejectionReason' => 'required'])
            ->set('rejectionReason', 'Test reason')
            ->call('rejectLeave', $pending->id)
            ->assertHasNoErrors()
            ->assertSet('rejectingLeaveId', null);
        $this->assertSame(LeaveStatus::REJECTED, $pending->fresh()->status);
        $page->call('closeDetails')->set('year', 2024)->call('openDetails', $user->employee->id)
            ->assertSet('details.year', 2024);
    }

    public function test_owner_remains_restricted_to_owned_companies(): void
    {
        $owner = $this->staff();
        $owner->update(['user_type' => 'owner']);
        DB::table('owners')->insert(['user_id' => $owner->id, 'company_id' => 1]);
        $other = $this->staff(company: 2);
        $leave = $this->leave($other);
        $this->actingAs($owner);
        $this->expectException(AuthorizationException::class);
        LeaveSummaryService::changeStatus($leave->id, LeaveStatus::APPROVED);
    }

    public function test_print_cannot_access_an_employee_outside_the_company(): void
    {
        $user = $this->staff();
        $other = $this->staff(company: 2);
        $this->actingAs($user);
        $this->get(route('leaves.statement.print', ['employee' => $other->employee->id]))->assertForbidden();
        $this->assertFalse(LeaveSummaryService::summaryQuery()->whereKey($other->employee->id)->exists());
    }

    public function test_empty_year_url_preserves_all_years_and_invalid_year_is_rejected(): void
    {
        $user = $this->staff();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::withQueryParams(['year' => ''])->test(LeavesSummary::class)->assertOk()->assertSet('year', null);
        $this->get(route('leaves.summary.print').'?year=')->assertOk()->assertViewHas('year', null);
        $this->getJson(route('leaves.summary.print', ['year' => 1899]))->assertUnprocessable();
        $this->getJson(route('leaves.statement.print', ['employee' => $user->employee->id, 'year' => 'invalid']))->assertUnprocessable();
    }

    public function test_it_generates_year_options(): void
    {
        $options = LeaveSummaryService::yearOptions(5);

        $this->assertCount(5, $options);
        $this->assertArrayHasKey(now()->year, $options);
        $this->assertSame((string) now()->year, $options[now()->year]);
    }

    public function test_it_computes_employee_balance_correctly(): void
    {
        Carbon::setTestNow('2026-11-10 12:00:00');
        try {
            $employee = new Employee([
                'annual_leave_days' => 30,
            ]);
            $employee->hire_date = '2024-11-10'; // exactly 2 years of service

            $balance = LeaveSummaryService::computeEmployeeBalance($employee, 15);

            $this->assertSame(30, $balance['annual_rate']);
            $this->assertSame(60, $balance['accrued_days']);
            $this->assertEquals(15, $balance['used_days']);
            $this->assertSame(45, $balance['available_days']);
            $this->assertSame(24.0, $balance['service_months']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_summary_query_builds_expected_sql(): void
    {
        $query = LeaveSummaryService::summaryQuery([
            'year' => 2026,
            'search' => 'محمد',
            'employment_status' => 'all',
            'leave_filter' => 'all',
        ]);

        $sql = strtolower($query->toSql());
        $cleanSql = str_replace(['`', '"'], '', $sql);

        $this->assertStringContainsString('from employees', $cleanSql);
        $this->assertStringContainsString('left join (select', $cleanSql);
        $this->assertStringContainsString('total_approved_days', $cleanSql);
        $this->assertStringContainsString('historical_annual_used_days', $cleanSql);
        $this->assertStringContainsString('is_on_leave_today', $cleanSql);
        $this->assertStringContainsString('pending_leaves_count', $cleanSql);
    }

    public function test_employee_leaves_relationship_is_defined(): void
    {
        $employee = new Employee;
        $relation = $employee->leaves();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Leave::class, $relation->getRelated());
    }
}
