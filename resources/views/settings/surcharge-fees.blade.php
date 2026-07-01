@extends('layouts.app')

@section('title', 'Surcharge Fees')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700">Settings</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Surcharge Fees</span>
@endsection

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Surcharge Fees</h2>
        <p class="text-sm text-gray-500 mt-1">
            Penalty surcharges for violations and construction-without-permit infractions. Violations are fixed amounts; construction stages are percentages of the applicable building permit fee.
        </p>
    </div>

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
        class="flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
        <i class="fas fa-check-circle text-green-500"></i>
        {{ session('success') }}
    </div>
    @endif

    @php
    $sectionColors = [
        'red'   => ['bg' => 'bg-red-600',   'badge' => 'bg-red-100 text-red-700'],
        'amber' => ['bg' => 'bg-amber-500', 'badge' => 'bg-amber-100 text-amber-700'],
        'gray'  => ['bg' => 'bg-gray-600',  'badge' => 'bg-gray-100 text-gray-700'],
    ];
    @endphp

    @foreach($sections as $key => $section)
    @php $clr = $sectionColors[$section['color']] ?? $sectionColors['gray']; @endphp

    <div x-data="{ open: true }" class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        {{-- Section header --}}
        <button @click="open = !open" type="button"
            class="w-full flex items-center justify-between px-5 py-4 bg-gray-50 hover:bg-gray-100 transition text-left">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-8 h-8 {{ $clr['bg'] }} text-white text-sm rounded-lg">
                    <i class="fas {{ $section['icon'] }}"></i>
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $section['label'] }}</p>
                    <p class="text-xs text-gray-400">{{ $section['types']->count() }} {{ Str::plural('fee type', $section['types']->count()) }}</p>
                </div>
            </div>
            <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-200" :class="open && 'rotate-180'"></i>
        </button>

        {{-- Section body --}}
        <div x-show="open" x-cloak class="divide-y divide-gray-100">
            @forelse($section['types'] as $feeType)
            <div class="p-5">

                {{-- Fee type header --}}
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800">{{ $feeType->name }}</h4>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono {{ $clr['badge'] }}">{{ $feeType->code }}</span>
                            <span class="text-xs text-gray-400">{{ ucfirst(str_replace('_', ' ', $feeType->computation_method)) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Schedule table --}}
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-right px-3 py-2 font-medium text-gray-500 w-36">Fixed Fee (₱)</th>
                                <th class="text-right px-3 py-2 font-medium text-gray-500 w-36">Percentage (0–1)</th>
                                <th class="text-right px-3 py-2 font-medium text-gray-500 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($feeType->feeSchedules as $schedule)
                            <tr x-data="{ editing: false }" class="hover:bg-gray-50">

                                {{-- Display row --}}
                                <template x-if="!editing">
                                    <td colspan="3" class="px-3 py-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex gap-8 text-gray-700">
                                                <span class="font-medium {{ $schedule->fixed_fee > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                                    {{ $schedule->fixed_fee > 0 ? '₱'.number_format($schedule->fixed_fee, 2) : '—' }}
                                                </span>
                                                <span class="{{ $schedule->percentage > 0 ? 'text-gray-700' : 'text-gray-300' }}">
                                                    {{ $schedule->percentage > 0 ? number_format($schedule->percentage * 100, 0).'%' : '—' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 ml-3 shrink-0">
                                                <button @click="editing = true" class="text-blue-500 hover:text-blue-700" title="Edit">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <form action="{{ route('settings.surcharge-fees.schedule.destroy', $schedule) }}" method="POST"
                                                    onsubmit="return confirm('Delete this row?')" autocomplete="off" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-400 hover:text-red-600" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </template>

                                {{-- Edit row --}}
                                <template x-if="editing">
                                    <td colspan="3" class="px-3 py-2">
                                        <form action="{{ route('settings.surcharge-fees.schedule.update', $schedule) }}"
                                            method="POST" class="flex items-center gap-2" autocomplete="off">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" name="fixed_fee" value="{{ $schedule->fixed_fee }}" step="0.01" min="0"
                                                class="w-36 px-2 py-1 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="Fixed Fee">
                                            <input type="number" name="percentage" value="{{ $schedule->percentage }}" step="0.0001" min="0" max="1"
                                                class="w-36 px-2 py-1 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500" placeholder="% (0–1)">
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
                            @empty
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-center text-xs text-gray-400 italic">
                                    No rates configured. Add a row below.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>

                        {{-- Add row --}}
                        <tfoot x-data="{ adding: false }" class="border-t border-gray-200">
                            <tr>
                                <td colspan="3" class="px-3 py-2 bg-gray-50">
                                    <div x-show="!adding">
                                        <button @click="adding = true" type="button"
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                            <i class="fas fa-plus mr-1"></i> Add Row
                                        </button>
                                    </div>
                                    <div x-show="adding" x-cloak>
                                        <form action="{{ route('settings.surcharge-fees.schedule.store', $feeType) }}"
                                            method="POST" class="flex items-end gap-2" autocomplete="off">
                                            @csrf
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-0.5">Fixed Fee (₱)</label>
                                                <input type="number" name="fixed_fee" step="0.01" min="0"
                                                    class="w-36 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500" placeholder="0.00">
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-0.5">Percentage (0–1)</label>
                                                <input type="number" name="percentage" step="0.0001" min="0" max="1"
                                                    class="w-36 px-2 py-1.5 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500" placeholder="0.0000">
                                            </div>
                                            <div class="flex items-end gap-1">
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">
                                                    <i class="fas fa-plus mr-1"></i> Add
                                                </button>
                                                <button type="button" @click="adding = false"
                                                    class="px-2 py-1.5 bg-gray-400 text-white text-xs rounded-lg hover:bg-gray-500">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @empty
            <div class="px-5 py-6 text-center text-sm text-gray-400">No fee types in this section.</div>
            @endforelse
        </div>
    </div>
    @endforeach
</div>
@endsection
