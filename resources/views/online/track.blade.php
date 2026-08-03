@extends('layouts.app')

@section('title', 'Track Application')

@php
    $historyPortal = $portal ?? 'client';
    $historyBackUrl = $backUrl ?? route('online.applications');
    $historyBackLabel = $historyPortal === 'staff' ? 'Back to Application' : 'My Applications';
@endphp

@section('breadcrumbs')
    <a href="{{ $historyBackUrl }}" class="text-gray-500 hover:text-gray-700">{{ $historyBackLabel }}</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Track {{ $application->application_number }}</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    @php
        $statusEnum = \App\Enums\ApplicationStatus::tryFrom($application->status);
        $turnaroundStart = $application->approved_at;
        $turnaroundEnd = $application->permits->sortBy('created_at')->first()?->created_at;
        $turnaroundDays = ($turnaroundStart && $turnaroundEnd) ? (int) floor($turnaroundStart->diffInDays($turnaroundEnd)) : null;
    @endphp

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                    <i class="fas fa-folder-open text-blue-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 font-mono leading-tight">{{ $application->application_number }}</h2>
                    <p class="text-sm text-gray-500">{{ $application->permitType?->name ?? $applicationType }}@if($application->project_title ?? null) — {{ $application->project_title }}@endif</p>
                </div>
            </div>
            <div class="flex items-center gap-4 sm:justify-end">
                @if($turnaroundDays !== null)
                <div class="text-right">
                    <p class="text-[11px] text-gray-400 uppercase tracking-wide font-medium">Turnaround</p>
                    <p class="text-sm font-semibold text-blue-700">
                        {{ $turnaroundDays < 1 ? 'Less than a day' : $turnaroundDays . ' day' . ($turnaroundDays === 1 ? '' : 's') }}
                    </p>
                </div>
                <div class="w-px h-8 bg-gray-200"></div>
                @endif
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $statusEnum?->color() ?? 'bg-blue-100 text-blue-700' }}">
                    {{ $statusEnum?->label() ?? ucfirst(str_replace('_', ' ', $application->status)) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Progress + full history, side by side on large screens --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <i class="fas fa-route text-blue-500"></i> Application Progress
            </h3>
            <div class="relative">
                @php
                    // Derived from this application's own timeline (which already varies
                    // by permit type — BP includes zoning steps, others don't) rather
                    // than a hardcoded list, so the progress markers always line up.
                    $statusOrder = array_column($timeline, 'status');
                    $currentIndex = array_search($application->status, $statusOrder);
                    if ($currentIndex === false) $currentIndex = -1;
                @endphp

                @foreach($timeline as $i => $step)
                @php
                    $stepIndex = array_search($step['status'], $statusOrder);
                    $isComplete = $stepIndex !== false && $stepIndex <= $currentIndex;
                    $isCurrent = $step['status'] === $application->status;
                @endphp
                <div class="flex items-start gap-4 {{ $i < count($timeline) - 1 ? 'pb-6' : '' }}">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0
                            @if($isCurrent) bg-blue-600 text-white ring-4 ring-blue-100
                            @elseif($isComplete) bg-green-500 text-white
                            @else bg-gray-200 text-gray-400 @endif">
                            @if($isComplete && !$isCurrent)
                                <i class="fas fa-check text-xs"></i>
                            @elseif($isCurrent)
                                <i class="fas fa-circle text-xs animate-pulse"></i>
                            @else
                                <i class="fas fa-circle text-xs"></i>
                            @endif
                        </div>
                        @if($i < count($timeline) - 1)
                        <div class="w-0.5 flex-1 {{ $isComplete ? 'bg-green-300' : 'bg-gray-200' }}" style="min-height:20px"></div>
                        @endif
                    </div>
                    <div class="pt-1">
                        <p class="text-sm font-medium {{ $isCurrent ? 'text-blue-700' : ($isComplete ? 'text-green-700' : 'text-gray-400') }}">
                            {{ $step['label'] }}
                        </p>
                        @if($step['date'] ?? null)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $step['date']->format('M d, Y h:i A') }}</p>
                        @endif
                        @if(($isComplete || $isCurrent) && ($processedBy[$step['status']] ?? null))
                            @php $who = $processedBy[$step['status']]; @endphp
                            <p class="text-xs text-gray-500 mt-0.5">
                                <i class="fas fa-user text-gray-400 mr-1"></i>
                                {{ $who['user']?->full_name ?? 'System' }}
                                <span class="text-gray-400">&middot; {{ \App\Support\ApplicationTimeline::processedByLabel($application, $who['user']) }}</span>
                                @if($who['at'])
                                    <span class="text-gray-400">&middot; {{ $who['at']->format('M d, Y h:i A') }}</span>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @include('partials.application-full-history')
    </div>

    <div class="text-center">
        <a href="{{ $historyBackUrl }}" class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-800 font-medium">
            <i class="fas fa-arrow-left"></i> {{ $historyPortal === 'staff' ? 'Back to Application' : 'Back to My Applications' }}
        </a>
    </div>
</div>
@endsection
