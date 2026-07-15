@extends('layouts.app')

@section('title', $application ? 'Edit Demolition Permit Application' : 'New Demolition Permit Application')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('demolition-applications.index') }}" class="text-gray-500 hover:text-gray-700">Demolition Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $application ? 'Edit ' . $application->application_number : 'New Application' }}</span>
@endsection

@section('content')
@php
    $selectedSubGroups = [];
    if ($application && $application->applicationOccupancyGroups) {
        foreach ($application->applicationOccupancyGroups as $aog) {
            $selectedSubGroups[] = $aog->occupancy_sub_group_id;
        }
    }
    if (old('occupancy_sub_groups')) {
        $selectedSubGroups = old('occupancy_sub_groups');
    }
    $sectionNum = 0;
@endphp

<form
    method="POST"
    action="{{ $application ? route('demolition-applications.update', $application) : route('demolition-applications.store') }}"
    x-data="demolitionApplicationForm()"
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
                <span class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold rounded-md bg-red-600 text-white">DP</span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $application ? 'Edit Application' : 'New Demolition Permit Application' }}
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
        {{-- 1. APPLICANT INFORMATION --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->hasAny(['applicant_first_name','applicant_last_name']) ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-red-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Information
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="applicant_first_name" class="block text-xs font-medium text-gray-600 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_first_name" id="applicant_first_name" required
                        value="{{ old('applicant_first_name', $application->applicant_first_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('applicant_first_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_middle_name" class="block text-xs font-medium text-gray-600 mb-1">Middle Name</label>
                    <input type="text" name="applicant_middle_name" id="applicant_middle_name"
                        value="{{ old('applicant_middle_name', $application->applicant_middle_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="applicant_last_name" class="block text-xs font-medium text-gray-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_last_name" id="applicant_last_name" required
                        value="{{ old('applicant_last_name', $application->applicant_last_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('applicant_last_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_tin" class="block text-xs font-medium text-gray-600 mb-1">TIN</label>
                    <input type="text" name="applicant_tin" id="applicant_tin"
                        value="{{ old('applicant_tin', $application->applicant_tin ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="applicant_telephone" class="block text-xs font-medium text-gray-600 mb-1">Telephone</label>
                    <input type="text" name="applicant_telephone" id="applicant_telephone"
                        value="{{ old('applicant_telephone', $application->applicant_telephone ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div class="flex items-end pb-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="owned_by_enterprise" value="0">
                        <input type="checkbox" name="owned_by_enterprise" value="1" x-model="ownedByEnterprise"
                            class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
                            {{ old('owned_by_enterprise', $application->owned_by_enterprise ?? false) ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">Construction Owned By an Enterprise</span>
                    </label>
                </div>
                <div x-show="ownedByEnterprise" x-cloak>
                    <label for="enterprise_name" class="block text-xs font-medium text-gray-600 mb-1">Enterprise Name</label>
                    <input type="text" name="enterprise_name" id="enterprise_name"
                        value="{{ old('enterprise_name', $application->enterprise_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="form_of_ownership_id" class="block text-xs font-medium text-gray-600 mb-1">Form of Ownership</label>
                    <select name="form_of_ownership_id" id="form_of_ownership_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">-- Select --</option>
                        @foreach($formOfOwnerships as $ownership)
                            <option value="{{ $ownership->id }}" {{ old('form_of_ownership_id', $application->form_of_ownership_id ?? '') == $ownership->id ? 'selected' : '' }}>{{ $ownership->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 2. APPLICANT ADDRESS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->hasAny(['applicant_province_id','applicant_city_id','applicant_barangay_id']) ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-red-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Address
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Province <span class="text-red-500">*</span></label>
                    <select name="applicant_province_id" x-model="selectedProvince" @change="selectedCity=''; selectedBarangay='';" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">-- Select Province --</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @error('applicant_province_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">City/Municipality <span class="text-red-500">*</span></label>
                    <select name="applicant_city_id" :value="selectedCity"
                        x-init="$nextTick(() => { $el.value = selectedCity })"
                        @change="selectedCity=$event.target.value; selectedBarangay=''; loadBarangays(selectedCity)" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">-- Select City --</option>
                        <template x-for="city in filteredCities" :key="city.id">
                            <option :value="city.id" x-text="city.name"></option>
                        </template>
                    </select>
                    @error('applicant_city_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Barangay <span class="text-red-500">*</span></label>
                    <select name="applicant_barangay_id" :value="selectedBarangay"
                        x-init="$watch('barangayOptions', () => $nextTick(() => { $el.value = selectedBarangay }))"
                        @change="selectedBarangay=$event.target.value" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">-- Select --</option>
                        <template x-for="brgy in barangayOptions" :key="brgy.id">
                            <option :value="brgy.id" x-text="brgy.name"></option>
                        </template>
                    </select>
                    @error('applicant_barangay_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_street" class="block text-xs font-medium text-gray-600 mb-1">No./Street/Bldg</label>
                    <input type="text" name="applicant_street" id="applicant_street"
                        value="{{ old('applicant_street', $application->applicant_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="applicant_zip_code" class="block text-xs font-medium text-gray-600 mb-1">Zip Code</label>
                    <input type="text" name="applicant_zip_code" id="applicant_zip_code"
                        value="{{ old('applicant_zip_code', $application->applicant_zip_code ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="applicant_ctc_no" class="block text-xs font-medium text-gray-600 mb-1">CTC No.</label>
                    <input type="text" name="applicant_ctc_no" id="applicant_ctc_no"
                        value="{{ old('applicant_ctc_no', $application->applicant_ctc_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="applicant_ctc_date_issued" class="block text-xs font-medium text-gray-600 mb-1">Date Issued</label>
                    <input type="date" name="applicant_ctc_date_issued" id="applicant_ctc_date_issued"
                        value="{{ old('applicant_ctc_date_issued', optional($application->applicant_ctc_date_issued ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="applicant_ctc_place_issued" class="block text-xs font-medium text-gray-600 mb-1">Place Issued</label>
                    <input type="text" name="applicant_ctc_place_issued" id="applicant_ctc_place_issued"
                        value="{{ old('applicant_ctc_place_issued', $application->applicant_ctc_place_issued ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 3. CHARACTER OF OCCUPANCY --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-red-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Character of Occupancy <span class="text-red-500 ml-1">*</span>
            </h3>
            <div id="occupancy-error" class="hidden mb-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                Please select at least one Character of Occupancy.
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach($occupancyGroups as $group)
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <span class="text-sm font-bold text-gray-800 underline">{{ $group->code }}: {{ $group->name }}</span>
                        </div>
                        <div class="px-4 py-2.5 space-y-1.5">
                            @foreach($group->subGroups as $subGroup)
                                @php $isChecked = in_array($subGroup->id, (array) $selectedSubGroups); @endphp
                                <div>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="occupancy_sub_groups[]" value="{{ $subGroup->id }}"
                                            class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
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
        {{-- 4. LOCATION OF DEMOLITION WORKS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->hasAny(['demolition_street','demolition_barangay_id']) ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-red-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Location of Demolition Works
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="lot_no" class="block text-xs font-medium text-gray-600 mb-1">Lot No.</label>
                    <input type="text" name="lot_no" id="lot_no"
                        value="{{ old('lot_no', $application->lot_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="block_no" class="block text-xs font-medium text-gray-600 mb-1">Blk No.</label>
                    <input type="text" name="block_no" id="block_no"
                        value="{{ old('block_no', $application->block_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="tct_no" class="block text-xs font-medium text-gray-600 mb-1">TCT No.</label>
                    <input type="text" name="tct_no" id="tct_no"
                        value="{{ old('tct_no', $application->tct_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="tax_dec_no" class="block text-xs font-medium text-gray-600 mb-1">Tax Dec. No.</label>
                    <input type="text" name="tax_dec_no" id="tax_dec_no"
                        value="{{ old('tax_dec_no', $application->tax_dec_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="demolition_street" class="block text-xs font-medium text-gray-600 mb-1">Street <span class="text-red-500">*</span></label>
                    <input type="text" name="demolition_street" id="demolition_street" required
                        value="{{ old('demolition_street', $application->demolition_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('demolition_street')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="demolition_barangay_id" class="block text-xs font-medium text-gray-600 mb-1">Barangay <span class="text-red-500">*</span></label>
                    <select name="demolition_barangay_id" id="demolition_barangay_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">-- Select --</option>
                        @foreach($sfcBarangays as $brgy)
                            <option value="{{ $brgy->id }}" {{ old('demolition_barangay_id', $application->demolition_barangay_id ?? '') == $brgy->id ? 'selected' : '' }}>{{ $brgy->name }}</option>
                        @endforeach
                    </select>
                    @error('demolition_barangay_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 5. SCOPE OF WORK --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->has('scope_of_work') ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-red-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Scope of Work <span class="text-red-500 ml-1">*</span>
            </h3>
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 rounded-lg" :class="scopeOfWork === 'demolition' ? 'bg-red-50 ring-1 ring-red-200' : 'hover:bg-gray-50'">
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0 sm:w-40">
                        <input type="radio" name="scope_of_work" value="demolition" x-model="scopeOfWork" required
                            class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500"
                            {{ old('scope_of_work', $application->scope_of_work ?? '') === 'demolition' ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">Demolition</span>
                    </label>
                    <input type="text" name="scope_of_work_detail" x-show="scopeOfWork === 'demolition'" x-cloak
                        :disabled="scopeOfWork !== 'demolition'"
                        value="{{ old('scope_of_work_detail', ($application->scope_of_work ?? '') === 'demolition' ? ($application->scope_of_work_detail ?? '') : '') }}"
                        placeholder="Specify details..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 rounded-lg" :class="scopeOfWork === 'others' ? 'bg-red-50 ring-1 ring-red-200' : 'hover:bg-gray-50'">
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0 sm:w-40">
                        <input type="radio" name="scope_of_work" value="others" x-model="scopeOfWork"
                            class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500"
                            {{ old('scope_of_work', $application->scope_of_work ?? '') === 'others' ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">Others (Specify)</span>
                    </label>
                    <input type="text" name="scope_of_work_detail" x-show="scopeOfWork === 'others'" x-cloak
                        :disabled="scopeOfWork !== 'others'"
                        value="{{ old('scope_of_work_detail', ($application->scope_of_work ?? '') === 'others' ? ($application->scope_of_work_detail ?? '') : '') }}"
                        placeholder="Specify details..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>
            @error('scope_of_work')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- ================================================================== --}}
        {{-- 6. FULL-TIME INSPECTOR AND SUPERVISOR OF DEMOLITION WORKS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->hasAny(['inspector_name','inspector_prc_no']) ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-red-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Full-time Inspector and Supervisor of Demolition Works
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="inspector_name" class="block text-xs font-medium text-gray-600 mb-1">Name of Architect or Civil Engineer <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_name" id="inspector_name" required
                        value="{{ old('inspector_name', $application->inspector_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('inspector_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inspector_address" class="block text-xs font-medium text-gray-600 mb-1">Address <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_address" id="inspector_address" required
                        value="{{ old('inspector_address', $application->inspector_address ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('inspector_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="inspector_telephone" class="block text-xs font-medium text-gray-600 mb-1">Telephone Number</label>
                    <input type="text" name="inspector_telephone" id="inspector_telephone"
                        value="{{ old('inspector_telephone', $application->inspector_telephone ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="inspector_prc_no" class="block text-xs font-medium text-gray-600 mb-1">PRC No. <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_prc_no" id="inspector_prc_no" required
                        value="{{ old('inspector_prc_no', $application->inspector_prc_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('inspector_prc_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inspector_prc_validity" class="block text-xs font-medium text-gray-600 mb-1">PRC Validity <span class="text-red-500">*</span></label>
                    <input type="date" name="inspector_prc_validity" id="inspector_prc_validity" required
                        value="{{ old('inspector_prc_validity', optional($application->inspector_prc_validity ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('inspector_prc_validity')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="inspector_ptr_no" class="block text-xs font-medium text-gray-600 mb-1">PTR No. <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_ptr_no" id="inspector_ptr_no" required
                        value="{{ old('inspector_ptr_no', $application->inspector_ptr_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('inspector_ptr_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inspector_ptr_date_issued" class="block text-xs font-medium text-gray-600 mb-1">Date Issued <span class="text-red-500">*</span></label>
                    <input type="date" name="inspector_ptr_date_issued" id="inspector_ptr_date_issued" required
                        value="{{ old('inspector_ptr_date_issued', optional($application->inspector_ptr_date_issued ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('inspector_ptr_date_issued')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inspector_ptr_issued_at" class="block text-xs font-medium text-gray-600 mb-1">Issued At <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_ptr_issued_at" id="inspector_ptr_issued_at" required
                        value="{{ old('inspector_ptr_issued_at', $application->inspector_ptr_issued_at ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('inspector_ptr_issued_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="inspector_tin" class="block text-xs font-medium text-gray-600 mb-1">TIN <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_tin" id="inspector_tin" required
                        value="{{ old('inspector_tin', $application->inspector_tin ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('inspector_tin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 7. LOT OWNER CONSENT --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-red-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Lot Owner Consent
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="owner_name" class="block text-xs font-medium text-gray-600 mb-1">Full Name of Lot Owner</label>
                    <input type="text" name="owner_name" id="owner_name"
                        value="{{ old('owner_name', $application->owner_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="owner_ctc_no" class="block text-xs font-medium text-gray-600 mb-1">CTC No.</label>
                    <input type="text" name="owner_ctc_no" id="owner_ctc_no"
                        value="{{ old('owner_ctc_no', $application->owner_ctc_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="owner_ctc_date_issued" class="block text-xs font-medium text-gray-600 mb-1">Date Issued</label>
                    <input type="date" name="owner_ctc_date_issued" id="owner_ctc_date_issued"
                        value="{{ old('owner_ctc_date_issued', optional($application->owner_ctc_date_issued ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label for="owner_ctc_place_issued" class="block text-xs font-medium text-gray-600 mb-1">Place Issued</label>
                    <input type="text" name="owner_ctc_place_issued" id="owner_ctc_place_issued"
                        value="{{ old('owner_ctc_place_issued', $application->owner_ctc_place_issued ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- REMARKS --}}
        {{-- ================================================================== --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <label for="remarks" class="block text-xs font-medium text-gray-600 mb-1">Remarks</label>
            <textarea name="remarks" id="remarks" rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">{{ old('remarks', $application->remarks ?? '') }}</textarea>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- FORM ACTIONS --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4">
        <a href="{{ route('demolition-applications.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
            <i class="fas fa-times text-xs"></i> Cancel
        </a>
        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-2.5 bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition">
            <i class="fas fa-save text-xs"></i> {{ $application ? 'Update Application' : 'Create Application' }}
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function demolitionApplicationForm() {
        return {
            selectedProvince: '{{ old('applicant_province_id', $application->applicant_province_id ?? '') }}',
            selectedCity: '{{ old('applicant_city_id', $application->applicant_city_id ?? '') }}',
            selectedBarangay: '{{ old('applicant_barangay_id', $application->applicant_barangay_id ?? '') }}',
            cities: @json($cities),
            barangayOptions: [],

            get filteredCities() {
                if (!this.selectedProvince) return [];
                return this.cities.filter(c => String(c.province_id) === String(this.selectedProvince));
            },
            async loadBarangays(cityId) {
                if (!cityId) { this.barangayOptions = []; return; }
                const res = await fetch(`/geo/barangays/${cityId}`);
                this.barangayOptions = await res.json();
            },
            init() {
                if (this.selectedCity) this.loadBarangays(this.selectedCity);
            },

            ownedByEnterprise: {{ old('owned_by_enterprise', $application->owned_by_enterprise ?? false) ? 'true' : 'false' }},
            scopeOfWork: '{{ old('scope_of_work', $application->scope_of_work ?? '') }}',
        }
    }

    function validateOccupancy() {
        var checked = document.querySelectorAll('input[name="occupancy_sub_groups[]"]:checked');
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
@if($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('validation-errors');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
@endif
@endpush
