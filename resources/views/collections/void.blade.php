@extends('layouts.app')

@section('title', 'Void Collection')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('collections.index') }}" class="text-gray-500 hover:text-gray-700">Collections</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Void</span>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    {{-- Search Card --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">Search Collection</h3>
        </div>
        <div class="p-6">
            <form method="GET" action="{{ route('collections.void') }}" class="flex items-end gap-3">
                <div class="flex-1">
                    <label for="or_number" class="block text-sm font-medium text-gray-700 mb-1">OR Number</label>
                    <input type="text" name="or_number" id="or_number" value="{{ request('or_number') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter OR number to search">
                </div>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
    </div>

    {{-- Not Found Message --}}
    @if(request()->has('or_number') && !$collection)
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-exclamation-triangle text-yellow-500"></i>
        <p class="text-sm text-yellow-700">No collection found with OR number <strong>"{{ request('or_number') }}"</strong></p>
    </div>
    @endif

    {{-- Collection Details & Void Form --}}
    @if($collection)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">Collection Details</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-medium text-gray-500">OR Number</p>
                    <p class="text-sm font-mono font-medium text-gray-900 mt-0.5">{{ $collection->or_number }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">OR Date</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ \Carbon\Carbon::parse($collection->or_date)->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Paid By</p>
                    <p class="text-sm text-gray-900 mt-0.5">{{ $collection->paid_by }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Amount</p>
                    <p class="text-sm font-semibold text-gray-900 mt-0.5">&#8369;{{ number_format($collection->amount_received, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Payment Mode</p>
                    @php
                        $modeColors = [
                            'cash' => 'bg-green-100 text-green-700',
                            'check' => 'bg-blue-100 text-blue-700',
                            'online' => 'bg-purple-100 text-purple-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1 {{ $modeColors[$collection->payment_mode] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($collection->payment_mode) }}
                    </span>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Application No.</p>
                    <p class="text-sm font-mono font-medium text-gray-900 mt-0.5">{{ $collection->application->application_number }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Void Form --}}
    <div class="bg-white rounded-xl border border-red-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-red-200 bg-red-50">
            <h3 class="text-sm font-semibold text-red-800">
                <i class="fas fa-exclamation-triangle mr-1"></i> Void This Collection
            </h3>
        </div>
        <form method="POST" action="{{ route('collections.void.process') }}" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="or_number" value="{{ $collection->or_number }}">

            {{-- Reason --}}
            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Voiding <span class="text-red-500">*</span></label>
                <textarea name="reason" id="reason" rows="3" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('reason') border-red-300 @enderror"
                    placeholder="Provide a detailed reason for voiding this collection">{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Confirmation --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Enter Your Password to Confirm <span class="text-red-500">*</span></label>
                <input type="password" name="password" id="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('password') border-red-300 @enderror"
                    placeholder="Enter your password">
                @error('password')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('collections.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </a>
                <button type="submit"
                    @click="if (!confirm('Are you sure you want to void this collection? This action cannot be undone.')) $event.preventDefault()"
                    class="inline-flex items-center gap-2 px-6 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-ban"></i> Void Collection
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
