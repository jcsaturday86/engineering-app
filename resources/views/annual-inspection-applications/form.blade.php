@extends('layouts.app')

@section('title', $application ? 'Edit Annual Inspection Application' : 'New Annual Inspection Application')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('annual-inspection-applications.index') }}" class="text-gray-500 hover:text-gray-700">Annual Inspection Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $application ? 'Edit ' . $application->application_number : 'New Application' }}</span>
@endsection

@section('content')
@php
    $selectedSubGroup = $application?->applicationOccupancyGroups?->first()?->occupancy_sub_group_id;
    if (old('occupancy_sub_group')) {
        $selectedSubGroup = old('occupancy_sub_group');
    }
@endphp
<form
    method="POST"
    action="{{ $application ? route('annual-inspection-applications.update', $application) : route('annual-inspection-applications.store') }}"
    onsubmit="return validateOccupancy();"
    autocomplete="off"
>
    @csrf
    @if($application)
        @method('PUT')
    @endif

    <div class="space-y-4">
        {{-- Compact Form Header --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold rounded-md bg-teal-600 text-white">AI</span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $application ? 'Edit Application' : 'New Annual Inspection Application' }}
                    </h2>
                    @if($application)
                        <p class="text-xs text-gray-500">{{ $application->application_number }}</p>
                    @endif
                </div>
            </div>
            <p class="text-xs text-gray-400">Fields marked with <span class="text-red-500">*</span> are required</p>
        </div>

        {{-- Validation Error Summary --}}
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4" id="validation-errors">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                <div>
                    <h4 class="text-sm font-semibold text-red-800">Please correct the following errors ({{ $errors->count() }}):</h4>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        {{-- ================================================================== --}}
        {{-- 1. APPLICATION KIND --}}
        {{-- ================================================================== --}}
        <div class="bg-white rounded-xl border {{ $errors->has('application_kind') ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">1</span>Application Kind <span class="text-red-500 ml-1">*</span>
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="application_kind" value="new" required
                        {{ old('application_kind', $application->application_kind ?? 'new') === 'new' ? 'checked' : '' }}
                        {{ $application && $application->status !== 'draft' ? 'disabled' : '' }}
                        class="w-4 h-4 text-teal-600 border-gray-300 focus:ring-teal-500">
                    <span>
                        <span class="block text-sm font-medium text-gray-900">New</span>
                        <span class="block text-xs text-gray-500">First-time permit application for the equipment</span>
                    </span>
                </label>
                <label class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="application_kind" value="yearly"
                        {{ old('application_kind', $application->application_kind ?? 'new') === 'yearly' ? 'checked' : '' }}
                        {{ $application && $application->status !== 'draft' ? 'disabled' : '' }}
                        class="w-4 h-4 text-teal-600 border-gray-300 focus:ring-teal-500">
                    <span>
                        <span class="block text-sm font-medium text-gray-900">Yearly</span>
                        <span class="block text-xs text-gray-500">Annual re-inspection of previously permitted equipment</span>
                    </span>
                </label>
            </div>
            @if($application && $application->status !== 'draft')
                <input type="hidden" name="application_kind" value="{{ $application->application_kind }}">
                <p class="text-xs text-gray-400">Application kind can no longer be changed once submitted.</p>
            @endif
            @error('application_kind')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- ================================================================== --}}
        {{-- 2. OWNER / LESSEE --}}
        {{-- ================================================================== --}}
        <div class="bg-white rounded-xl border {{ $errors->has('owner_name') ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">2</span>Owner / Lessee
            </h3>
            <div>
                <label for="owner_name" class="block text-xs font-medium text-gray-600 mb-1">Name of Owner/Lessee <span class="text-red-500">*</span></label>
                <input type="text" name="owner_name" id="owner_name" required
                    value="{{ old('owner_name', $application->owner_name ?? '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                @error('owner_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 3. LOCATION ADDRESS --}}
        {{-- ================================================================== --}}
        <div class="bg-white rounded-xl border {{ $errors->hasAny(['location_street','location_barangay_id']) ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">3</span>Location Address
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="location_street" class="block text-xs font-medium text-gray-600 mb-1">Street/Bldg. <span class="text-red-500">*</span></label>
                    <input type="text" name="location_street" id="location_street" required
                        value="{{ old('location_street', $application->location_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('location_street')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="location_barangay_id" class="block text-xs font-medium text-gray-600 mb-1">Barangay <span class="text-red-500">*</span></label>
                    <select name="location_barangay_id" id="location_barangay_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        <option value="">-- Select --</option>
                        @foreach($sfcBarangays as $brgy)
                            <option value="{{ $brgy->id }}" {{ old('location_barangay_id', $application->location_barangay_id ?? '') == $brgy->id ? 'selected' : '' }}>{{ $brgy->name }}</option>
                        @endforeach
                    </select>
                    @error('location_barangay_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 4. CHARACTER OF OCCUPANCY --}}
        {{-- ================================================================== --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">4</span>Character of Occupancy <span class="text-red-500 ml-1">*</span>
            </h3>
            <div id="occupancy-error" class="hidden mb-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                Please select a Character of Occupancy.
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach($occupancyGroups as $group)
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <span class="text-sm font-bold text-gray-800 underline">{{ $group->code }}: {{ $group->name }}</span>
                        </div>
                        <div class="px-4 py-2.5 space-y-1.5">
                            @foreach($group->subGroups as $subGroup)
                                @php $isChecked = (string) $selectedSubGroup === (string) $subGroup->id; @endphp
                                <div>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="occupancy_sub_group" value="{{ $subGroup->id }}"
                                            class="w-4 h-4 text-teal-600 border-gray-300 focus:ring-teal-500"
                                            {{ $isChecked ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">{{ $subGroup->name }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 5. EQUIPMENT / ITEMS TO BE INSPECTED --}}
        {{-- ================================================================== --}}
        @php
            $existingEquipmentData = old('equipment', $application
                ? $application->equipmentItems->map(fn ($e) => [
                    'fee_code' => $e->fee_code,
                    'quantity' => $e->quantity,
                    'specification' => $e->specification,
                ])->values()->all()
                : []);
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3" x-data="{
            equipment: {{ json_encode(array_values($existingEquipmentData)) }}
        }">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-1 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">5</span>Equipment / Items to be Inspected
            </h3>
            <p class="text-xs text-gray-500 mb-3">This list is the declared basis for Engineering Assessment — staff will reference it when adding fee items.</p>

            <template x-for="(row, index) in equipment" :key="index">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-start border border-gray-100 rounded-lg p-3">
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Equipment <span class="text-red-500">*</span></label>
                        <select :name="'equipment[' + index + '][fee_code]'" x-model="row.fee_code" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">-- Select equipment --</option>
                            @foreach($equipmentCategories as $groupLabel => $codes)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($codes as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Quantity <span class="text-red-500">*</span></label>
                        <input type="number" :name="'equipment[' + index + '][quantity]'" x-model="row.quantity" min="1" value="1" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Specification</label>
                        <input type="text" :name="'equipment[' + index + '][specification]'" x-model="row.specification" placeholder="e.g. 50 kW, 3rd Floor unit"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div class="sm:col-span-1 flex sm:justify-end">
                        <button type="button" @click="equipment.splice(index, 1)"
                            class="mt-6 sm:mt-6 inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-gray-400 hover:text-red-600 hover:border-red-300 transition" title="Remove">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>
            </template>

            <button type="button" @click="equipment.push({ fee_code: '', quantity: 1, specification: '' })"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-teal-700 border border-teal-200 rounded-lg hover:bg-teal-50 transition">
                <i class="fas fa-plus text-xs"></i> Add Equipment/Item
            </button>
            @error('equipment')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- FORM ACTIONS --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4">
        <a href="{{ route('annual-inspection-applications.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
            <i class="fas fa-times text-xs"></i> Cancel
        </a>
        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-2.5 bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition">
            <i class="fas fa-save text-xs"></i> {{ $application ? 'Update Application' : 'Create Application' }}
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function validateOccupancy() {
        var checked = document.querySelectorAll('input[name="occupancy_sub_group"]:checked');
        var errorEl = document.getElementById('occupancy-error');
        if (checked.length === 0) {
            errorEl.classList.remove('hidden');
            errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        errorEl.classList.add('hidden');
        return true;
    }
</script>
@endpush

@if($errors->any())
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('validation-errors');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
</script>
@endpush
@endif
