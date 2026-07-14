@extends('layouts.app')

@section('title', 'Assessment Summary')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('assessments.index') }}" class="text-gray-500 hover:text-gray-700">Assessments</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Summary for {{ $application->application_number }}</span>
@endsection

@section('content')
@php
    $isOp = $isOp ?? false;
    $isDp = $isDp ?? false;
    $printRoute = $isDp ? route('assessments.print.dp', $application) : ($isOp ? route('assessments.print.op', $application) : route('assessments.print', $application));
    $backRoute = $isDp ? route('assessments.assess.dp', $application) : ($isOp ? route('assessments.assess.op', $application) : route('assessments.assess', $application));
@endphp
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Assessment Summary</h2>
        <div class="flex gap-2">
            <a href="{{ $printRoute }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition" target="_blank">
                <i class="fas fa-print"></i> Print
            </a>
            <a href="{{ $backRoute }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    {{-- Application Info Card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Application Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Application Number</p>
                <p class="text-sm font-mono font-semibold text-gray-900">{{ $application->application_number }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Applicant</p>
                <p class="text-sm font-semibold text-gray-900">{{ $application->applicant_last_name }}, {{ $application->applicant_first_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Project Title</p>
                <p class="text-sm font-semibold text-gray-900">{{ $application->project_title ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Fee Categories with Items --}}
    @foreach($assessmentItems as $categoryName => $items)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">{{ $categoryName }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Fee Code</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Description</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Qty</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Unit Fee</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-gray-700">{{ $item->fee_code }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->unit_fee, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700">Category Subtotal</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">&#8369;{{ number_format($items->sum('amount'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endforeach

    {{-- Grand Total Section --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Total Summary</h3>
        <div class="space-y-3 max-w-sm ml-auto">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Items Subtotal</span>
                <span class="font-medium text-gray-900">&#8369;{{ number_format($assessmentItems->flatten()->sum('amount'), 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Filing Fee</span>
                <span class="font-medium text-gray-900">&#8369;{{ number_format($assessment->filing_fee ?? 0, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Processing Fee</span>
                <span class="font-medium text-gray-900">&#8369;{{ number_format($assessment->processing_fee ?? 0, 2) }}</span>
            </div>
            <hr class="border-gray-200">
            <div class="flex justify-between text-base">
                <span class="font-semibold text-gray-900">Grand Total</span>
                <span class="font-bold text-gray-900">&#8369;{{ number_format($assessment->total_amount ?? 0, 2) }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
