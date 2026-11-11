<?php

namespace Tests\Feature;

use App\Filament\Resources\DailyJournals\Pages\DailyJournalsSummary;
use App\Models\User;
use App\Services\DailyJournalSummaryService;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DailyJournalSummaryTest extends TestCase
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

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
        });

        Schema::create('daily_journals', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique();
            $table->date('entry_date');
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->decimal('purchases', 14, 2)->default(0);
            $table->decimal('sales', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->foreignId('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_detailed')->default(false);
            $table->decimal('sales_cash', 14, 2)->nullable();
            $table->decimal('sales_network', 14, 2)->nullable();
            $table->decimal('sales_deferred', 14, 2)->nullable();
            $table->decimal('sales_other', 14, 2)->nullable();
            $table->decimal('purchases_cash', 14, 2)->nullable();
            $table->decimal('purchases_network', 14, 2)->nullable();
            $table->decimal('purchases_credit', 14, 2)->nullable();
            $table->decimal('purchases_debit', 14, 2)->nullable();
            $table->decimal('expenses_other', 14, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        DB::table('companies')->insert([['id' => 1, 'trade_name' => 'First company'], ['id' => 2, 'trade_name' => 'Second company']]);
        DB::table('branches')->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Branch One'],
            ['id' => 2, 'company_id' => 1, 'name' => 'Branch Two'],
            ['id' => 3, 'company_id' => 2, 'name' => 'Branch Three'],
        ]);
        $this->travelTo(now()->setDate(2026, 11, 11)->startOfDay());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_summary_groups_by_branch_and_aggregates_sales_purchases_and_net(): void
    {
        $this->staff('super_admin');
        $first = $this->journal(['branch_id' => 1, 'sales' => 100, 'purchases' => 40, 'net_amount' => 60, 'entry_date' => '2026-11-01', 'is_detailed' => true]);
        $newer = $this->journal(['branch_id' => 1, 'sales' => 50, 'purchases' => 30, 'net_amount' => 20, 'entry_date' => '2026-11-05']);
        $second = $this->journal(['branch_id' => 2, 'sales' => 200, 'purchases' => 250, 'net_amount' => -50, 'entry_date' => '2026-11-03']);
        $this->journal(['branch_id' => null, 'sales' => 10, 'purchases' => 0, 'net_amount' => 10]);
        $this->journal(['company_id' => 2, 'branch_id' => 3, 'sales' => 500, 'purchases' => 100, 'net_amount' => 400]);

        $rows = DailyJournalSummaryService::summaryQuery([])->get();
        $this->assertCount(4, $rows);

        $branchOne = $rows->firstWhere('id', $first);
        $this->assertSame(2, (int) $branchOne->entries_count);
        $this->assertSame(150.0, (float) $branchOne->sales_sum);
        $this->assertSame(70.0, (float) $branchOne->purchases_sum);
        $this->assertSame(80.0, (float) $branchOne->net_sum);

        $stats = DailyJournalSummaryService::stats([]);
        $this->assertSame(4, (int) $stats->branches);
        $this->assertSame(5, (int) $stats->entries_count);
        $this->assertSame(860.0, (float) $stats->sales_sum);
        $this->assertSame(420.0, (float) $stats->purchases_sum);
        $this->assertSame(440.0, (float) $stats->net_sum);

        // Entries of the same branch are grouped, ordered by the newest entry date.
        $this->assertSame([$newer, $first], DailyJournalSummaryService::details($first, [])['entries']->modelKeys());

        // Entries without a branch form their own group.
        $noBranch = $rows->first(fn ($row) => $row->branch_id === null);
        $this->assertSame(1, (int) $noBranch->entries_count);
        $this->assertSame([$noBranch->id], DailyJournalSummaryService::details($noBranch->id, [])['entries']->modelKeys());

        // The journal type filter narrows the grouped rows.
        $detailedOnly = DailyJournalSummaryService::summaryQuery(['is_detailed' => '1'])->get();
        $this->assertSame(1, (int) $detailedOnly->firstWhere('id', $first)->entries_count);
        $this->assertSame(100.0, (float) $detailedOnly->firstWhere('id', $first)->sales_sum);

        // The date filter keeps only the matching entries in each group.
        $datedRows = DailyJournalSummaryService::summaryQuery(['date_from' => '2026-11-02'])->get();
        $branchOneDated = $datedRows->firstWhere('branch_id', 1);
        $this->assertSame(1, (int) $branchOneDated->entries_count);
        $this->assertSame(50.0, (float) $branchOneDated->sales_sum);
    }

    public function test_filament_and_print_use_the_same_filters(): void
    {
        $user = $this->staff(permissions: ['view_any_daily_journal']);
        $filteredOut = $this->journal(['branch_id' => 1, 'entry_date' => '2026-11-01', 'entry_number' => 'OCTOBER-JOURNAL']);
        $local = $this->journal(['branch_id' => 1, 'entry_date' => '2026-11-05']);

        Livewire::test(DailyJournalsSummary::class)
            ->set('dateFrom', '2026-11-02')
            ->call('openDetails', $local)
            ->assertDispatched('open-modal', id: 'daily-journal-details')
            ->assertSee('DJ-2')->assertDontSee('OCTOBER-JOURNAL')
            ->call('exportCsv')->assertFileDownloaded('daily-journals-summary-2026-11-11.csv');

        $this->get(route('daily-journals.summary.print', ['date_from' => '2026-11-02']))
            ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && (int) $rows->first()->entries_count === 1);
        $this->get(route('daily-journals.summary.print', ['journal' => $local, 'date_from' => '2026-11-02']))
            ->assertOk()->assertSee('DJ-2')->assertDontSee('OCTOBER-JOURNAL');
        $this->get(route('daily-journals.summary.print', ['journal' => $filteredOut, 'date_from' => '2026-11-02']))
            ->assertNotFound();
    }

    public function test_links_are_scoped_to_company_branch_and_permissions(): void
    {
        $user = $this->staff(permissions: ['view_any_daily_journal'], branch: 1);
        $local = $this->journal(['branch_id' => 1]);
        $otherBranch = $this->journal(['branch_id' => 2]);
        $otherCompany = $this->journal(['company_id' => 2, 'branch_id' => 3]);

        $this->assertSame([$local], DailyJournalSummaryService::summaryQuery([])->get()->modelKeys());
        $this->assertSame(0, DailyJournalSummaryService::summaryQuery(['company_id' => 2])->count());
        $this->get(route('daily-journals.summary.print', ['journal' => $otherBranch]))->assertNotFound();
        $this->get(route('daily-journals.summary.print', ['journal' => $otherCompany]))->assertNotFound();
        Livewire::test(DailyJournalsSummary::class)->call('openDetails', $otherBranch)->assertNotFound();

        $this->staff();
        $this->get(route('daily-journals.summary.print'))->assertForbidden();
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

    private function journal(array $attributes): int
    {
        return DB::table('daily_journals')->insertGetId(array_merge([
            'entry_number' => 'DJ-'.(DB::table('daily_journals')->count() + 1),
            'company_id' => 1,
            'branch_id' => 1,
            'entry_date' => '2026-11-01',
            'sales' => 100,
            'purchases' => 0,
            'net_amount' => 100,
        ], $attributes));
    }
}
