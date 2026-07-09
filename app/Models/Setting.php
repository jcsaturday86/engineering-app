<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Base64 data URI for a Setting's uploaded file (e.g. general.logo), or null if
     * unset/missing. Centralizes the pattern used to embed dynamic branding images
     * (city seal, DPWH logo, national government logo) into PDF/print views so they
     * always reflect the current value in Settings > General.
     */
    public static function imageDataUri(Collection $settings, string $key): ?string
    {
        $path = $settings[$key] ?? null;

        if (empty($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path);

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    }

    /**
     * Fetch all "general" group settings, keyed by setting key.
     */
    public static function general(): Collection
    {
        return static::where('group', 'general')->pluck('value', 'key');
    }
}
