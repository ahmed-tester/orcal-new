<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserFavorite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_url_cleans_host_slashes_and_whitespace(): void
    {
        $this->assertSame('/employees', UserFavorite::normalizeUrl('https://example.com/employees/'));
        $this->assertSame('/employees', UserFavorite::normalizeUrl('  /employees///  '));
        $this->assertSame('/', UserFavorite::normalizeUrl('https://example.com/'));
        $this->assertSame('/employees?page=2', UserFavorite::normalizeUrl('/employees/?page=2'));
    }

    public function test_guest_cannot_toggle_or_delete_favorites(): void
    {
        $user = User::factory()->create();
        $favorite = $user->favorites()->create([
            'title' => 'Employees',
            'url' => '/employees',
            'icon' => 'heroicon-o-users',
            'sort_order' => 1,
        ]);

        $this->postJson(route('favorites.toggle'), [
            'url' => '/employees',
            'title' => 'Employees',
        ])->assertUnauthorized();

        $this->deleteJson(route('favorites.destroy', $favorite))
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_toggle_favorite_and_receive_badge_key(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);

        $addResponse = $this->actingAs($user)->postJson(route('favorites.toggle'), [
            'url' => 'https://example.com/companies/',
            'title' => 'Companies',
            'icon' => 'heroicon-o-building-office',
        ]);

        $addResponse->assertOk()
            ->assertJson([
                'favorited' => true,
                'url' => '/companies',
            ])
            ->assertJsonStructure([
                'favorites' => [
                    ['id', 'title', 'url', 'icon', 'count'],
                ],
            ]);

        $this->assertDatabaseHas('user_favorites', [
            'user_id' => $user->id,
            'url' => '/companies',
            'title' => 'Companies',
        ]);

        $removeResponse = $this->actingAs($user)->postJson(route('favorites.toggle'), [
            'url' => '/companies',
            'title' => 'Companies',
        ]);

        $removeResponse->assertOk()
            ->assertJson([
                'favorited' => false,
                'url' => '/companies',
                'favorites' => [],
            ]);

        $this->assertDatabaseMissing('user_favorites', [
            'user_id' => $user->id,
            'url' => '/companies',
        ]);
    }

    public function test_toggle_rejects_livewire_urls(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.toggle'), [
                'url' => '/livewire/update',
                'title' => 'Invalid',
            ])
            ->assertStatus(422);
    }

    public function test_user_cannot_delete_another_users_favorite(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $favorite = $owner->favorites()->create([
            'title' => 'Employees',
            'url' => '/employees',
            'icon' => 'heroicon-o-users',
            'sort_order' => 1,
        ]);

        $this->actingAs($otherUser)
            ->deleteJson(route('favorites.destroy', $favorite))
            ->assertForbidden();

        $this->actingAs($owner)
            ->deleteJson(route('favorites.destroy', $favorite))
            ->assertOk()
            ->assertJson([
                'deleted' => true,
                'url' => '/employees',
                'favorites' => [],
            ]);
    }
}
