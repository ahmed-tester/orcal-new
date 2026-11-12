<?php

namespace Tests\Feature;

use App\Filament\Resources\DebtCollections\Pages\CreateDebtCollection;
use App\Filament\Resources\Debts\Pages\DebtsSummary;
use App\Models\User;
use App\Services\DebtSummaryService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DebtSummaryTest extends TestCase
{
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

        foreach (['guarantors', 'treasury_transactions'] as $name) {
            Schema::create($name, fn (Blueprint $table) => $table->id());
        }
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->string('voucher_number')->nullable();
            $table->string('transaction_type')->nullable();
        });

        (require database_path('migrations/2026_10_13_170000_create_debts_and_debt_collections_tables.php'))->up();
        DB::table('companies')->insert([['id' => 1, 'trade_name' => 'First company'], ['id' => 2, 'trade_name' => 'Second company']]);
        $this->travelTo(now()->setDate(2026, 11, 11)->startOfDay());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_summary_groups_by_company_and_identity_and_calculates_overdue_balance(): void
    {
        $user = $this->staff('super_admin');
        $first = $this->debt(['employee_id' => $user->employee->id, 'remaining_amount' => 75, 'paid_amount' => 25, 'status' => 'partial']);
        $second = $this->debt(['employee_id' => $user->employee->id, 'due_date' => '2026-11-11']);
        $this->debt(['employee_id' => $user->employee->id, 'company_id' => 2]);

        foreach ([[' 0001 ', null], ['0001', null], ['0001', 2], ['0002', null], [null, null], ['', null]] as [$identity, $company]) {
            $this->debt(['debtor_type' => 'external', 'company_id' => $company, 'external_national_id' => $identity, 'external_name' => 'Same name']);
        }
        $this->collection($first, 'PENDING-VOUCHER', 20);
        $this->collection($first, 'OTHER-VOUCHER', 5);

        $rows = DebtSummaryService::summaryQuery([])->get();
        $this->assertCount(7, $rows);
        $this->assertSame(2, (int) $rows->firstWhere('id', $first)->debt_count);
        $this->assertSame(75.0, (float) $rows->firstWhere('id', $first)->overdue_sum);
        $this->assertSame(900.0, (float) DebtSummaryService::stats([])->total_sum);
        $this->assertSame(25.0, (float) DebtSummaryService::stats([])->paid_sum);
        $this->assertSame([$second, $first], DebtSummaryService::details($first, [])['debts']->modelKeys());
        $this->assertCount(2, DebtSummaryService::details($first, [])['debts']->last()->collections);
        $this->assertSame([$first], DebtSummaryService::details($first, ['status' => 'overdue'])['debts']->modelKeys());
        $externalRows = $rows->filter(fn ($debt) => $debt->isExternal() && $debt->company_id === null);
        $this->assertSame([1, 1, 1, 2], $externalRows->pluck('debt_count')->map(fn ($value) => (int) $value)->sort()->values()->all());
        $this->assertCount(2, DebtSummaryService::details($externalRows->firstWhere('debt_count', 2)->id, [])['debts']);
    }

    public function test_filament_and_print_use_the_same_filters_and_hide_vouchers_without_permission(): void
    {
        $user = $this->staff(permissions: ['view_any_debt']);
        $first = $this->debt(['employee_id' => $user->employee->id]);
        $this->debt(['employee_id' => $user->employee->id, 'debt_date' => '2026-10-10', 'debt_number' => 'OCTOBER-DEBT']);
        $this->collection($first, 'HIDDEN-VOUCHER');

        Livewire::test(DebtsSummary::class)
            ->set('dateFrom', '2026-11-01')
            ->call('openDetails', $first)
            ->assertDispatched('open-modal', id: 'debt-details')
            ->assertSee('DBT-1')->assertDontSee('OCTOBER-DEBT')->assertDontSee('HIDDEN-VOUCHER')
            ->call('exportCsv')->assertFileDownloaded('debts-summary-2026-11-11.csv');

        $this->get(route('debts.summary.print', ['date_from' => '2026-11-01']))
            ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && (int) $rows->first()->debt_count === 1);
        $this->get(route('debts.summary.print', ['debt' => $first, 'date_from' => '2026-11-01']))
            ->assertOk()->assertSee('DBT-1')->assertDontSee('OCTOBER-DEBT')->assertDontSee('HIDDEN-VOUCHER');
    }

    public function test_debt_links_are_scoped_to_company_branch_and_permissions(): void
    {
        $user = $this->staff(permissions: ['view_any_debt', 'view_any_debt_collection', 'create_debt_collection'], branch: 1);
        $local = $this->debt(['employee_id' => $user->employee->id]);
        $otherEmployee = User::factory()->create();
        $otherId = DB::table('employees')->insertGetId(['user_id' => $otherEmployee->id, 'company_id' => 1, 'branch_id' => 2]);
        $remote = $this->debt(['employee_id' => $otherId]);
        $otherCompany = $this->debt(['employee_id' => $user->employee->id, 'company_id' => 2]);
        $this->debt(['company_id' => null, 'debtor_type' => 'external', 'external_national_id' => 'EXTERNAL']);
        $voucher = $this->collection($remote, 'REMOTE-VOUCHER', employeeId: $otherId);

        $this->assertSame([$local], DebtSummaryService::summaryQuery([])->get()->modelKeys());
        $this->assertSame(0, DebtSummaryService::summaryQuery(['company_id' => 2])->count());
        $this->get(route('debts.summary.print', ['debt' => $remote]))->assertNotFound();
        $this->get(route('exports.debt-voucher', ['debt' => $otherCompany]))->assertNotFound();
        $this->get(route('system.print', ['type' => 'debt-collection', 'id' => $voucher]))->assertNotFound();
        Livewire::test(DebtsSummary::class)->call('openDetails', $remote)->assertNotFound();
        Livewire::withQueryParams(['debt_id' => $local])->test(CreateDebtCollection::class)->assertSet('data.debt_id', $local);
        Livewire::withQueryParams(['debt_id' => $remote])->test(CreateDebtCollection::class)->assertNotFound();

        $this->staff();
        $this->get(route('exports.debts-statement'))->assertForbidden();
        $this->get(route('debts.summary.print'))->assertForbidden();
        $this->get(route('system.print', ['type' => 'debt-collection', 'id' => $voucher]))->assertForbidden();
    }

    public function test_prefilled_collection_saves_through_the_existing_form_and_rechecks_the_selected_debt(): void
    {
        $user = $this->staff(permissions: ['view_any_debt_collection', 'create_debt_collection']);
        $local = $this->debt(['employee_id' => $user->employee->id]);
        $outside = $this->debt(['company_id' => 2]);

        Livewire::withQueryParams(['debt_id' => $local])->test(CreateDebtCollection::class)
            ->assertOk()->assertSet('data.debt_id', $local)->assertSet('data.amount', '100.00')
            ->set('data.amount', 40)->call('create')->assertHasNoErrors();

        $this->assertDatabaseHas('debt_collections', ['debt_id' => $local, 'amount' => 40, 'company_id' => 1, 'employee_id' => $user->employee->id]);
        $this->assertDatabaseHas('debts', ['id' => $local, 'paid_amount' => 40, 'remaining_amount' => 60]);

        Livewire::withQueryParams(['debt_id' => $local])->test(CreateDebtCollection::class)
            ->set('data.debt_id', $outside)->call('create')->assertHasErrors(['data.debt_id']);
        $this->assertDatabaseMissing('debt_collections', ['debt_id' => $outside]);
    }

    private function staff(string $type = 'admin', array $permissions = [], ?int $branch = null): User
    {
        $user = User::factory()->create(['user_type' => $type, 'status' => 'active']);
        DB::table('employees')->insert(['user_id' => $user->id, 'company_id' => 1, 'branch_id' => $branch]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);

        return $user;
    }

    private function debt(array $attributes): int
    {
        return DB::table('debts')->insertGetId(array_merge([
            'debt_number' => 'DBT-'.(DB::table('debts')->count() + 1),
            'company_id' => 1,
            'debt_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'total_amount' => 100,
            'remaining_amount' => 100,
        ], $attributes));
    }

    private function collection(int $debtId, string $number, int $amount = 25, ?int $employeeId = null): int
    {
        return DB::table('debt_collections')->insertGetId([
            'debt_id' => $debtId,
            'voucher_number' => $number,
            'company_id' => 1,
            'employee_id' => $employeeId,
            'collection_date' => '2026-11-05',
            'amount' => $amount,
        ]);
    }
}
