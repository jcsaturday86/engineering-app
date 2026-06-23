@extends('layouts.app')

@section('title', 'Track Application')

@section('breadcrumbs')
    <a href="{{ route('online.dashboard') }}" class="text-gray-500 hover:text-gray-700">My Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Track {{ $application->application_number }}</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6 text-center">
        <h2 class="text-lg font-bold text-gray-900 font-mono">{{ $application->application_number }}</h2>
        <p class="text-sm text-gray-500">{{ $application->permitType?->name }} — {{ $application->project_title }}</p>
        <span class="inline-flex items-center px-3 py-1 mt-3 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
            {{ ucfirst(str_replace('_', ' ', $application->status)) }}
        </span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-6">Application Progress</h3>
        <div class="relative">
            @php
                $statusOrder = ['draft','submitted','zoning_assessed','engineering_assessed','billed','paid','permit_generated','released'];
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
                        @if($isCurrent) bg-blue-600 text-white
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
                    @if($step['date'])
                    <p class="text-xs text-gray-400 mt-0.5">{{ $step['date']->format('M d, Y h:i A') }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="text-center">
        <a href="{{ route('online.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-1"></i> Back to My Applications
        </a>
    </div>
</div>
@endsection
