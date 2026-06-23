@extends('layouts.app')

@section('title', 'Assess Application')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('assessments.index') }}" class="text-gray-500 hover:text-gray-700">Assessments</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">Assess {{ $application->application_number }}</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Assess Application</h2>
        <a href="{{ route('assessments.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    {{-- Application Summary Card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Application Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-500">Application Number</p>
                <p class="text-sm font-mono font-semibold text-gray-900">{{ $application->application_number }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Applicant Name</p>
                <p class="text-sm font-semibold text-gray-900">{{ $application->applicant_last_name }}, {{ $application->applicant_first_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Project Title</p>
                <p class="text-sm font-semibold text-gray-900">{{ $application->project_title ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Estimated Cost</p>
                <p class="text-sm font-semibold text-gray-900">&#8369;{{ number_format($application->total_estimated_cost ?? 0, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Scope of Work</p>
                <p class="text-sm font-semibold text-gray-900">{{ $application->scope_of_work ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">No. of Storeys</p>
                <p class="text-sm font-semibold text-gray-900">{{ $application->number_of_storeys ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Floor Area</p>
                <p class="text-sm font-semibold text-gray-900">{{ $application->total_floor_area ? number_format($application->total_floor_area, 2) . ' sq.m.' : '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Existing Assessment Items --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">Assessment Items</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Fee Code</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Description</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Qty</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Unit Fee</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Inspection Fee</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($assessmentItems as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-gray-700">{{ $item->fee_code }}</td>
                        <td class="px-4 py-3 text-gray-900">{{ $item->description }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->unit_fee, 2) }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->inspection_fee, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <form action="{{ route('assessments.removeItem', $item) }}" method="POST" class="inline" onsubmit="return confirm('Remove this item?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700" title="Remove">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-receipt text-2xl mb-2"></i>
                            <p>No assessment items yet. Add items below.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($assessmentItems->count())
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-700">Items Subtotal</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">&#8369;{{ number_format($assessmentItems->sum('amount'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Add Fee Item Form --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6" x-data="{
        selectedCategory: '',
        feeTypes: @js($feeCategories->mapWithKeys(fn($c) => [$c->id => $c->feeTypes->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'code' => $t->code])])),
        quantity: 1,
        unitFee: 0,
        get filteredFeeTypes() {
            return this.selectedCategory ? (this.feeTypes[this.selectedCategory] || []) : [];
        },
        get amount() {
            return (this.quantity * this.unitFee).toFixed(2);
        }
    }">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Add Fee Item</h3>
        <form action="{{ route('assessments.addItem', $application) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Fee Category --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Fee Category</label>
                    <select name="fee_category_id" x-model="selectedCategory" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Category --</option>
                        @foreach($feeCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Fee Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Fee Type</label>
                    <select name="fee_type_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Fee Type --</option>
                        <template x-for="ft in filteredFeeTypes" :key="ft.id">
                            <option :value="ft.id" x-text="ft.code + ' - ' + ft.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Quantity --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Quantity</label>
                    <input type="number" name="quantity" x-model.number="quantity" step="0.01" min="0" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Unit Fee --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Unit Fee</label>
                    <input type="number" name="unit_fee" x-model.number="unitFee" step="0.01" min="0" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                {{-- Computed Amount --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Computed Amount</label>
                    <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 font-medium">
                        &#8369;<span x-text="parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">0.00</span>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-end">
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Totals Section --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Assessment Totals</h3>
        <div class="space-y-3 max-w-sm ml-auto">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Items Subtotal</span>
                <span class="font-medium text-gray-900">&#8369;{{ number_format($assessmentItems->sum('amount'), 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Filing Fee</span>
                <span class="font-medium text-gray-900">&#8369;{{ number_format($assessment->filing_fee ?? 0, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Processing Fee</span>
                <span class="font-medium text-gray-900">&#8369;{{ number_format($assessment->processing_fee ?? 0, 2) }}</span>
            </div>
            <hr class="border-gray-200">
            <div class="flex justify-between text-base">
                <span class="font-semibold text-gray-900">Grand Total</span>
                <span class="font-bold text-gray-900">&#8369;{{ number_format($assessment->total_amount ?? 0, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Finalize Button --}}
    @if($assessment && $assessment->status !== 'finalized' && $assessmentItems->count())
    <div class="flex justify-end">
        <form action="{{ route('assessments.finalize', $application) }}" method="POST" onsubmit="return confirm('Are you sure you want to finalize this assessment? This action cannot be undone.');">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition shadow-sm">
                <i class="fas fa-check-circle"></i> Finalize Assessment
            </button>
        </form>
    </div>
    @endif
</div>
@endsection
