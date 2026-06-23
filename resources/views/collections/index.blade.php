@extends('layouts.app')

@section('title', 'Collections')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Collections</span>
@endsection

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Collections</h2>
        <div class="flex gap-2">
            <a href="{{ route('collections.void') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded-lg hover:bg-red-100 border border-red-200 transition">
                <i class="fas fa-ban"></i> Void Collection
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">OR Number</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">OR Date</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Application No.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Paid By</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Payment Mode</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Collected By</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($collections as $collection)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="font-mono font-medium text-gray-900">{{ $collection->or_number }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ \Carbon\Carbon::parse($collection->or_date)->format('M d, Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('applications.show', $collection->application) }}" class="font-mono text-blue-600 hover:text-blue-800 font-medium">
                                {{ $collection->application->application_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-900">{{ $collection->paid_by }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">
                            &#8369;{{ number_format($collection->amount_received, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $modeColors = [
                                    'cash' => 'bg-green-100 text-green-700',
                                    'check' => 'bg-blue-100 text-blue-700',
                                    'online' => 'bg-purple-100 text-purple-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $modeColors[$collection->payment_mode] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($collection->payment_mode) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $collection->collectedBy->full_name ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-700',
                                    'voided' => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$collection->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($collection->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('collections.receipt', $collection) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition" title="Print Receipt">
                                <i class="fas fa-print"></i> Receipt
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-400">
                            <i class="fas fa-receipt text-3xl mb-3"></i>
                            <p>No collections found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($collections->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $collections->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
