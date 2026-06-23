@extends('layouts.app')

@section('title', 'Fee Schedules')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700">Settings</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Fee Schedules</span>
@endsection

@section('content')
<div class="space-y-6">
    <h2 class="text-xl font-bold text-gray-900">Fee Schedule Management</h2>

    @foreach($permitTypes as $permitType)
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 rounded-t-xl flex items-center gap-3">
            <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-lg">
                <i class="fas fa-file-alt text-blue-600 text-sm"></i>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">{{ $permitType->name }}</h3>
                <p class="text-xs text-gray-500">{{ $permitType->code }}</p>
            </div>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($permitType->feeCategories as $category)
            <a href="{{ route('settings.fees.category', $category) }}" class="flex items-center justify-between px-6 py-3.5 hover:bg-gray-50 transition">
                <div class="flex items-center gap-3">
                    <i class="fas fa-folder text-yellow-500 text-sm"></i>
                    <div>
                        <span class="text-sm font-medium text-gray-900">{{ $category->name }}</span>
                        <span class="ml-2 text-xs text-gray-400">({{ $category->code }})</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $category->fee_types_count > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $category->fee_types_count }} {{ Str::plural('fee type', $category->fee_types_count) }}
                    </span>
                    <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                </div>
            </a>
            @empty
            <div class="px-6 py-6 text-center text-sm text-gray-400">No fee categories.</div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>
@endsection
