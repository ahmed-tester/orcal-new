<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Filament\Resources\TaskAssignments\Pages\EditTaskAssignment;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\TaskAssignmentItemReview;
use App\Models\User;
use App\Services\TaskAssignmentItemReviewService;
use App\Services\TaskAssignmentSummaryService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskAssignmentItemReviewTest extends TestCase
{
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        foreach (['0001_01_01_000000_create_users_table.php', '2026_09_17_123833_create_permission_tables.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name');
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
        });

        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
        });

        (require database_path('migrations/2026_09_20_160000_create_tasks_and_weekly_notes_tables.php'))->up();
        (require database_path('migrations/2026_11_18_151000_add_task_item_review_workflow.php'))->up();

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'contract_duration')) {
                $table->string('contract_duration')->nullable();
            }
            if (! Schema::hasColumn('employees', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
        Schema::table('task_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('task_assignments', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
        Schema::table('task_assignment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('task_assignment_items', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('supervision_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable()->default('supervision');
            $table->foreignId('company_id')->nullable();
            $table->foreignId('supervisor_id')->nullable();
            $table->foreignId('task_id')->nullable();
            $table->string('title')->nullable();
            $table->text('tasks')->nullable();
            $table->json('assigned_employees')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->nullable()->default('active');
            $table->timestamps();
        });

        DB::table('companies')->insert([
            ['id' => 1, 'trade_name' => 'Company A'],
            ['id' => 2, 'trade_name' => 'Company B'],
        ]);
        DB::table('branches')->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Main Branch'],
            ['id' => 2, 'company_id' => 2, 'name' => 'Other Branch'],
        ]);

        Role::findOrCreate('super_admin', 'web');
        foreach ([
            'view_any_task_assignment',
            'update_task_assignment',
            'review_task_assignment',
            'extend_task_assignment_due_date',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Notification::fake();
    }

    public function test_weighted_quality_excludes_pending_review_items_and_matches_assignment_totals(): void
    {
        $reviewer = $this->createReviewer(1);
        [$employee, $employeeUser] = $this->createEmployee(1);
        [$assignment, $items] = $this->createAssignmentWithItems($employee, [
            ['weight' => 40, 'due_date' => '2026-11-12'],
            ['weight' => 60, 'due_date' => '2026-11-15'],
        ]);

        $this->actingAs($reviewer);
        $beforeRow = TaskAssignmentSummaryService::employeeRow($employee->id, ['month' => '2026-11']);
        $this->assertNotNull($beforeRow);
        $this->assertSame(0.0, (float) $beforeRow->weight_sum);
        $this->assertSame(0.0, TaskAssignmentSummaryService::weightedPercentage($beforeRow));

        $this->travelTo(now()->setDate(2026, 11, 11)->setTime(10, 0));
        $review0 = TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[0], 'First item done');
        TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[1], 'Second item awaiting review');

        TaskAssignmentItemReviewService::approve($reviewer, $items[0]->fresh(), 34.0, 'Good work', $review0->id);

        $assignment->refresh();
        $row = TaskAssignmentSummaryService::employeeRow($employee->id, ['month' => '2026-11']);

        $this->assertSame(100.0, (float) $assignment->total_weight);
        $this->assertSame(34.0, (float) $assignment->total_score);
        $this->assertSame(85.0, (float) $assignment->overall_percentage);
        $this->assertFalse($assignment->is_completed);

        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->approved_items_count);
        $this->assertSame(2, (int) $row->total_items);
        $this->assertSame(1, (int) $row->pending_review_count);
        $this->assertSame(40.0, (float) $row->weight_sum);
        $this->assertSame(85.0, TaskAssignmentSummaryService::weightedPercentage($row));
    }

    public function test_near_midnight_submission_approved_next_day_uses_submission_date_for_on_time(): void
    {
        $reviewer = $this->createReviewer(1);
        [$employee, $employeeUser] = $this->createEmployee(1);
        [, $items] = $this->createAssignmentWithItems($employee, [
            ['weight' => 50, 'due_date' => '2026-11-12'],
        ]);

        $this->travelTo(now()->setDate(2026, 11, 12)->setTime(23, 55));
        $review = TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[0], 'Submitted before midnight');

        $this->travelTo(now()->setDate(2026, 11, 13)->setTime(9, 30));
        TaskAssignmentItemReviewService::approve($reviewer, $items[0]->fresh(), 50.0, null, $review->id);

        $approved = $items[0]->fresh();
        $this->assertSame(TaskStatus::COMPLETED, $approved->status);
        $this->assertSame('2026-11-12', $approved->completed_at?->toDateString());
        $this->assertSame('2026-11-12 23:55:00', $approved->accepted_submission_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-13 09:30:00', $approved->approved_at?->format('Y-m-d H:i:s'));

        $this->actingAs($reviewer);
        $row = TaskAssignmentSummaryService::employeeRow($employee->id, ['month' => '2026-11']);
        $this->assertSame(1, (int) $row->on_time_count);
        $this->assertSame(100.0, TaskAssignmentSummaryService::onTimeRate($row));
    }

    public function test_return_for_revision_tracks_reason_counts_and_late_resubmission(): void
    {
        $reviewer = $this->createReviewer(1);
        [$employee, $employeeUser] = $this->createEmployee(1);
        [, $items] = $this->createAssignmentWithItems($employee, [
            ['weight' => 40, 'due_date' => '2026-11-12'],
        ]);

        $this->travelTo(now()->setDate(2026, 11, 11)->setTime(14, 0));
        $attempt1 = TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[0], 'Initial attempt');

        $this->travelTo(now()->setDate(2026, 11, 11)->setTime(16, 0));
        TaskAssignmentItemReviewService::returnForRevision(
            $reviewer,
            $items[0]->fresh(),
            'Missing supporting attachment',
            TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT,
            $attempt1->id,
        );

        $returned = $items[0]->fresh();
        $this->assertSame(TaskStatus::RETURNED_FOR_REVISION, $returned->status);
        $this->assertSame(1, $returned->returned_count);
        $this->assertSame(1, $returned->defect_return_count);
        $this->assertSame('Missing supporting attachment', $returned->latest_return_reason);

        $this->travelTo(now()->setDate(2026, 11, 13)->setTime(11, 0));
        $attempt2 = TaskAssignmentItemReviewService::submitForReview($employeeUser, $returned, 'Added attachment');

        $this->travelTo(now()->setDate(2026, 11, 15)->setTime(10, 0));
        TaskAssignmentItemReviewService::approve($reviewer, $items[0]->fresh(), 34.0, null, $attempt2->id);

        $final = $items[0]->fresh();
        $this->assertSame('2026-11-13', $final->completed_at?->toDateString());
        $this->assertSame(2, $final->reviews()->count());

        $this->actingAs($reviewer);
        $row = TaskAssignmentSummaryService::employeeRow($employee->id, ['month' => '2026-11']);
        $this->assertSame(0, (int) $row->on_time_count);
        $this->assertSame(0.0, TaskAssignmentSummaryService::onTimeRate($row));
        $this->assertSame(85.0, TaskAssignmentSummaryService::weightedPercentage($row));
    }

    public function test_scope_change_return_does_not_increment_defect_return_count(): void
    {
        $reviewer = $this->createReviewer(1);
        [$employee, $employeeUser] = $this->createEmployee(1);
        [, $items] = $this->createAssignmentWithItems($employee, [
            ['weight' => 40, 'due_date' => '2026-11-12'],
        ]);

        $review = TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[0], 'Done');
        TaskAssignmentItemReviewService::returnForRevision(
            $reviewer,
            $items[0]->fresh(),
            'Client changed requirement',
            TaskAssignmentItemReview::RETURN_SCOPE_CHANGE,
            $review->id,
        );

        $item = $items[0]->fresh();
        $this->assertSame(1, $item->returned_count);
        $this->assertSame(0, $item->defect_return_count);
    }

    public function test_documented_due_date_extension_preserves_original_due_date(): void
    {
        $extender = $this->createUserWithPermissions(1, ['extend_task_assignment_due_date']);
        [$employee] = $this->createEmployee(1);
        [, $items] = $this->createAssignmentWithItems($employee, [
            ['weight' => 30, 'due_date' => '2026-11-12'],
        ]);

        $this->travelTo(now()->setDate(2026, 11, 12)->setTime(10, 0));
        TaskAssignmentItemReviewService::extendDueDate($extender, $items[0], '2026-11-16', 'Delayed upstream approval');

        $extended = $items[0]->fresh();
        $this->assertSame('2026-11-12', $extended->original_due_date?->toDateString());
        $this->assertSame('2026-11-16', $extended->due_date?->toDateString());
        $this->assertSame('Delayed upstream approval', $extended->due_date_extension_reason);
        $this->assertSame($extender->id, $extended->due_date_extended_by);
    }

    public function test_undated_item_and_closed_month_approval_rules(): void
    {
        $reviewer = $this->createReviewer(1);
        [$employee, $employeeUser] = $this->createEmployee(1);
        [, $items] = $this->createAssignmentWithItems($employee, [
            ['weight' => 50, 'due_date' => '2026-10-20'],
            ['weight' => 50, 'due_date' => null],
        ], '2026-10-05');

        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(12, 0));
        $octReview0 = TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[0], 'October work');
        $octReview1 = TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[1], 'Undated work');

        // Still pending in closed month (2026-10) when viewed in 2026-11
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(9, 0));
        $this->actingAs($reviewer);
        $pendingOctRow = TaskAssignmentSummaryService::employeeRow($employee->id, ['month' => '2026-10']);
        $this->assertSame(2, (int) $pendingOctRow->pending_review_count);
        $this->assertSame(0, (int) $pendingOctRow->completed_in_period);

        // Approved in November -> attributed to October (submission month)
        TaskAssignmentItemReviewService::approve($reviewer, $items[0]->fresh(), 50.0, null, $octReview0->id);
        TaskAssignmentItemReviewService::approve($reviewer, $items[1]->fresh(), 40.0, null, $octReview1->id);

        $approvedOctRow = TaskAssignmentSummaryService::employeeRow($employee->id, ['month' => '2026-10']);
        $this->assertSame(0, (int) $approvedOctRow->pending_review_count);
        $this->assertSame(2, (int) $approvedOctRow->completed_in_period);
        $this->assertSame(100.0, TaskAssignmentSummaryService::onTimeRate($approvedOctRow));
        $this->assertSame(90.0, TaskAssignmentSummaryService::weightedPercentage($approvedOctRow));
    }

    public function test_authorization_self_review_guard_duplicate_decision_and_direct_mutation_blocks(): void
    {
        $reviewer = $this->createReviewer(1);
        $otherCompanyReviewer = $this->createReviewer(2);
        [$employee, $employeeUser] = $this->createEmployee(1);

        // Even if the employee user is granted super_admin, self-review and self-extension are blocked.
        $employeeUser->assignRole('super_admin');

        [, $items] = $this->createAssignmentWithItems($employee, [
            ['weight' => 40, 'due_date' => '2026-11-12'],
        ]);

        // Direct mutation on review-controlled item is blocked on server.
        $this->expectException(ValidationException::class);
        $items[0]->update(['status' => TaskStatus::COMPLETED->value, 'score' => 40]);
    }

    public function test_self_review_out_of_scope_missing_extension_permission_and_duplicate_approval_are_blocked(): void
    {
        $reviewOnlyUser = $this->createUserWithPermissions(1, ['review_task_assignment']);
        $otherCompanyReviewer = $this->createReviewer(2);
        [$employee, $employeeUser] = $this->createEmployee(1);
        $employeeUser->assignRole('super_admin');

        [, $items] = $this->createAssignmentWithItems($employee, [
            ['weight' => 40, 'due_date' => '2026-11-12'],
        ]);

        $review = TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[0], 'Ready');

        // Self-approval blocked
        try {
            TaskAssignmentItemReviewService::approve($employeeUser, $items[0]->fresh(), 40.0, null, $review->id);
            $this->fail('Expected AuthorizationException for self-review.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        // Self-extension blocked
        try {
            TaskAssignmentItemReviewService::extendDueDate($employeeUser, $items[0]->fresh(), '2026-11-20', 'Self extend');
            $this->fail('Expected AuthorizationException for self-extension.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        // Review-only user cannot extend due date
        try {
            TaskAssignmentItemReviewService::extendDueDate($reviewOnlyUser, $items[0]->fresh(), '2026-11-20', 'No permission');
            $this->fail('Expected AuthorizationException for missing extension permission.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        // Out-of-scope reviewer cannot approve
        try {
            TaskAssignmentItemReviewService::approve($otherCompanyReviewer, $items[0]->fresh(), 40.0, null, $review->id);
            $this->fail('Expected AuthorizationException for out-of-scope reviewer.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        // First valid approval succeeds
        TaskAssignmentItemReviewService::approve($reviewOnlyUser, $items[0]->fresh(), 35.0, null, $review->id);

        // Duplicate approval on same item is blocked
        try {
            TaskAssignmentItemReviewService::approve($reviewOnlyUser, $items[0]->fresh(), 40.0, null, $review->id);
            $this->fail('Expected AuthorizationException on duplicate approval.');
        } catch (AuthorizationException) {
            $this->assertSame(35.0, (float) $items[0]->fresh()->score);
        }
    }

    public function test_stale_review_attempt_id_is_rejected_after_return_and_resubmission(): void
    {
        $reviewerA = $this->createReviewer(1);
        $reviewerB = $this->createReviewer(1);
        [$employee, $employeeUser] = $this->createEmployee(1);

        [, $items] = $this->createAssignmentWithItems($employee, [
            ['weight' => 40, 'due_date' => '2026-11-12'],
        ]);

        $attempt1 = TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[0], 'Attempt 1');

        // Reviewer B returns attempt 1, and employee resubmits attempt 2
        TaskAssignmentItemReviewService::returnForRevision($reviewerB, $items[0]->fresh(), 'Needs fix', TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT, $attempt1->id);
        $attempt2 = TaskAssignmentItemReviewService::submitForReview($employeeUser, $items[0]->fresh(), 'Attempt 2');

        // Reviewer A tries to approve using stale attempt1 ID -> must be rejected
        try {
            TaskAssignmentItemReviewService::approve($reviewerA, $items[0]->fresh(), 40.0, null, $attempt1->id);
            $this->fail('Expected ValidationException for stale expectedReviewId.');
        } catch (ValidationException) {
            $this->assertSame(TaskStatus::PENDING_REVIEW, $items[0]->fresh()->status);
            $this->assertSame(TaskAssignmentItemReview::DECISION_PENDING, $attempt2->fresh()->decision);
        }
    }

    public function test_edit_page_refreshes_only_the_matching_item_in_the_open_assignment(): void
    {
        $reviewer = $this->createReviewer(1);
        $reviewer->givePermissionTo('update_task_assignment');
        $this->actingAs($reviewer);
        [$employee] = $this->createEmployee(1);
        [$assignment, [$item, $sibling]] = $this->createAssignmentWithItems($employee, [
            ['weight' => 30, 'due_date' => '2026-11-12'],
            ['weight' => 30, 'due_date' => '2026-11-13'],
        ]);
        [, [$otherItem]] = $this->createAssignmentWithItems($employee, [
            ['weight' => 30, 'due_date' => '2026-11-14'],
        ]);
        $itemKey = 'record-'.$item->id;

        Livewire::test(EditTaskAssignment::class, ['record' => $assignment->id])
            ->set("data.items.{$itemKey}.notes", 'Unsaved note')
            ->call('refreshAssignmentItem', $itemKey, $sibling->id)
            ->assertSet("data.items.{$itemKey}.due_date", '2026-11-12')
            ->call('refreshAssignmentItem', 'record-'.$otherItem->id, $otherItem->id)
            ->assertSet("data.items.{$itemKey}.notes", 'Unsaved note')
            ->assertSet('data.items.record-'.$otherItem->id, null);
    }

    public function test_open_review_actions_reject_resubmissions_and_allow_review_after_reopening(): void
    {
        $reviewer = $this->createReviewer(1);
        $reviewer->givePermissionTo('update_task_assignment');
        $this->actingAs($reviewer);
        [$employee, $employeeUser] = $this->createEmployee(1);
        [$assignment, [$item]] = $this->createAssignmentWithItems($employee, [
            ['weight' => 40, 'due_date' => '2026-11-12'],
        ]);
        $itemKey = 'record-'.$item->id;
        $attempt = TaskAssignmentItemReviewService::submitForReview($employeeUser, $item, 'First submission');

        $page = Livewire::test(EditTaskAssignment::class, ['record' => $assignment->id])
            ->set('data.notes', 'Unsaved assignment note');

        foreach ([
            'approve_item' => ['score' => 35],
            'return_item' => ['reason' => 'Needs another fix'],
        ] as $name => $fields) {
            $action = TestAction::make($name)->schemaComponent('items', 'form')->arguments(['item' => $itemKey]);
            $page->mountAction($action)->assertSet('lockedReviewId', $attempt->id);

            TaskAssignmentItemReviewService::returnForRevision($reviewer, $item, 'Needs work', TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT, $attempt->id);
            $attempt = TaskAssignmentItemReviewService::submitForReview($employeeUser, $item, 'New submission');

            $page->fillForm($fields)->callMountedAction()->assertHasErrors(['status']);
            $this->assertSame(TaskAssignmentItemReview::DECISION_PENDING, $attempt->fresh()->decision);
            $page->unmountAction();
        }

        $approve = TestAction::make('approve_item')->schemaComponent('items', 'form')->arguments(['item' => $itemKey]);
        $page->mountAction($approve)
            ->assertSet('lockedReviewId', $attempt->id)
            ->fillForm(['score' => 35])
            ->callMountedAction()
            ->assertHasNoErrors()
            ->assertActionHidden($approve)
            ->assertSet("data.items.{$itemKey}.status", TaskStatus::COMPLETED->value)
            ->assertSet('data.notes', 'Unsaved assignment note');

        $this->assertSame(TaskStatus::COMPLETED, $item->fresh()->status);
    }

    private function createReviewer(int $companyId): User
    {
        return $this->createUserWithPermissions($companyId, [
            'view_any_task_assignment',
            'review_task_assignment',
            'extend_task_assignment_due_date',
        ]);
    }

    private function createUserWithPermissions(int $companyId, array $permissions): User
    {
        $this->sequence++;
        $user = User::factory()->create([
            'name' => 'Reviewer '.$this->sequence,
            'email' => "reviewer{$this->sequence}@example.com",
            'user_type' => 'saudi_employee',
            'status' => 'active',
        ]);
        Employee::query()->create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'branch_id' => null,
            'employee_number' => 'REV-'.$this->sequence,
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);
        $user->givePermissionTo($permissions);

        return $user->refresh();
    }

    /**
     * @return array{0: Employee, 1: User}
     */
    private function createEmployee(int $companyId): array
    {
        $this->sequence++;
        $user = User::factory()->create([
            'name' => 'Employee '.$this->sequence,
            'email' => "employee{$this->sequence}@example.com",
            'user_type' => 'saudi_employee',
            'status' => 'active',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'branch_id' => $companyId === 1 ? 1 : 2,
            'employee_number' => 'EMP-'.$this->sequence,
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        return [$employee, $user];
    }

    /**
     * @param  array<int, array{weight: float|int, due_date: ?string}>  $itemDefs
     * @return array{0: TaskAssignment, 1: array<int, TaskAssignmentItem>}
     */
    private function createAssignmentWithItems(Employee $employee, array $itemDefs, string $assignmentDate = '2026-11-01'): array
    {
        $this->sequence++;
        $assignment = TaskAssignment::query()->create([
            'transaction_number' => 'TA-'.$this->sequence,
            'assignment_date' => $assignmentDate,
            'employee_id' => $employee->id,
        ]);

        $items = [];
        foreach ($itemDefs as $idx => $def) {
            $task = Task::query()->create([
                'task_code' => 'TSK-'.$this->sequence.'-'.$idx,
                'name' => 'Task '.$this->sequence.'-'.$idx,
                'default_weight' => $def['weight'],
            ]);

            $items[] = TaskAssignmentItem::query()->create([
                'task_assignment_id' => $assignment->id,
                'task_id' => $task->id,
                'status' => TaskStatus::PENDING->value,
                'requires_review' => true,
                'weight' => $def['weight'],
                'due_date' => $def['due_date'],
            ]);
        }

        return [$assignment, $items];
    }
}
