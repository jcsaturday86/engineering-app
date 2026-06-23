@extends('layouts.app')

@section('title', $feeType->name . ' - Fee Schedule')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700">Settings</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.fees') }}" class="text-gray-500 hover:text-gray-700">Fees</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $feeType->name }}</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Back link --}}
    <div>
        <a href="{{ route('settings.fees') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left text-xs"></i>
            Back to Fee Categories
        </a>
    </div>

    {{-- Fee Type Details Card --}}
    <div x-data="{ editing: false }" class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $feeType->name }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $feeType->feeCategory->permitType->name }} &rarr; {{ $feeType->feeCategory->name }}
                </p>
            </div>
            <button @click="editing = !editing" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas" :class="editing ? 'fa-times' : 'fa-pencil-alt'"></i>
                <span x-text="editing ? 'Cancel' : 'Edit'"></span>
            </button>
        </div>

        {{-- Display Mode --}}
        <div x-show="!editing" class="px-6 py-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="block text-xs font-medium text-gray-500 mb-1">Code</span>
                    <span class="text-gray-900 font-mono">{{ $feeType->code }}</span>
                </div>
                <div>
                    <span class="block text-xs font-medium text-gray-500 mb-1">Name</span>
                    <span class="text-gray-900">{{ $feeType->name }}</span>
                </div>
                <div>
                    <span class="block text-xs font-medium text-gray-500 mb-1">Computation Method</span>
                    <span class="text-gray-900">{{ ucfirst($feeType->computation_method ?? 'Not set') }}</span>
                </div>
                <div>
                    <span class="block text-xs font-medium text-gray-500 mb-1">Description</span>
                    <span class="text-gray-900">{{ $feeType->description ?? '-' }}</span>
                </div>
            </div>
            <div class="flex items-center gap-6 mt-3 text-sm">
                <span class="flex items-center gap-1.5">
                    <i class="fas {{ $feeType->has_excess ? 'fa-check-circle text-green-500' : 'fa-times-circle text-gray-300' }}"></i>
                    Has Excess
                </span>
                <span class="flex items-center gap-1.5">
                    <i class="fas {{ $feeType->has_minimum ? 'fa-check-circle text-green-500' : 'fa-times-circle text-gray-300' }}"></i>
                    Has Minimum
                </span>
                <span class="flex items-center gap-1.5">
                    <i class="fas {{ $feeType->has_maximum ? 'fa-check-circle text-green-500' : 'fa-times-circle text-gray-300' }}"></i>
                    Has Maximum
                </span>
            </div>
        </div>

        {{-- Edit Mode --}}
        <div x-show="editing" x-cloak class="px-6 py-4">
            <form action="{{ route('settings.fees.type.update', $feeType) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $feeType->code) }}" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $feeType->name) }}" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Computation Method</label>
                        <select name="computation_method" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">-- Select --</option>
                            @foreach(['fixed' => 'Fixed', 'per_unit' => 'Per Unit', 'percentage' => 'Percentage', 'range' => 'Range-based', 'tiered' => 'Tiered', 'formula' => 'Formula'] as $val => $label)
                                <option value="{{ $val }}" {{ old('computation_method', $feeType->computation_method) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="description" value="{{ old('description', $feeType->description) }}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div class="flex items-center gap-6 mt-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="has_excess" value="1" {{ old('has_excess', $feeType->has_excess) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        Has Excess
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="has_minimum" value="1" {{ old('has_minimum', $feeType->has_minimum) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        Has Minimum
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="has_maximum" value="1" {{ old('has_maximum', $feeType->has_maximum) ? 'checked' : '' }} class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        Has Maximum
                    </label>
                </div>
                <div class="mt-4">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                        Update Fee Type
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Fee Schedules Table --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">Fee Schedules</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ $feeType->feeSchedules->count() }} {{ Str::plural('row', $feeType->feeSchedules->count()) }} defined</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">
                        <th class="px-4 py-3">Range From</th>
                        <th class="px-4 py-3">Range To</th>
                        <th class="px-4 py-3">Fixed Fee</th>
                        <th class="px-4 py-3">Fee/Unit</th>
                        <th class="px-4 py-3">%</th>
                        <th class="px-4 py-3">Excess Threshold</th>
                        <th class="px-4 py-3">Excess Fee</th>
                        <th class="px-4 py-3">Excess Every</th>
                        <th class="px-4 py-3">Min Fee</th>
                        <th class="px-4 py-3">Max Fee</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($feeType->feeSchedules as $schedule)
                        <tr x-data="{ editRow: false }" class="hover:bg-gray-50">
                            {{-- Display mode --}}
                            <template x-if="!editRow">
                                <td class="px-4 py-2.5 text-gray-700" colspan="11">
                                    <div class="flex items-center justify-between">
                                        <div class="grid grid-cols-10 gap-2 flex-1 text-xs">
                                            <span>{{ number_format($schedule->range_from, 2) }}</span>
                                            <span>{{ number_format($schedule->range_to, 2) }}</span>
                                            <span>{{ number_format($schedule->fixed_fee, 2) }}</span>
                                            <span>{{ number_format($schedule->fee_per_unit, 4) }}</span>
                                            <span>{{ $schedule->percentage ? number_format($schedule->percentage, 4) . '%' : '-' }}</span>
                                            <span>{{ number_format($schedule->excess_threshold, 2) }}</span>
                                            <span>{{ number_format($schedule->excess_fee, 4) }}</span>
                                            <span>{{ number_format($schedule->excess_every, 2) }}</span>
                                            <span>{{ number_format($schedule->minimum_fee, 2) }}</span>
                                            <span>{{ number_format($schedule->maximum_fee, 2) }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 ml-4 shrink-0">
                                            <button @click="editRow = true" class="text-primary-600 hover:text-primary-800" title="Edit">
                                                <i class="fas fa-pencil-alt text-xs"></i>
                                            </button>
                                            <form action="{{ route('settings.fees.schedule.destroy', $schedule) }}" method="POST" onsubmit="return confirm('Delete this schedule row?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700" title="Delete">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </template>

                            {{-- Edit mode --}}
                            <template x-if="editRow">
                                <td class="px-4 py-2" colspan="11">
                                    <form action="{{ route('settings.fees.schedule.update', $schedule) }}" method="POST" class="flex items-center gap-1">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="range_from" value="{{ $schedule->range_from }}" step="0.01" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="From">
                                        <input type="number" name="range_to" value="{{ $schedule->range_to }}" step="0.01" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="To">
                                        <input type="number" name="fixed_fee" value="{{ $schedule->fixed_fee }}" step="0.01" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="Fixed">
                                        <input type="number" name="fee_per_unit" value="{{ $schedule->fee_per_unit }}" step="0.0001" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="Per Unit">
                                        <input type="number" name="percentage" value="{{ $schedule->percentage }}" step="0.0001" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="%">
                                        <input type="number" name="excess_threshold" value="{{ $schedule->excess_threshold }}" step="0.01" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="Threshold">
                                        <input type="number" name="excess_fee" value="{{ $schedule->excess_fee }}" step="0.0001" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="Excess">
                                        <input type="number" name="excess_every" value="{{ $schedule->excess_every }}" step="0.01" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="Every">
                                        <input type="number" name="minimum_fee" value="{{ $schedule->minimum_fee }}" step="0.01" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="Min">
                                        <input type="number" name="maximum_fee" value="{{ $schedule->maximum_fee }}" step="0.01" class="w-20 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary-500" placeholder="Max">
                                        <button type="submit" class="px-2 py-1 text-xs font-medium text-white bg-primary-600 rounded hover:bg-primary-700">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" @click="editRow = false" class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded hover:bg-gray-200">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                            </template>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-6 text-center text-sm text-gray-400 italic">
                                No fee schedules defined yet. Add one below.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Add Row Form --}}
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="11" class="px-4 py-3">
                            <form action="{{ route('settings.fees.schedule.store', $feeType) }}" method="POST" class="flex items-end gap-1 flex-wrap">
                                @csrf
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">Range From</label>
                                    <input type="number" name="range_from" step="0.01" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">Range To</label>
                                    <input type="number" name="range_to" step="0.01" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">Fixed Fee</label>
                                    <input type="number" name="fixed_fee" step="0.01" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">Fee/Unit</label>
                                    <input type="number" name="fee_per_unit" step="0.0001" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0.0000">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">%</label>
                                    <input type="number" name="percentage" step="0.0001" class="w-20 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">Excess Threshold</label>
                                    <input type="number" name="excess_threshold" step="0.01" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">Excess Fee</label>
                                    <input type="number" name="excess_fee" step="0.0001" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0.0000">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">Excess Every</label>
                                    <input type="number" name="excess_every" step="0.01" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">Min Fee</label>
                                    <input type="number" name="minimum_fee" step="0.01" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0.00">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-0.5">Max Fee</label>
                                    <input type="number" name="maximum_fee" step="0.01" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-primary-500" placeholder="0.00">
                                </div>
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                    <i class="fas fa-plus mr-1"></i> Add Row
                                </button>
                            </form>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
