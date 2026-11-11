<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceSummaryService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class AttendanceSummaryServiceTest extends TestCase
{
    public function test_it_resolves_month_date_range(): void
    {
        Carbon::setTestNow('2026-11-30 12:00:00');
        try {
            [$start, $end] = AttendanceSummaryService::resolveDateRange(['month' => '2026-11']);
            $this->assertSame('2026-11-01', $start);
            $this->assertSame('2026-11-30', $end);

            [$startFeb, $endFeb] = AttendanceSummaryService::resolveDateRange(['month' => '2026-02']);
            $this->assertSame('2026-02-01', $startFeb);
            $this->assertSame('2026-02-28', $endFeb);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_it_generates_month_options(): void
    {
        $options = AttendanceSummaryService::monthOptions(12);

        $this->assertCount(12, $options);
        $this->assertArrayHasKey(now()->format('Y-m'), $options);
    }

    public function test_summary_query_builds_expected_sql(): void
    {
        $query = AttendanceSummaryService::summaryQuery([
            'month' => '2026-11',
            'search' => 'أحمد',
            'nationality' => 'all',
            'employment_status' => 'all',
        ]);

        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('from employees', str_replace(['`', '"'], '', $sql));
        $this->assertStringContainsString('left join (select', $sql);
        $this->assertStringContainsString('recorded_days', $sql);
        $this->assertStringContainsString('present_days', $sql);
        $this->assertStringContainsString('late_days', $sql);
        $this->assertStringContainsString('absent_days', $sql);
        $this->assertStringContainsString('late_minutes', $sql);
        $this->assertStringContainsString('total_hours', $sql);
    }

    public function test_employee_relationship_is_defined(): void
    {
        $employee = new Employee;
        $relation = $employee->attendances();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Attendance::class, $relation->getRelated());
    }
}
