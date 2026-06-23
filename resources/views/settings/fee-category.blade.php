@extends('layouts.app')

@section('title', $feeCategory->name)

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.fees') }}" class="text-gray-500 hover:text-gray-700">Fee Schedules</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $feeCategory->name }}</span>
@endsection

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $feeCategory->name }}</h2>
            <p class="text-sm text-gray-500">{{ $feeCategory->permitType?->name }} &middot; {{ $feeCategory->code }}</p>
        </div>
        <a href="{{ route('settings.fees') }}" class="text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    {{-- Fee Types Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Code</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Name</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Method</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-500">Excess</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-500">Min</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-500">Max</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-500">Schedules</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($feeCategory->feeTypes as $feeType)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $feeType->code }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $feeType->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $feeType->computation_method ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($feeType->has_excess) <i class="fas fa-check text-green-500 text-xs"></i>
                            @else <i class="fas fa-minus text-gray-300 text-xs"></i> @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($feeType->has_minimum) <i class="fas fa-check text-green-500 text-xs"></i>
                            @else <i class="fas fa-minus text-gray-300 text-xs"></i> @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($feeType->has_maximum) <i class="fas fa-check text-green-500 text-xs"></i>
                            @else <i class="fas fa-minus text-gray-300 text-xs"></i> @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $feeType->fee_schedules_count > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $feeType->fee_schedules_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('settings.fees.type', $feeType) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                <i class="fas fa-sliders-h mr-1"></i>Manage
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400">No fee types defined.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add Fee Type --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6" x-data="{ showForm: false }">
        <button @click="showForm = !showForm" class="text-sm font-medium text-blue-600 hover:text-blue-800">
            <i class="fas fa-plus mr-1"></i> Add Fee Type
        </button>
        <div x-show="showForm" x-cloak class="mt-4">
            <form action="{{ route('settings.fees.type.store') }}" method="POST">
                @csrf
                <input type="hidden" name="fee_category_id" value="{{ $feeCategory->id }}">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Code *</label>
                        <input type="text" name="code" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="e.g. BF-001">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Name *</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Computation Method</label>
                        <select name="computation_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="fixed">Fixed</option>
                            <option value="per_unit">Per Unit</option>
                            <option value="range_based">Range-based</option>
                            <option value="cumulative_range">Cumulative Range</option>
                            <option value="percentage">Percentage</option>
                            <option value="formula">Formula</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-6 mt-4">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="has_excess" value="1" class="rounded border-gray-300"> Has Excess</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="has_minimum" value="1" class="rounded border-gray-300"> Has Min</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="has_maximum" value="1" class="rounded border-gray-300"> Has Max</label>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Save</button>
                    <button type="button" @click="showForm = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
