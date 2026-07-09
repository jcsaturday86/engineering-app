@php
    $faviconSettings = \App\Models\Setting::whereIn('key', ['general.favicon', 'general.logo'])->pluck('value', 'key');
    $faviconPath = $faviconSettings['general.favicon'] ?? null;
    if (empty($faviconPath) || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconPath)) {
        $faviconPath = $faviconSettings['general.logo'] ?? null;
    }
    $faviconUrl = $faviconPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconPath)
        ? asset('storage/' . $faviconPath)
        : asset('favicon.ico');
@endphp
<link rel="icon" type="image/png" href="{{ $faviconUrl }}">
