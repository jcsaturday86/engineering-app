<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    /**
     * In-memory cache of settings to avoid repeated DB queries.
     *
     * @var array<string, mixed>|null
     */
    protected static ?array $cache = null;

    /**
     * Get a setting value by key, with an optional default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->loadCache();

        return static::$cache[$key] ?? $default;
    }

    /**
     * Set a setting value by key. Creates or updates the setting row.
     */
    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value],
        );

        // Invalidate cache so the next read picks up the new value
        static::$cache = null;
    }

    /**
     * Get all settings within a group as a key-value array.
     *
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array
    {
        return Setting::where('group', $group)
            ->pluck('value', 'key')
            ->map(fn ($val, $key) => $this->castValue($key, $val))
            ->toArray();
    }

    /**
     * Load all settings into the static cache (once per request).
     */
    protected function loadCache(): void
    {
        if (static::$cache !== null) {
            return;
        }

        static::$cache = Setting::pluck('value', 'key')
            ->toArray();
    }

    /**
     * Cast a setting value based on its stored type.
     */
    protected function castValue(string $key, mixed $value): mixed
    {
        $setting = Setting::where('key', $key)->first();

        if (! $setting) {
            return $value;
        }

        return match ($setting->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Flush the static cache (useful in tests or after bulk updates).
     */
    public static function flushCache(): void
    {
        static::$cache = null;
    }
}
