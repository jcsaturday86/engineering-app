@extends('layouts.app')

@section('title', 'Signatories')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700">Settings</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Signatories</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Signatories</h2>
    </div>

    {{-- Signatories List --}}
    <div class="space-y-4">
        @forelse($signatories as $signatory)
        <div x-data="{ editing: false }" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            {{-- Display Mode --}}
            <div x-show="!editing" class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                {{ $signatory->role }}
                            </span>
                            @if($signatory->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Inactive</span>
                            @endif
                        </div>
                        <h3 class="text-base font-semibold text-gray-900">{{ $signatory->name }}</h3>
                        <div class="mt-1 text-sm text-gray-600 space-y-0.5">
                            @if($signatory->title)
                                <p><span class="text-gray-400">Title:</span> {{ $signatory->title }}</p>
                            @endif
                            @if($signatory->designation)
                                <p><span class="text-gray-400">Designation:</span> {{ $signatory->designation }}</p>
                            @endif
                            @if($signatory->department)
                                <p><span class="text-gray-400">Department:</span> {{ $signatory->department }}</p>
                            @endif
                            @if($signatory->license_no)
                                <p><span class="text-gray-400">License No.:</span> {{ $signatory->license_no }}</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <button @click="editing = true" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-edit text-xs"></i> Edit
                        </button>
                    </div>
                </div>
            </div>

            {{-- Edit Mode --}}
            <div x-show="editing" x-cloak class="p-6">
                <form method="POST" action="{{ route('settings.signatories.update', $signatory) }}">
                    @csrf

                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                {{ $signatory->role }}
                            </span>
                            <span class="text-sm text-gray-500">Editing signatory</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" name="name"
                                    value="{{ old('name', $signatory->name) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                <input type="text" name="title"
                                    value="{{ old('title', $signatory->title) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                                <input type="text" name="designation"
                                    value="{{ old('designation', $signatory->designation) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                <input type="text" name="department"
                                    value="{{ old('department', $signatory->department) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">License No.</label>
                                <input type="text" name="license_no"
                                    value="{{ old('license_no', $signatory->license_no) }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="editing = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
            <i class="fas fa-file-signature text-3xl text-gray-300 mb-3"></i>
            <p class="text-gray-400">No signatories configured</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
