<?php

namespace Tests\Feature;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserType;
use App\Filament\Pages\CompanyDepartmentPerformance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CompanyTicketsPerformanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CompanyTicketsPerformanceTest extends TestCase
{
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        config(['cache.default' => 'array']);
        config(['queue.default' => 'sync']);
        Notification::fake();
        DB::purge();
        DB::reconnect('sqlite');

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_09_17_123833_create_permission_tables.php',
            '2026_09_17_132000_create_companies_and_organization_tables.php',
            '2026_10_01_300000_create_support_tickets_tables.php',
            '2026_10_13_103000_add_metadata_column_for_hijri_dates.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    public function test_unauthorized_user_cannot_view_tickets_performance(): void
    {
        $user = $this->createUser(UserType::SAUDI_EMPLOYEE);
        $this->actingAs($user);

        $this->assertFalse(CompanyTicketsPerformanceService::canView($user));

        $page = new CompanyDepartmentPerformance;
        $this->assertFalse($page->canViewTicketsOverview());

        $this->expectException(HttpException::class);
        CompanyTicketsPerformanceService::getStats([]);
    }

    public function test_permitted_user_sees_only_authorized_company_tickets_and_ignores_branch_filter(): void
    {
        $companyA = $this->createCompany('A');
        $companyB = $this->createCompany('B');

        $staff = $this->createUser(UserType::SAUDI_EMPLOYEE, $companyA->id);
        Employee::create([
            'user_id' => $staff->id,
            'company_id' => $companyA->id,
            'status' => 'active',
        ]);
        Permission::firstOrCreate(['name' => 'view_any_ticket', 'guard_name' => 'web']);
        $staff->givePermissionTo('view_any_ticket');
        $this->actingAs($staff);

        $this->createTicket($companyA->id, $staff->id, [
            'status' => TicketStatus::OPEN->value,
            'priority' => TicketPriority::URGENT->value,
            'category' => TicketCategory::TECHNICAL->value,
            'created_at' => '2026-11-10 10:00:00',
        ]);
        $this->createTicket($companyA->id, $staff->id, [
            'status' => TicketStatus::RESOLVED->value,
            'priority' => TicketPriority::LOW->value,
            'category' => TicketCategory::FINANCE->value,
            'created_at' => '2026-11-12 10:00:00',
        ]);
        // Outside date range
        $this->createTicket($companyA->id, $staff->id, [
            'status' => TicketStatus::OPEN->value,
            'priority' => TicketPriority::HIGH->value,
            'category' => TicketCategory::HR->value,
            'created_at' => '2026-10-01 10:00:00',
        ]);
        // Unauthorized company
        $this->createTicket($companyB->id, $staff->id, [
            'status' => TicketStatus::OPEN->value,
            'priority' => TicketPriority::URGENT->value,
            'category' => TicketCategory::TECHNICAL->value,
            'created_at' => '2026-11-10 10:00:00',
        ]);

        $stats = CompanyTicketsPerformanceService::getStats([
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
            'branch_id' => 999,
        ]);

        $this->assertTrue($stats['has_data']);
        $this->assertSame(2, $stats['totals']['all']);
        $this->assertSame(1, $stats['totals']['open']);
        $this->assertSame(1, $stats['totals']['resolved_closed']);
        $this->assertSame(1, $stats['totals']['urgent_high']);
        $this->assertCount(2, $stats['categories']);

        $openStatement = CompanyTicketsPerformanceService::getStatementQuery([
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-30',
        ], 'open')->get();

        $this->assertCount(1, $openStatement);
        $this->assertSame($companyA->id, $openStatement->first()->company_id);
    }

    public function test_performance_page_switches_to_tickets_tab_and_opens_kashf(): void
    {
        $company = $this->createCompany('L');
        $admin = $this->createUser(UserType::SUPER_ADMIN, $company->id);
        $this->actingAs($admin);

        $this->createTicket($company->id, $admin->id, [
            'status' => TicketStatus::IN_PROGRESS->value,
            'priority' => TicketPriority::HIGH->value,
            'category' => TicketCategory::COMPLAINT->value,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $page = new CompanyDepartmentPerformance;
        $page->mount();
        $this->assertTrue($page->canViewTicketsOverview());

        $page->showTicketsOverview();
        $this->assertSame('tickets', $page->activeOverviewTab);

        $overview = $page->ticketsOverview();
        $this->assertSame(1, $overview['totals']['all']);
        $this->assertSame(1, $overview['totals']['in_progress']);
        $this->assertSame(1, $overview['totals']['urgent_high']);

        $page->openStatement('tickets', 'in_progress');
        $this->assertTrue($page->showStatementModal);
        $this->assertSame('tickets', $page->statementDomain);
        $this->assertSame('in_progress', $page->statementType);

        $records = $page->statementRecords();
        $this->assertNotNull($records);
        $this->assertSame(1, $records->total());
    }

    private function createCompany(string $suffix): Company
    {
        $seq = ++$this->seq;

        return Company::create([
            'trade_name' => "Company {$suffix} {$seq}",
            'code' => "CMP-{$seq}",
        ]);
    }

    private function createUser(UserType $type, ?int $companyId = null): User
    {
        $seq = ++$this->seq;

        return User::create([
            'name' => "User {$seq}",
            'email' => "user{$seq}@example.test",
            'password' => bcrypt('password'),
            'user_type' => $type,
            'company_id' => $companyId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTicket(int $companyId, int $userId, array $attributes = []): Ticket
    {
        $seq = ++$this->seq;
        $createdAt = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $ticket = Ticket::create(array_merge([
            'ticket_number' => "TCK-2026-00{$seq}",
            'user_id' => $userId,
            'company_id' => $companyId,
            'category' => TicketCategory::TECHNICAL->value,
            'priority' => TicketPriority::MEDIUM->value,
            'status' => TicketStatus::OPEN->value,
            'subject' => "Ticket {$seq}",
            'description' => 'Test description',
        ], $attributes));

        if ($createdAt !== null) {
            DB::table('tickets')->where('id', $ticket->id)->update(['created_at' => $createdAt]);
        }

        return $ticket->refresh();
    }
}
