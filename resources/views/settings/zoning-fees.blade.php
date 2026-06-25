@extends('layouts.app')

@section('title', 'Zoning Fee Settings')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700">Settings</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Zoning Fees</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Land Use & Zoning Fee Settings</h2>
            <p class="text-sm text-gray-500 mt-1">Manage locational clearance and zoning certification fee schedules by occupancy group.</p>
        </div>
    </div>

    {{-- Zoning Certification Fee --}}
    @if($certFee)
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-green-600 text-white text-xs font-bold rounded-full mr-2"><i class="fas fa-certificate text-xs"></i></span>
            Zoning Certification Fee
        </h3>
        <form action="{{ route('settings.zoning-fees.updateCert', $certFee) }}" method="POST" class="flex items-end gap-3" autocomplete="off">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fixed Fee (all sub-groups)</label>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">&#8369;</span>
                    <input type="number" name="amount" value="{{ $certFee->amount }}" step="0.01" min="0"
                        class="w-40 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-save text-xs"></i> Update
            </button>
        </form>
    </div>
    @endif

    {{-- Locational Clearance Fees by Group --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4 flex items-center">
            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-600 text-white text-xs font-bold rounded-full mr-2"><i class="fas fa-map-marked-alt text-xs"></i></span>
            Locational Clearance Fees
        </h3>

        <div class="space-y-4">
            @foreach($groups as $group)
            <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                <button @click="open = !open" type="button"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">{{ $group->code }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $group->name }}</span>
                        <span class="text-xs text-gray-400">({{ $group->subGroups->count() }} sub-groups)</span>
                    </div>
                    <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" :class="open && 'rotate-180'"></i>
                </button>

                <div x-show="open" x-cloak class="divide-y divide-gray-100">
                    @foreach($group->subGroups as $subGroup)
                    @php $schedules = $lcSchedules[$subGroup->id] ?? collect(); @endphp
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-800">
                                <span class="text-xs text-gray-400 mr-1">#{{ $subGroup->id }}</span>
                                {{ $subGroup->name }}
                            </h4>
                            <span class="text-xs text-gray-400">{{ $schedules->count() }} row(s)</span>
                        </div>

                        @if($schedules->count())
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="text-left px-3 py-2 font-medium text-gray-500">Range From</th>
                                        <th class="text-left px-3 py-2 font-medium text-gray-500">Range To</th>
                                        <th class="text-right px-3 py-2 font-medium text-gray-500">Amount</th>
                                        <th class="text-right px-3 py-2 font-medium text-gray-500">Excess Of</th>
                                        <th class="text-right px-3 py-2 font-medium text-gray-500">Rate</th>
                                        <th class="text-right px-3 py-2 font-medium text-gray-500">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($schedules as $schedule)
                                    <tr x-data="{ editing: false }" class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-700" x-show="!editing">&#8369;{{ number_format($schedule->range_from, 2) }}</td>
                                        <td class="px-3 py-2 text-gray-700" x-show="!editing">&#8369;{{ number_format($schedule->range_to, 2) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-900" x-show="!editing">&#8369;{{ number_format($schedule->amount, 2) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600" x-show="!editing">{{ $schedule->excess_of > 0 ? '₱' . number_format($schedule->excess_of, 2) : '—' }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600" x-show="!editing">{{ $schedule->percentage > 0 ? $schedule->percentage : '—' }}</td>
                                        <td class="px-3 py-2 text-right" x-show="!editing">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="editing = true" class="text-blue-500 hover:text-blue-700" title="Edit">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <form action="{{ route('settings.zoning-fees.destroy', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Delete this row?')" autocomplete="off">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Delete">
                                                        <i class="fas fa-trash text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        <template x-if="editing">
                                            <td class="px-3 py-2" colspan="6">
                                                <form action="{{ route('settings.zoning-fees.update', $schedule) }}" method="POST" class="flex items-center gap-1 flex-wrap" autocomplete="off">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="number" name="range_from" value="{{ $schedule->range_from }}" step="0.01" class="w-28 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500" placeholder="From">
                                                    <input type="number" name="range_to" value="{{ $schedule->range_to }}" step="0.01" class="w-28 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500" placeholder="To">
                                                    <input type="number" name="amount" value="{{ $schedule->amount }}" step="0.01" class="w-24 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500" placeholder="Amount">
                                                    <input type="number" name="excess_of" value="{{ $schedule->excess_of }}" step="0.01" class="w-28 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500" placeholder="Excess Of">
                                                    <input type="number" name="percentage" value="{{ $schedule->percentage }}" step="0.000001" class="w-24 px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500" placeholder="Rate">
                                                    <button type="submit" class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" @click="editing = false" class="px-2 py-1 bg-gray-400 text-white text-xs rounded hover:bg-gray-500">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </template>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-xs text-gray-400 italic">No fee schedules configured for this sub-group.</p>
                        @endif

                        {{-- Add Row --}}
                        <div x-data="{ adding: false }" class="mt-2">
                            <button @click="adding = !adding" class="text-xs text-blue-600 hover:text-blue-800">
                                <i class="fas fa-plus mr-1"></i> Add Row
                            </button>
                            <div x-show="adding" x-cloak class="mt-2">
                                <form action="{{ route('settings.zoning-fees.store', $subGroup) }}" method="POST" class="flex items-end gap-1 flex-wrap" autocomplete="off">
                                    @csrf
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Range From</label>
                                        <input type="number" name="range_from" step="0.01" required class="w-28 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500" placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Range To</label>
                                        <input type="number" name="range_to" step="0.01" required class="w-28 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500" placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Amount</label>
                                        <input type="number" name="amount" step="0.01" required class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500" placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Excess Of</label>
                                        <input type="number" name="excess_of" step="0.01" class="w-28 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500" placeholder="0.00">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Rate</label>
                                        <input type="number" name="percentage" step="0.000001" class="w-24 px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-blue-500" placeholder="0.001">
                                    </div>
                                    <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-plus mr-1"></i> Add
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
