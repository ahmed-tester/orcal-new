<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'group',
        'input_type',
        'value',
        'options',
        'description',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function ($setting) {
            if ($setting->isDirty('key') && $setting->getOriginal('key') !== null) {
                $setting->key = $setting->getOriginal('key');
            }
        });

        static::deleting(function ($setting) {
            return false;
        });

        static::saved(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("setting_is_active_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
            Cache::forget('settings_all');
        });

        static::deleted(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("setting_is_active_{$setting->key}");
            Cache::forget("settings_group_{$setting->group}");
            Cache::forget('settings_all');
        });
    }

    /**
     * Check if a setting is currently active/enabled.
     */
    public static function isActive(string $key): bool
    {
        return Cache::rememberForever("setting_is_active_{$key}", function () use ($key) {
            $setting = static::where('key', $key)->first();

            return $setting ? (bool) $setting->is_active : false;
        });
    }

    /**
     * Get a setting value only if it is active.
     */
    public static function getActive(string $key, mixed $default = null): mixed
    {
        if (!static::isActive($key)) {
            return $default;
        }

        return static::get($key, $default);
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting_{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set / Update a setting value.
     */
    public static function set(string $key, mixed $value, string $group = 'general', ?string $label = null, string $inputType = 'text'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'group' => $group,
                'label' => $label ?? $key,
                'input_type' => $inputType,
            ]
        );

        Cache::forget("setting_{$key}");
        Cache::forget("settings_group_{$group}");
        Cache::forget('settings_all');

        return $setting;
    }

    /**
     * Check if a setting exists.
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Get all settings by group.
     */
    public static function getGroup(string $group)
    {
        return Cache::rememberForever("settings_group_{$group}", function () use ($group) {
            return static::where('group', $group)->pluck('value', 'key');
        });
    }

    /**
     * Get allowed upload file extensions as an array.
     */
    public static function getAllowedUploadExtensions(): array
    {
        $extensions = static::get('allowed_upload_extensions', 'pdf,jpg,jpeg,png');
        if (is_array($extensions)) {
            return array_map('trim', array_map('strtolower', $extensions));
        }

        return array_filter(array_map('trim', explode(',', strtolower((string) $extensions))));
    }

    /**
     * Get allowed MIME types based on the configured extensions.
     */
    public static function getAllowedUploadMimeTypes(): array
    {
        $extensions = static::getAllowedUploadExtensions();
        $mimeMap = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt'  => 'text/plain',
        ];

        $mimes = [];
        foreach ($extensions as $ext) {
            if (isset($mimeMap[$ext])) {
                $mimes[] = $mimeMap[$ext];
            } elseif ($ext === 'image/*' || $ext === 'image') {
                $mimes[] = 'image/jpeg';
                $mimes[] = 'image/png';
                $mimes[] = 'image/webp';
            }
        }

        return !empty($mimes) ? array_values(array_unique($mimes)) : ['application/pdf', 'image/jpeg', 'image/png'];
    }

    /**
     * Get maximum allowed upload size in kilobytes.
     */
    public static function getMaxUploadSizeKb(): int
    {
        $size = static::get('max_upload_size_kb', 5120);

        return (int) ($size ?: 5120);
    }

    /**
     * Get Gemini API Key from settings or fallback to config/env.
     */
    public static function getGeminiApiKey(): ?string
    {
        $key = static::getActive('gemini_api_key');
        if (!empty($key)) {
            return trim((string) $key);
        }

        $rawKey = static::get('gemini_api_key');
        if (!empty($rawKey)) {
            return trim((string) $rawKey);
        }

        return config('services.gemini.key');
    }

    /**
     * Get Gemini primary model from settings or fallback to config/env.
     */
    public static function getGeminiModel(): string
    {
        $model = static::get('gemini_model');
        if (!empty($model)) {
            return trim((string) $model);
        }

        return config('services.gemini.model', 'gemini-3.5-flash');
    }
}

