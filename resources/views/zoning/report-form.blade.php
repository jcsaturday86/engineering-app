@extends('layouts.app')

@section('title', 'Zoning Report')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('zoning.index') }}" class="text-gray-500 hover:text-gray-700">Zoning</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Report</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">
                <i class="fas fa-file-alt text-gray-400 mr-2"></i>Generate Zoning Report
            </h3>
        </div>
        <div class="p-6">
            @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <p class="text-sm text-gray-500 mb-6">Select a date range to generate the zoning assessment report.</p>

            <form method="GET" action="{{ route('zoning.report') }}" class="space-y-6" autocomplete="off">
                {{-- Date Range --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Date From <span class="text-red-500">*</span></label>
                        <input type="date" id="date_from" name="date_from" value="{{ old('date_from', now()->startOfYear()->toDateString()) }}" required
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">Date To <span class="text-red-500">*</span></label>
                        <input type="date" id="date_to" name="date_to" value="{{ old('date_to', now()->toDateString()) }}" required
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    @php
                        $statusLabels = [
                            'for_zoning_assessment' => 'Pending',
                            'zoning_assessed' => 'Zoning Assessed',
                            'engineering_assessed' => 'Engineering Assessed',
                            'billed' => 'Billed',
                            'paid' => 'Paid',
                            'permit_generated' => 'Permit Generated',
                            'released' => 'Released',
                        ];
                    @endphp
                    <select id="status" name="status"
                        class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All</option>
                        @foreach($statusLabels as $s => $label)
                            <option value="{{ $s }}" {{ old('status') === $s ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Submit --}}
                <div class="pt-2">
                    <button type="submit" target="_blank" formtarget="_blank" class="w-full sm:w-auto flex justify-center items-center gap-2 py-2.5 px-6 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        <i class="fas fa-cog"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
