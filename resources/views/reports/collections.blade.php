@extends('layouts.app')

@section('title', 'Collections Report')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-500">Reports</span>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Collections</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">
                <i class="fas fa-money-bill-wave text-gray-400 mr-2"></i>Generate Collections Report
            </h3>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('reports.generate') }}" target="_blank" class="space-y-6">
                @csrf
                <input type="hidden" name="report_type" value="collections">

                {{-- Date Range --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                        <input type="date" id="date_from" name="date_from" value="{{ old('date_from') }}"
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                        <input type="date" id="date_to" name="date_to" value="{{ old('date_to') }}"
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Payment Mode --}}
                <div>
                    <label for="payment_mode" class="block text-sm font-medium text-gray-700">Payment Mode</label>
                    <select id="payment_mode" name="payment_mode"
                        class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All</option>
                        <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="check" {{ old('payment_mode') === 'check' ? 'selected' : '' }}>Check</option>
                        <option value="online" {{ old('payment_mode') === 'online' ? 'selected' : '' }}>Online</option>
                    </select>
                </div>

                {{-- Format --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center">
                            <input type="radio" name="format" value="pdf" checked
                                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="fas fa-file-pdf text-red-500 mr-1"></i>PDF
                            </span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="format" value="excel" {{ old('format') === 'excel' ? 'checked' : '' }}
                                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="fas fa-file-excel text-green-500 mr-1"></i>Excel
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="pt-2">
                    <button type="submit" class="w-full sm:w-auto flex justify-center items-center gap-2 py-2.5 px-6 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        <i class="fas fa-cog"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
