@extends('layouts.app')

@section('title', $permitType->name . ' Requirements')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700">Settings</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.document-requirements') }}" class="text-gray-500 hover:text-gray-700">Document Requirements</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $permitType->name }}</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $permitType->name }} — Document Requirements</h2>
            <p class="text-sm text-gray-500 mt-1">
                Documents the online client must attach for {{ Str::startsWith(strtolower($permitType->name), ['a','e','i','o','u']) ? 'an' : 'a' }} {{ strtolower($permitType->name) }} application.
            </p>
        </div>
        <a href="{{ route('settings.document-requirements') }}" class="text-sm text-gray-500 hover:text-gray-800 shrink-0">
            <i class="fas fa-arrow-left text-xs mr-1"></i> All services
        </a>
    </div>

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
        class="flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
        <i class="fas fa-check-circle text-green-500"></i>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Legend --}}
    <div class="flex items-center flex-wrap gap-4 px-4 py-3 bg-white rounded-xl border border-gray-200 text-xs text-gray-500">
        <span class="flex items-center gap-1.5">
            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">Mandatory</span> blocks submission
        </span>
        <span class="flex items-center gap-1.5">
            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">Conditional</span> shown with its condition, never blocks
        </span>
        <span class="flex items-center gap-1.5">
            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">Optional</span> never blocks
        </span>
        <span class="flex items-center gap-1.5">
            <i class="fas fa-heading text-gray-400"></i> heading rows accept no upload
        </span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-900">
                Requirements
                <span class="ml-1 text-xs font-normal text-gray-400">
                    ({{ $requirements->count() }} top-level, {{ $requirements->sum(fn ($r) => $r->children->count()) }} nested)
                </span>
            </p>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($requirements as $requirement)
                @include('settings.partials.document-requirement-row', [
                    'requirement' => $requirement,
                    'permitType' => $permitType,
                    'parentOptions' => $parentOptions,
                    'depth' => 0,
                ])

                @foreach($requirement->children as $child)
                    @include('settings.partials.document-requirement-row', [
                        'requirement' => $child,
                        'permitType' => $permitType,
                        'parentOptions' => $parentOptions,
                        'depth' => 1,
                    ])
                @endforeach
            @empty
            <div class="px-5 py-12 text-center">
                <i class="fas fa-folder-open text-3xl text-gray-300 mb-3"></i>
                <p class="text-sm text-gray-500">No document requirements configured for this service yet.</p>
                <p class="text-xs text-gray-400 mt-1">Clients will see "no documents required" and can submit without attaching anything.</p>
            </div>
            @endforelse
        </div>

        {{-- Add new requirement --}}
        <div x-data="{ adding: {{ $errors->any() ? 'true' : 'false' }} }" class="border-t border-gray-200 bg-gray-50">
            <button x-show="!adding" @click="adding = true" type="button"
                class="w-full flex items-center justify-center gap-2 px-5 py-3.5 text-sm font-medium text-blue-600 hover:bg-gray-100 transition">
                <i class="fas fa-plus text-xs"></i> Add Requirement
            </button>

            <form x-show="adding" x-cloak method="POST" action="{{ route('settings.document-requirements.store', $permitType) }}"
                class="p-5 space-y-4" autocomplete="off">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Document name <span class="text-red-500">*</span></label>
                    <textarea name="name" rows="2" required maxlength="1000"
                        placeholder="e.g. Certified True Copy of Latest Tax Declaration"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">{{ old('name') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Condition note</label>
                    <input type="text" name="condition_note" maxlength="1000" value="{{ old('condition_note') }}"
                        placeholder="e.g. In case the applicant is not the registered owner of the lot."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Shown to the client as helper text under the document name.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Obligation level</label>
                        <select name="requirement_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="mandatory" {{ old('requirement_level') === 'mandatory' ? 'selected' : '' }}>Mandatory</option>
                            <option value="conditional" {{ old('requirement_level', 'conditional') === 'conditional' ? 'selected' : '' }}>Conditional</option>
                            <option value="optional" {{ old('requirement_level') === 'optional' ? 'selected' : '' }}>Optional</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nest under</label>
                        <select name="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">— Top level —</option>
                            @foreach($parentOptions as $option)
                            <option value="{{ $option->id }}" {{ (int) old('parent_id') === $option->id ? 'selected' : '' }}>
                                {{ Str::limit($option->name, 60) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-4 pb-2">
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input type="checkbox" name="is_uploadable" value="1" {{ old('is_uploadable', '1') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            Accepts upload
                        </label>
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            Active
                        </label>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i> Add Requirement
                    </button>
                    <button type="button" @click="adding = false" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
