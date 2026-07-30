@extends('layouts.app')

@section('title', 'New Online Application')

@section('breadcrumbs')
    <a href="{{ route('online.applications') }}" class="text-gray-500 hover:text-gray-700">My Applications</a>
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

    $steps = [
        ['title' => 'Choose your permit type',   'body' => 'Pick the one on the right that matches your project.'],
        ['title' => 'Fill out the form',         'body' => 'Complete every required field and save. It is kept as a <strong>Draft</strong> you can edit anytime.'],
        ['title' => 'Upload your requirements',  'body' => 'Attach your plans and supporting documents. At least one is required before you can submit.'],
        ['title' => 'Submit for review',         'body' => 'Engineering reviews it. You cannot edit while it is under review.'],
        ['title' => 'Watch for the result',      'body' => 'You will be notified. If <strong>returned</strong>, the reason appears on your application — fix it and resubmit.'],
        ['title' => 'Pay and claim your permit', 'body' => 'Settle the fees at the Treasury office, then download your permit once it is generated.'],
    ];
@endphp
<div class="max-w-6xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1 sm:gap-4 mb-4">
        <h2 class="text-xl font-bold text-gray-900">New Permit Application</h2>
        <a href="{{ route('online.applications') }}" class="text-sm text-gray-500 hover:text-gray-800 shrink-0">
            <i class="fas fa-arrow-left text-xs mr-1"></i> Back to My Applications
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)] gap-6 items-stretch">

        {{-- ============================================================== --}}
        {{-- LEFT — HOW IT WORKS --}}
        {{-- ============================================================== --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col h-full">
            <h3 class="text-base font-semibold text-gray-900 mb-2">
                <i class="fas fa-circle-info text-blue-500 text-2xl mr-1.5"></i> How it works
            </h3>

            <ol class="relative flex-1 flex flex-col justify-between">
                {{-- vertical rail joining the step numbers --}}
                <span class="absolute left-4 top-4 bottom-4 w-px bg-gray-200" aria-hidden="true"></span>

                @foreach($steps as $i => $step)
                <li class="relative flex items-center gap-4">
                    <span class="relative z-10 flex items-center justify-center w-8 h-8 rounded-full bg-gray-400 text-white text-sm font-semibold shrink-0 ring-4 ring-white">
                        {{ $i + 1 }}
                    </span>
                    <p class="text-sm text-gray-600 leading-snug">
                        <span class="font-semibold text-gray-900">{{ $step['title'] }}.</span>
                        {!! $step['body'] !!}
                    </p>
                </li>
                @endforeach
            </ol>

            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1">
                <p class="text-xs text-gray-500 flex items-start gap-2">
                    <i class="fas fa-paperclip text-gray-400 mt-0.5 w-3.5 text-center shrink-0"></i>
                    <span>Documents must be <strong>PDF, JPG or PNG</strong>, up to <strong>10 MB</strong> each.</span>
                </p>
                <p class="text-xs text-gray-500 flex items-start gap-2">
                    <i class="fas fa-bars-progress text-gray-400 mt-0.5 w-3.5 text-center shrink-0"></i>
                    <span>The progress bar on every application shows the stage it has reached.</span>
                </p>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- RIGHT — PERMIT TYPE SELECTION --}}
        {{-- ============================================================== --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col h-full">
            <h3 class="text-base font-semibold text-gray-900 mb-2">
                <i class="fas fa-list-check text-blue-500 text-2xl mr-1.5"></i> Select a permit type
            </h3>

            <div class="flex-1 flex flex-col justify-center gap-2.5">
                @foreach($permitTypes as $pt)
                @php $meta = $typeMeta[$pt->code] ?? ['icon' => 'fa-file-lines', 'color' => 'bg-gray-600', 'desc' => '']; @endphp
                <a href="{{ route('online.apply', ['type' => $pt->code]) }}"
                   class="group flex items-center gap-4 p-3.5 bg-white rounded-lg border border-gray-200 hover:border-blue-400 hover:bg-blue-50/40 hover:shadow-sm transition">
                    <span class="flex items-center justify-center w-10 h-10 rounded-lg {{ $meta['color'] }} text-white text-base shrink-0">
                        <i class="fas {{ $meta['icon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-sm font-semibold text-gray-900 leading-tight truncate">{{ $pt->name }}</h4>
                        <p class="text-xs text-gray-500 leading-snug mt-0.5 line-clamp-1">{{ $meta['desc'] }}</p>
                    </div>
                    <i class="fas fa-chevron-right text-xs text-gray-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition shrink-0"></i>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
