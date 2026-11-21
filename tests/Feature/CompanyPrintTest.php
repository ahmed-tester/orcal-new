<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_company_print_routes(): void
    {
        $company = Company::create([
            'trade_name' => 'شركة فواز للاختبار',
            'company_number' => 'COM-001',
            'status' => 'active',
        ]);

        $this->get(route('companies.print', $company))->assertRedirect(route('filament.admin.auth.login'));
        $this->get(route('companies.pdf', $company))->assertRedirect(route('filament.admin.auth.login'));
        $this->get(route('exports.companies-statement'))->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_super_admin_can_preview_company_print_sheet(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create([
            'trade_name' => 'شركة أوركال القابضة',
            'trade_name_en' => 'Orcal Holding Co',
            'company_number' => 'COM-099',
            'commercial_registration_number' => '1010123456',
            'phone' => '0501234567',
            'email' => 'info@orcal.test',
            'status' => 'active',
        ]);

        Branch::create([
            'company_id' => $company->id,
            'name' => 'فرع الرياض الرئيسي',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('companies.print', $company))
            ->assertOk()
            ->assertSee('شركة أوركال القابضة')
            ->assertSee('Orcal Holding Co')
            ->assertSee('COM-099')
            ->assertSee('1010123456')
            ->assertSee('0501234567')
            ->assertSee('فرع الرياض الرئيسي');
    }

    public function test_super_admin_accessing_pdf_route_is_safely_redirected_to_print_preview(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create([
            'trade_name' => 'شركة أوركال للتقنية',
            'company_number' => 'COM-100',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('companies.pdf', $company));

        $response->assertRedirect(route('companies.print', $company));
    }

    public function test_super_admin_can_view_companies_statement_and_filter_by_status(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);

        $activeCompany = Company::create([
            'trade_name' => 'الشركة الأولى النشطة',
            'company_number' => 'COM-A01',
            'status' => 'active',
        ]);

        $inactiveCompany = Company::create([
            'trade_name' => 'الشركة الثانية غير النشطة',
            'company_number' => 'COM-I01',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->get(route('exports.companies-statement'))
            ->assertOk()
            ->assertSee('الشركة الأولى النشطة')
            ->assertSee('الشركة الثانية غير النشطة');

        $this->actingAs($admin)
            ->get(route('exports.companies-statement', ['status' => 'active']))
            ->assertOk()
            ->assertSee('الشركة الأولى النشطة')
            ->assertDontSee('الشركة الثانية غير النشطة');

        $this->actingAs($admin)
            ->get(route('exports.companies-statement', ['status' => 'inactive']))
            ->assertOk()
            ->assertDontSee('الشركة الأولى النشطة')
            ->assertSee('الشركة الثانية غير النشطة');
    }

    public function test_super_admin_can_export_companies_statement_pdf(): void
    {
        $admin = User::factory()->create(['user_type' => 'super_admin']);
        Company::create([
            'trade_name' => 'شركة التصدير بي دي إف',
            'company_number' => 'COM-PDF',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('exports.companies-statement', ['export_mode' => 'pdf']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
