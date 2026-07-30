@extends('layouts.app')

@section('title', 'Document Requirements')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700">Settings</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Document Requirements</span>
@endsection

@section('content')
@php
    $typeIcons = [
        'BP' => ['icon' => 'fa-building', 'bg' => 'bg-blue-100', 'fg' => 'text-blue-600'],
        'OP' => ['icon' => 'fa-door-open', 'bg' => 'bg-indigo-100', 'fg' => 'text-indigo-600'],
        'FP' => ['icon' => 'fa-border-all', 'bg' => 'bg-teal-100', 'fg' => 'text-teal-600'],
        'DP' => ['icon' => 'fa-hammer', 'bg' => 'bg-orange-100', 'fg' => 'text-orange-600'],
        'SGP' => ['icon' => 'fa-sign-hanging', 'bg' => 'bg-pink-100', 'fg' => 'text-pink-600'],
        'AI' => ['icon' => 'fa-clipboard-check', 'bg' => 'bg-emerald-100', 'fg' => 'text-emerald-600'],
    ];
@endphp
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Document Requirements</h2>
        <p class="text-sm text-gray-500 mt-1">
            The documents online clients must attach for each service. Mandatory items must all be
            uploaded before an application can be submitted for review; conditional and optional
            items are shown to the client but never block submission.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($permitTypes as $permitType)
        @php $meta = $typeIcons[$permitType->code] ?? ['icon' => 'fa-file-lines', 'bg' => 'bg-gray-100', 'fg' => 'text-gray-600']; @endphp
        <a href="{{ route('settings.document-requirements.type', $permitType) }}"
           class="group bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-400 hover:shadow-sm transition flex items-center gap-4">
            <span class="flex items-center justify-center w-11 h-11 {{ $meta['bg'] }} rounded-lg shrink-0">
                <i class="fas {{ $meta['icon'] }} {{ $meta['fg'] }}"></i>
            </span>
            <div class="min-w-0 flex-1">
                <h3 class="text-sm font-semibold text-gray-900">{{ $permitType->name }}</h3>
                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $permitType->code }}</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $permitType->document_requirements_count > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $permitType->document_requirements_count }} {{ Str::plural('requirement', $permitType->document_requirements_count) }}
                    </span>
                    @if($permitType->mandatory_requirements_count > 0)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                        {{ $permitType->mandatory_requirements_count }} mandatory
                    </span>
                    @elseif($permitType->document_requirements_count === 0)
                    <span class="text-xs text-gray-400 italic">none required yet</span>
                    @endif
                </div>
            </div>
            <i class="fas fa-chevron-right text-xs text-gray-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition shrink-0"></i>
        </a>
        @endforeach
    </div>
</div>
@endsection
