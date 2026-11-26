<?php

namespace App\Http\Controllers;

use App\Models\UserFavorite;
use App\Services\SidebarBadgeService;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserFavoriteController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:300'],
            'title' => ['required', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $url = UserFavorite::normalizeUrl($validated['url']);

        if (str_starts_with($url, '/livewire')) {
            abort(422, 'Invalid favorite URL.');
        }

        $result = DB::transaction(function () use ($user, $url, $validated) {
            $existing = $user->favorites()
                ->where('url', $url)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();

                return [
                    'favorited' => false,
                    'message' => __('admin.removed_from_favorites'),
                ];
            }

            $nextSort = ((int) $user->favorites()->max('sort_order')) + 1;

            $user->favorites()->create([
                'title' => trim($validated['title']),
                'url' => $url,
                'icon' => $validated['icon'] ?: 'heroicon-o-star',
                'sort_order' => $nextSort,
            ]);

            return [
                'favorited' => true,
                'message' => __('admin.added_to_favorites'),
            ];
        });

        return response()->json([
            'favorited' => $result['favorited'],
            'url' => $url,
            'message' => $result['message'],
            'favorites' => $this->formatFavorites($user),
        ]);
    }

    public function destroy(Request $request, UserFavorite $favorite): JsonResponse
    {
        abort_unless($favorite->user_id === $request->user()->id, 403);

        $removedUrl = $favorite->url;
        $favorite->delete();

        return response()->json([
            'deleted' => true,
            'url' => $removedUrl,
            'message' => __('admin.removed_from_favorites'),
            'favorites' => $this->formatFavorites($request->user()),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatFavorites($user): array
    {
        if (Filament::getCurrentPanel() === null && ($defaultPanel = Filament::getDefaultPanel())) {
            Filament::setCurrentPanel($defaultPanel);
        }

        $sidebarCounts = rescue(fn () => SidebarBadgeService::getCountsByUrl(), [], false);

        return $user->favorites()
            ->get(['id', 'title', 'url', 'icon', 'sort_order'])
            ->map(function (UserFavorite $fav) use ($sidebarCounts) {
                $count = $sidebarCounts[rtrim(url($fav->url), '/')] ?? null;

                return [
                    'id' => $fav->id,
                    'title' => $fav->title,
                    'url' => $fav->url,
                    'icon' => $fav->icon,
                    'count' => ($count !== null && $count > 0) ? (int) $count : null,
                ];
            })
            ->values()
            ->all();
    }
}
