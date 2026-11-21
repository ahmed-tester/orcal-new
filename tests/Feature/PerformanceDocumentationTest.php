<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemDocumentationPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PerformanceDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_documentation_page(): void
    {
        $this->get('/system-documentation-page')
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_super_admin_can_access_performance_documentation_subpage(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->get('/system-documentation-page?activeGroup=reports&activeSub=performance')
            ->assertOk()
            ->assertSee('playerPerformance')
            ->assertSee('/videos/performance.mp4')
            ->assertSee('لوحة أداء المنشآت والأقسام')
            ->assertSee('أداء الموظف الشخصية')
            ->assertSee('تقييم أداء المشرفين')
            ->assertSee('50%');
    }

    public function test_active_group_performance_alias_maps_to_reports_and_sub_performance(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->get('/system-documentation-page?activeGroup=performance')
            ->assertOk()
            ->assertSee('playerPerformance')
            ->assertSee('performance.mp4');
    }

    public function test_livewire_can_switch_to_performance_sub_tab(): void
    {
        $admin = User::factory()->create([
            'user_type' => 'super_admin',
        ]);

        $this->actingAs($admin);

        Livewire::test(SystemDocumentationPage::class)
            ->set('activeGroup', 'reports')
            ->call('setSub', 'performance')
            ->assertSet('activeSub', 'performance')
            ->assertSeeHtml('id="playerPerformance"')
            ->assertSee('performance.mp4')
            ->assertSee('معادلة مقياس الأداء (0-100)');
    }
}
