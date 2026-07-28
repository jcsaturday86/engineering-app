@extends('layouts.app')

@section('title', 'New Online Application')

@section('breadcrumbs')
    <a href="{{ route('online.dashboard') }}" class="text-gray-500 hover:text-gray-700">My Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">New Application</span>
@endsection

@section('content')
@php
    $typeMeta = [
        'BP' => ['icon' => 'fa-building', 'color' => 'bg-blue-600', 'desc' => 'Construct, renovate, or add to a building or structure.'],
        'OP' => ['icon' => 'fa-door-open', 'color' => 'bg-indigo-600', 'desc' => 'Apply for occupancy of a completed structure.'],
        'FP' => ['icon' => 'fa-border-all', 'color' => 'bg-teal-600', 'desc' => 'Construct or erect a fence around a property.'],
        'DP' => ['icon' => 'fa-hammer', 'color' => 'bg-orange-600', 'desc' => 'Demolish or move an existing building or structure.'],
        'SGP' => ['icon' => 'fa-sign-hanging', 'color' => 'bg-pink-600', 'desc' => 'Install, attach, or paint a signage on your premises.'],
        'AI' => ['icon' => 'fa-clipboard-check', 'color' => 'bg-emerald-600', 'desc' => 'Annual inspection of mechanical/electrical equipment.'],
    ];
@endphp
<div class="max-w-4xl mx-auto">
    <h2 class="text-xl font-bold text-gray-900 mb-2">New Permit Application</h2>
    <p class="text-sm text-gray-500 mb-6">Choose the type of permit you'd like to apply for. You'll fill out the full application form, save it as a draft, and submit it for Engineering review whenever you're ready.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach($permitTypes as $pt)
        @php $meta = $typeMeta[$pt->code] ?? ['icon' => 'fa-file-lines', 'color' => 'bg-gray-600', 'desc' => '']; @endphp
        <a href="{{ route('online.apply', ['type' => $pt->code]) }}"
           class="flex items-start gap-4 p-5 bg-white rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-sm transition">
            <span class="flex items-center justify-center w-11 h-11 rounded-lg {{ $meta['color'] }} text-white shrink-0">
                <i class="fas {{ $meta['icon'] }}"></i>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-gray-900">{{ $pt->name }}</h3>
                <p class="text-xs text-gray-500 mt-1">{{ $meta['desc'] }}</p>
            </div>
        </a>
        @endforeach
    </div>

    <div class="mt-6">
        <a href="{{ route('online.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left text-xs mr-1"></i> Back to My Applications
        </a>
    </div>
</div>
@endsection
