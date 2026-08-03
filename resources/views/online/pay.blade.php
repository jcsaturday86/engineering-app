@extends('layouts.app')

@section('title', 'Pay Online')

@section('breadcrumbs')
    <a href="{{ route('online.applications') }}" class="text-gray-500 hover:text-gray-700">My Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Pay Online</span>
@endsection

@section('content')
<div class="space-y-6 max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900">{{ $application->application_number }}</h3>
        @if($application->project_title ?? null)
        <p class="text-sm text-gray-500 mt-0.5">{{ $application->project_title }}</p>
        @endif
    </div>

    {{-- Payment card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-100 rounded-full mb-4">
            <i class="fas fa-credit-card text-2xl text-blue-600"></i>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">Pay Online via LandBank Link.Biz</h2>

        @if($billing)
        <p class="text-sm text-gray-500 mt-2">Amount Due</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">&#8369;{{ number_format($billing->total_amount, 2) }}</p>
        @endif

        <div class="mt-6 flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg text-left">
            <i class="fas fa-circle-info text-amber-500 mt-0.5"></i>
            <p class="text-sm text-amber-800">
                Online payment is coming soon. In the meantime, please settle this billing at the Treasury Office.
            </p>
        </div>

        <a href="{{ route('online.applications') }}" class="inline-flex items-center gap-2 mt-6 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
            <i class="fas fa-arrow-left"></i> Back to My Applications
        </a>
    </div>
</div>
@endsection
