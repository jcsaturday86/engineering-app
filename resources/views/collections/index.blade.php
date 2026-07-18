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
    </div>

    {{-- Barcode Scan / Search --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" action="{{ route('collections.index') }}" autocomplete="off">
            <label class="block text-xs font-medium text-gray-500 mb-1">
                <i class="fas fa-barcode mr-1"></i> Scan Assessment Barcode / Search Application
            </label>
            <div class="flex gap-2">
                <input type="text" name="search" value="{{ $search ?? '' }}" autofocus
                    placeholder="Scan barcode or type application no. / applicant name (e.g. BP-2026-06-00014)"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search"></i> Search
                </button>
                @if(!empty($search))
                <a href="{{ route('collections.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                    Clear
                </a>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-1">Scanning the barcode on a printed assessment opens the payment form directly.</p>
        </form>
    </div>

    @if(!empty($search) && $forPayment->isEmpty())
    <div class="flex items-center gap-2 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        <i class="fas fa-circle-exclamation"></i>
        <span>No application awaiting payment matches "<strong>{{ $search }}</strong>".</span>
    </div>
    @endif

    {{-- For Payment --}}
    @if($forPayment->count())
    <div class="bg-white rounded-xl border border-orange-200 overflow-hidden">
        <div class="px-4 py-3 bg-orange-50 border-b border-orange-200">
            <h3 class="text-sm font-semibold text-orange-800">
                <i class="fas fa-exclamation-circle mr-1"></i> Awaiting Payment ({{ $forPayment->count() }})
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">App No.</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Type</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Applicant</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Amount Due</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($forPayment as $app)
                    <tr class="hover:bg-orange-50/30">
                        <td class="px-4 py-3 font-mono text-sm text-blue-600">{{ $app->application_number }}</td>
                        <td class="px-4 py-3">
                            @php
                                $typeBadge = match($app->getPermitTypeCode()) {
                                    'BP' => 'bg-blue-100 text-blue-700',
                                    'OP' => 'bg-indigo-100 text-indigo-700',
                                    'DP' => 'bg-red-100 text-red-700',
                                    'SGP' => 'bg-purple-100 text-purple-700',
                                    'FP' => 'bg-teal-100 text-teal-700',
                                    'AI' => 'bg-cyan-100 text-cyan-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeBadge }}">
                                {{ $app->getPermitTypeCode() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-900">{{ $app->owner_name ?? ($app->applicant_last_name . ', ' . $app->applicant_first_name) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($app->billings->where('status','unpaid')->first()?->total_amount ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $app->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('create-collections')
                            @php
                                $payRoute = match($app->getPermitTypeCode()) {
                                    'OP' => route('collections.create.op', $app),
                                    'DP' => route('collections.create.dp', $app),
                                    'SGP' => route('collections.create.sgp', $app),
                                    'FP' => route('collections.create.fp', $app),
                                    'AI' => route('collections.create.ai', $app),
                                    default => route('collections.create', $app),
                                };
                            @endphp
                            <a href="{{ $payRoute }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-cash-register"></i> Collect Payment
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Payment History --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="text-sm font-semibold text-gray-700">
                <i class="fas fa-receipt mr-1"></i> My Collections
            </h3>
            <form method="GET" action="{{ route('collections.index') }}" class="flex items-center gap-2">
                <label for="month" class="text-xs font-medium text-gray-500">Month</label>
                <input type="month" id="month" name="month" value="{{ $month }}"
                    onchange="this.form.submit()"
                    class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </form>
        </div>
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
                            <a href="{{ route('collections.receipt', $collection) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition" title="Print Receipt">
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
