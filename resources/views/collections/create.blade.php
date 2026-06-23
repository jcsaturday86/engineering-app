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
<div class="max-w-3xl mx-auto space-y-6">
    {{-- Billing Summary --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">Billing Summary</h3>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-medium text-gray-500">Application No.</p>
                    <p class="text-sm font-mono font-medium text-gray-900 mt-0.5">{{ $application->application_number }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Applicant</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $application->applicant_last_name }}, {{ $application->applicant_first_name }}</p>
                </div>
            </div>

            {{-- Billing Items Table --}}
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Description</th>
                            <th class="text-right px-4 py-2.5 font-medium text-gray-500">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($billing->billingItems as $item)
                        <tr>
                            <td class="px-4 py-2.5 text-gray-700">{{ $item->description }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900">Total Amount Due</td>
                            <td class="px-4 py-3 text-right font-bold text-lg text-gray-900">&#8369;{{ number_format($billing->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Payment Form --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" x-data="{ paymentMode: '{{ old('payment_mode', 'cash') }}' }">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">Payment Details</h3>
        </div>
        <form method="POST" action="{{ route('collections.store', $application) }}" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="billing_id" value="{{ $billing->id }}">

            {{-- OR Number --}}
            <div>
                <label for="or_number" class="block text-sm font-medium text-gray-700 mb-1">OR Number <span class="text-red-500">*</span></label>
                <input type="text" name="or_number" id="or_number" value="{{ old('or_number') }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('or_number') border-red-300 @enderror"
                    placeholder="Enter OR number">
                @error('or_number')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Paid By --}}
            <div>
                <label for="paid_by" class="block text-sm font-medium text-gray-700 mb-1">Paid By <span class="text-red-500">*</span></label>
                <input type="text" name="paid_by" id="paid_by" value="{{ old('paid_by', $application->applicant_last_name . ', ' . $application->applicant_first_name) }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('paid_by') border-red-300 @enderror">
                @error('paid_by')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Amount Due (readonly display) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount Due</label>
                <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-semibold text-gray-900">
                    &#8369;{{ number_format($billing->total_amount, 2) }}
                </div>
            </div>

            {{-- Amount Received --}}
            <div>
                <label for="amount_received" class="block text-sm font-medium text-gray-700 mb-1">Amount Received <span class="text-red-500">*</span></label>
                <input type="number" name="amount_received" id="amount_received" value="{{ old('amount_received') }}" step="0.01" min="0" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('amount_received') border-red-300 @enderror"
                    placeholder="0.00">
                @error('amount_received')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Payment Mode --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Mode <span class="text-red-500">*</span></label>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_mode" value="cash" x-model="paymentMode"
                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Cash</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_mode" value="check" x-model="paymentMode"
                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Check</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_mode" value="online" x-model="paymentMode"
                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Online</span>
                    </label>
                </div>
                @error('payment_mode')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Check Fields --}}
            <div x-show="paymentMode === 'check'" x-cloak class="space-y-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="text-sm font-medium text-blue-800">Check Details</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
            <div x-show="paymentMode === 'online'" x-cloak class="p-4 bg-purple-50 border border-purple-200 rounded-lg">
                <h4 class="text-sm font-medium text-purple-800 mb-3">Online Payment Details</h4>
                <div>
                    <label for="online_reference" class="block text-xs font-medium text-gray-600 mb-1">Reference Number</label>
                    <input type="text" name="online_reference" id="online_reference" value="{{ old('online_reference') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Transaction reference number">
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('billing.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
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
