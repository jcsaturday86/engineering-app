@extends('layouts.app')

@section('title', 'Settings')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Settings</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">General Settings</h2>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" autocomplete="off" enctype="multipart/form-data">
        @csrf

        <div class="space-y-6">
            @foreach($settings as $group => $items)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">{{ $group }}</h3>
                </div>
                <div class="p-6 space-y-5">
                    @foreach($items as $setting)
                    <div>
                        <label for="setting_{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-1">
                            {{ ucwords(str_replace(['_', '-'], ' ', $setting->key)) }}
                        </label>

                        @if($setting->type === 'boolean')
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                <input type="checkbox"
                                    id="setting_{{ $setting->key }}"
                                    name="settings[{{ $setting->key }}]"
                                    value="1"
                                    {{ $setting->value ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        @elseif($setting->type === 'textarea')
                            <textarea
                                id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >{{ old('settings.' . $setting->key, $setting->value) }}</textarea>
                        @elseif($setting->type === 'number')
                            <input type="number"
                                id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                value="{{ old('settings.' . $setting->key, $setting->value) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @elseif($setting->type === 'file')
                            @if($setting->value)
                                <img src="{{ asset('storage/' . $setting->value) }}" alt="Current {{ $setting->key }}" class="h-16 mb-2 rounded border border-gray-200 object-contain bg-gray-50 p-1">
                            @endif
                            <input type="file"
                                id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                accept="image/png,image/jpeg"
                                class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @else
                            <input type="text"
                                id="setting_{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                value="{{ old('settings.' . $setting->key, $setting->value) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @endif

                        @if($setting->description)
                            <p class="mt-1 text-xs text-gray-500">{{ $setting->description }}</p>
                        @endif

                        @error('settings.' . $setting->key)
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
