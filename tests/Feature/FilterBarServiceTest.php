<?php

namespace Tests\Feature;

use App\Enums\TreasuryTransactionType;
use App\Models\Installment;
use App\Models\TreasuryTransaction;
use App\Services\FilterBarService;
use Illuminate\Database\Eloquent\Builder;
use Tests\TestCase;

class FilterBarServiceTest extends TestCase
{
    public function test_it_applies_search_company_and_date_filters(): void
    {
        $query = FilterBarService::applyFilters(TreasuryTransaction::query(), [
            'search' => 'رواتب',
            'company_id' => ['2', '3'],
            'status' => ['all'],
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ], [
            'search_columns' => ['recipient_or_payer', 'company.trade_name'],
            'company' => true,
            'status' => true,
            'date_column' => 'transaction_date',
        ]);

        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('recipient_or_payer', $sql);
        $this->assertStringContainsString('exists (', $sql);
        $this->assertStringContainsString('company_id', $sql);
        $this->assertStringContainsString('transaction_date', $sql);
        $this->assertSame(
            ['%رواتب%', '%رواتب%', '2', '3', '2026-01-01', '2026-01-31'],
            $query->getBindings()
        );
    }

    public function test_it_keeps_zero_values_and_normalizes_enums(): void
    {
        $query = FilterBarService::applyFilters(TreasuryTransaction::query(), [], [
            'where_in' => [
                'transaction_type' => ['0', TreasuryTransactionType::INCOMING],
            ],
        ]);

        $this->assertSame(['0', 'incoming'], $query->getBindings());
    }

    public function test_it_supports_nested_relation_search_columns(): void
    {
        $query = FilterBarService::applyFilters(Installment::query(), [
            'search' => 'أحمد',
        ], [
            'search_columns' => [
                'external_name',
                'employee.user.name',
                'employee.details.national_id',
            ],
        ]);

        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('external_name', $sql);
        $this->assertStringContainsString('employees', $sql);
        $this->assertStringContainsString('national_id', $sql);
        $this->assertSame(['%أحمد%', '%أحمد%', '%أحمد%'], $query->getBindings());
    }

    public function test_it_skips_empty_values_and_the_all_marker(): void
    {
        $query = FilterBarService::applyFilters(TreasuryTransaction::query(), [
            'company_id' => [],
            'status' => 'all',
            'date_from' => null,
        ], [
            'company' => true,
            'status' => true,
            'date_column' => 'transaction_date',
        ]);

        $this->assertSame([], $query->getBindings());
    }

    public function test_it_runs_page_specific_extra_callbacks(): void
    {
        $query = FilterBarService::applyFilters(TreasuryTransaction::query(), [
            'payment_item' => 'تست',
        ], [
            'extra' => [
                fn (Builder $query, array $state) => $query->where('description', 'like', "%{$state['payment_item']}%"),
            ],
        ]);

        $this->assertStringContainsString('description', $query->toSql());
        $this->assertSame(['%تست%'], $query->getBindings());
    }
}
