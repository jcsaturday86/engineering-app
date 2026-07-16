@extends('layouts.app')

@section('title', 'Record Payment')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('collections.index') }}" class="text-gray-500 hover:text-gray-700">Collections</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Record Payment</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Payment Form --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{
        paymentMode: '{{ old('payment_mode', 'cash') }}',
        amountDue: {{ (float) $billing->total_amount }},
        amountReceived: {{ (float) old('amount_received', 0) }},
        get change() { return Math.max(0, this.amountReceived - this.amountDue); },
        get insufficient() { return this.amountReceived > 0 && this.amountReceived < this.amountDue; }
    }">
        <div class="px-5 py-2.5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">Payment Details</h3>
        </div>
        @php
            $storeRoute = match($application->getPermitTypeCode()) {
                'OP' => route('collections.store.op', $application),
                'DP' => route('collections.store.dp', $application),
                'SGP' => route('collections.store.sgp', $application),
                'FP' => route('collections.store.fp', $application),
                default => route('collections.store', $application),
            };
        @endphp
        <form method="POST" action="{{ $storeRoute }}" class="p-5 space-y-3" autocomplete="off">
            @csrf
            <input type="hidden" name="billing_id" value="{{ $billing->id }}">

            {{-- Row 1: Application context --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <p class="text-xs font-medium text-gray-500">Application No.</p>
                    <p class="text-sm font-mono font-medium text-gray-900">{{ $application->application_number }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Applicant</p>
                    <p class="text-sm text-gray-900">{{ $application->applicant_last_name }}, {{ $application->applicant_first_name }}</p>
                </div>
            </div>

            {{-- Row 2: Identifiers --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="or_number" class="block text-sm font-medium text-gray-700 mb-1">OR Number <span class="text-red-500">*</span></label>
                    <input type="text" name="or_number" id="or_number" value="{{ old('or_number') }}" required autofocus
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('or_number') border-red-300 @enderror"
                        placeholder="Enter OR number">
                    @error('or_number')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="paid_by" class="block text-sm font-medium text-gray-700 mb-1">Paid By <span class="text-red-500">*</span></label>
                    <input type="text" name="paid_by" id="paid_by" value="{{ old('paid_by', $application->applicant_last_name . ', ' . $application->applicant_first_name) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('paid_by') border-red-300 @enderror">
                    @error('paid_by')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Row 3: POS-style amount strip --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount Due</label>
                    <div class="w-full h-[42px] flex items-center px-3 bg-gray-50 border border-gray-200 rounded-lg text-sm font-semibold text-gray-900">
                        &#8369;{{ number_format($billing->total_amount, 2) }}
                    </div>
                </div>
                <div>
                    <label for="amount_received" class="block text-sm font-medium text-gray-700 mb-1">Amount Received <span class="text-red-500">*</span></label>
                    <input type="number" name="amount_received" id="amount_received" value="{{ old('amount_received') }}" step="0.01" min="0" required autofocus
                        x-model.number="amountReceived"
                        class="w-full h-[42px] px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('amount_received') border-red-300 @enderror"
                        placeholder="0.00">
                    @error('amount_received')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div x-show="paymentMode === 'cash'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <span x-show="!insufficient">Change</span>
                        <span x-show="insufficient" class="text-red-600">Short</span>
                    </label>
                    <div class="w-full h-[42px] flex items-center justify-end px-3 rounded-lg text-sm font-bold"
                        :class="insufficient ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'">
                        &#8369;<span x-text="(insufficient ? (amountDue - amountReceived) : change).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">0.00</span>
                    </div>
                </div>
            </div>
            <p x-show="paymentMode === 'cash' && insufficient" x-cloak class="text-xs text-red-500">Amount received is less than the amount due.</p>

            {{-- Row 4: Payment Mode segmented control --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Mode <span class="text-red-500">*</span></label>
                <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden">
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_mode" value="cash" x-model="paymentMode" class="sr-only peer">
                        <span class="block px-4 py-2 text-sm font-medium text-gray-600 peer-checked:bg-blue-600 peer-checked:text-white transition border-r border-gray-300">
                            <i class="fas fa-money-bill-wave mr-1"></i> Cash
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_mode" value="check" x-model="paymentMode" class="sr-only peer">
                        <span class="block px-4 py-2 text-sm font-medium text-gray-600 peer-checked:bg-blue-600 peer-checked:text-white transition border-r border-gray-300">
                            <i class="fas fa-money-check mr-1"></i> Check
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_mode" value="online" x-model="paymentMode" class="sr-only peer">
                        <span class="block px-4 py-2 text-sm font-medium text-gray-600 peer-checked:bg-blue-600 peer-checked:text-white transition">
                            <i class="fas fa-mobile-screen mr-1"></i> Online
                        </span>
                    </label>
                </div>
                @error('payment_mode')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Check Fields --}}
            <div x-show="paymentMode === 'check'" x-cloak class="space-y-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="text-sm font-medium text-blue-800">Check Details</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="bank_name" class="block text-xs font-medium text-gray-600 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Bank name">
                    </div>
                    <div>
                        <label for="check_number" class="block text-xs font-medium text-gray-600 mb-1">Check Number</label>
                        <input type="text" name="check_number" id="check_number" value="{{ old('check_number') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Check no.">
                    </div>
                    <div>
                        <label for="check_date" class="block text-xs font-medium text-gray-600 mb-1">Check Date</label>
                        <input type="date" name="check_date" id="check_date" value="{{ old('check_date') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            {{-- Online Fields --}}
            <div x-show="paymentMode === 'online'" x-cloak class="p-3 bg-purple-50 border border-purple-200 rounded-lg">
                <h4 class="text-sm font-medium text-purple-800 mb-2">Online Payment Details</h4>
                <div>
                    <label for="online_reference" class="block text-xs font-medium text-gray-600 mb-1">Reference Number</label>
                    <input type="text" name="online_reference" id="online_reference" value="{{ old('online_reference') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Transaction reference number">
                </div>
            </div>

            {{-- Actions (sticky so it stays reachable without scrolling) --}}
            <div class="sticky bottom-0 flex items-center justify-end gap-3 pt-3 pb-1 -mx-5 px-5 bg-white border-t border-gray-200">
                <a href="{{ route('collections.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-check-circle"></i> Process Payment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
