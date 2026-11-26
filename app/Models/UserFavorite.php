<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'url',
        'icon',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Normalize a URL to a clean relative internal path so host differences do not break matching.
     */
    public static function normalizeUrl(string $url): string
    {
        $parsed = parse_url(trim($url));
        $path = $parsed['path'] ?? '/';
        $path = '/' . ltrim($path, '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        if (! empty($parsed['query'])) {
            $path .= '?' . $parsed['query'];
        }

        return mb_substr($path, 0, 300);
    }
}
