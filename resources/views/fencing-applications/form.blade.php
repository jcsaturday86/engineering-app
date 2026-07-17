@extends('layouts.app')

@section('title', $application ? 'Edit Fencing Permit Application' : 'New Fencing Permit Application')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('fencing-applications.index') }}" class="text-gray-500 hover:text-gray-700">Fencing Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $application ? 'Edit ' . $application->application_number : 'New Application' }}</span>
@endsection

@section('content')
@php
    $sectionNum = 0;
@endphp

<form
    method="POST"
    action="{{ $application ? route('fencing-applications.update', $application) : route('fencing-applications.store') }}"
    x-data="fencingApplicationForm()"
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
                <span class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold rounded-md bg-teal-600 text-white">FP</span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $application ? 'Edit Application' : 'New Fencing Permit Application' }}
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
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Information
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="applicant_first_name" class="block text-xs font-medium text-gray-600 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_first_name" id="applicant_first_name" required
                        value="{{ old('applicant_first_name', $application->applicant_first_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_first_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_middle_name" class="block text-xs font-medium text-gray-600 mb-1">Middle Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_middle_name" id="applicant_middle_name" required
                        value="{{ old('applicant_middle_name', $application->applicant_middle_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_middle_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_last_name" class="block text-xs font-medium text-gray-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_last_name" id="applicant_last_name" required
                        value="{{ old('applicant_last_name', $application->applicant_last_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_last_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_tin" class="block text-xs font-medium text-gray-600 mb-1">TIN <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_tin" id="applicant_tin" required
                        value="{{ old('applicant_tin', $application->applicant_tin ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_tin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="applicant_telephone" class="block text-xs font-medium text-gray-600 mb-1">Telephone Number <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_telephone" id="applicant_telephone" required
                        value="{{ old('applicant_telephone', $application->applicant_telephone ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_telephone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end pb-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="owned_by_enterprise" value="0">
                        <input type="checkbox" name="owned_by_enterprise" value="1" x-model="ownedByEnterprise"
                            class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500"
                            {{ old('owned_by_enterprise', $application->owned_by_enterprise ?? false) ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">Construction Owned By an Enterprise</span>
                    </label>
                </div>
                <div x-show="ownedByEnterprise" x-cloak>
                    <label for="enterprise_name" class="block text-xs font-medium text-gray-600 mb-1">Enterprise Name <span class="text-red-500" x-show="ownedByEnterprise">*</span></label>
                    <input type="text" name="enterprise_name" id="enterprise_name" :required="ownedByEnterprise"
                        value="{{ old('enterprise_name', $application->enterprise_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('enterprise_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="form_of_ownership_id" class="block text-xs font-medium text-gray-600 mb-1">Form of Ownership <span class="text-red-500" x-show="ownedByEnterprise">*</span></label>
                    <select name="form_of_ownership_id" id="form_of_ownership_id" :required="ownedByEnterprise"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        <option value="">-- Select --</option>
                        @foreach($formOfOwnerships as $ownership)
                            <option value="{{ $ownership->id }}" {{ old('form_of_ownership_id', $application->form_of_ownership_id ?? '') == $ownership->id ? 'selected' : '' }}>{{ $ownership->name }}</option>
                        @endforeach
                    </select>
                    @error('form_of_ownership_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 2. APPLICANT ADDRESS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->hasAny(['applicant_province_id','applicant_city_id','applicant_barangay_id']) ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Address
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Province <span class="text-red-500">*</span></label>
                    <select name="applicant_province_id" x-model="selectedProvince" @change="selectedCity=''; selectedBarangay='';" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        <option value="">-- Select --</option>
                        <template x-for="brgy in barangayOptions" :key="brgy.id">
                            <option :value="brgy.id" x-text="brgy.name"></option>
                        </template>
                    </select>
                    @error('applicant_barangay_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_street" class="block text-xs font-medium text-gray-600 mb-1">No./Street/Bldg <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_street" id="applicant_street" required
                        value="{{ old('applicant_street', $application->applicant_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_street')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_zip_code" class="block text-xs font-medium text-gray-600 mb-1">Zip Code <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_zip_code" id="applicant_zip_code" required
                        value="{{ old('applicant_zip_code', $application->applicant_zip_code ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_zip_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="applicant_ctc_no" class="block text-xs font-medium text-gray-600 mb-1">CTC No. <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_ctc_no" id="applicant_ctc_no" required
                        value="{{ old('applicant_ctc_no', $application->applicant_ctc_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_ctc_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_ctc_date_issued" class="block text-xs font-medium text-gray-600 mb-1">Date Issued <span class="text-red-500">*</span></label>
                    <input type="date" name="applicant_ctc_date_issued" id="applicant_ctc_date_issued" required
                        value="{{ old('applicant_ctc_date_issued', optional($application->applicant_ctc_date_issued ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_ctc_date_issued')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_ctc_issued_at" class="block text-xs font-medium text-gray-600 mb-1">Issued At <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_ctc_issued_at" id="applicant_ctc_issued_at" required
                        value="{{ old('applicant_ctc_issued_at', $application->applicant_ctc_issued_at ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('applicant_ctc_issued_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 3. LOCATION OF CONSTRUCTION --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->hasAny(['construction_street','construction_barangay_id']) ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Location of Construction
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label for="lot_no" class="block text-xs font-medium text-gray-600 mb-1">Lot No. <span class="text-red-500">*</span></label>
                    <input type="text" name="lot_no" id="lot_no" required
                        value="{{ old('lot_no', $application->lot_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('lot_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="block_no" class="block text-xs font-medium text-gray-600 mb-1">Blk No. <span class="text-red-500">*</span></label>
                    <input type="text" name="block_no" id="block_no" required
                        value="{{ old('block_no', $application->block_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('block_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tct_no" class="block text-xs font-medium text-gray-600 mb-1">TCT No. <span class="text-red-500">*</span></label>
                    <input type="text" name="tct_no" id="tct_no" required
                        value="{{ old('tct_no', $application->tct_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('tct_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="tax_dec_no" class="block text-xs font-medium text-gray-600 mb-1">Tax Dec. No. <span class="text-red-500">*</span></label>
                    <input type="text" name="tax_dec_no" id="tax_dec_no" required
                        value="{{ old('tax_dec_no', $application->tax_dec_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('tax_dec_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="construction_street" class="block text-xs font-medium text-gray-600 mb-1">Street <span class="text-red-500">*</span></label>
                    <input type="text" name="construction_street" id="construction_street" required
                        value="{{ old('construction_street', $application->construction_street ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('construction_street')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="construction_barangay_id" class="block text-xs font-medium text-gray-600 mb-1">Barangay <span class="text-red-500">*</span></label>
                    <select name="construction_barangay_id" id="construction_barangay_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        <option value="">-- Select --</option>
                        @foreach($sfcBarangays as $brgy)
                            <option value="{{ $brgy->id }}" {{ old('construction_barangay_id', $application->construction_barangay_id ?? '') == $brgy->id ? 'selected' : '' }}>{{ $brgy->name }}</option>
                        @endforeach
                    </select>
                    @error('construction_barangay_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 4. SCOPE OF WORK --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->has('scope_of_work') ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Scope of Work (Choose One) <span class="text-red-500 ml-1">*</span>
            </h3>
            <div class="space-y-2">
                @foreach(['new_construction' => 'New Construction', 'erection' => 'Erection', 'addition' => 'Addition'] as $value => $label)
                <div class="flex items-center gap-2 p-3 rounded-lg" :class="scopeOfWork === '{{ $value }}' ? 'bg-teal-50 ring-1 ring-teal-200' : 'hover:bg-gray-50'">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="scope_of_work" value="{{ $value }}" x-model="scopeOfWork" required
                            class="w-4 h-4 text-teal-600 border-gray-300 focus:ring-teal-500"
                            {{ old('scope_of_work', $application->scope_of_work ?? '') === $value ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">{{ $label }}</span>
                    </label>
                </div>
                @endforeach
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 rounded-lg" :class="scopeOfWork === 'repair' ? 'bg-teal-50 ring-1 ring-teal-200' : 'hover:bg-gray-50'">
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0 sm:w-40">
                        <input type="radio" name="scope_of_work" value="repair" x-model="scopeOfWork"
                            class="w-4 h-4 text-teal-600 border-gray-300 focus:ring-teal-500"
                            {{ old('scope_of_work', $application->scope_of_work ?? '') === 'repair' ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">Repair (Specify)</span>
                    </label>
                    <input type="text" name="scope_of_work_detail" x-show="scopeOfWork === 'repair'" x-cloak
                        :disabled="scopeOfWork !== 'repair'" :required="scopeOfWork === 'repair'"
                        value="{{ old('scope_of_work_detail', ($application->scope_of_work ?? '') === 'repair' ? ($application->scope_of_work_detail ?? '') : '') }}"
                        placeholder="Specify details..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 rounded-lg" :class="scopeOfWork === 'others' ? 'bg-teal-50 ring-1 ring-teal-200' : 'hover:bg-gray-50'">
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0 sm:w-40">
                        <input type="radio" name="scope_of_work" value="others" x-model="scopeOfWork"
                            class="w-4 h-4 text-teal-600 border-gray-300 focus:ring-teal-500"
                            {{ old('scope_of_work', $application->scope_of_work ?? '') === 'others' ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">Others (Specify)</span>
                    </label>
                    <input type="text" name="scope_of_work_detail" x-show="scopeOfWork === 'others'" x-cloak
                        :disabled="scopeOfWork !== 'others'" :required="scopeOfWork === 'others'"
                        value="{{ old('scope_of_work_detail', ($application->scope_of_work ?? '') === 'others' ? ($application->scope_of_work_detail ?? '') : '') }}"
                        placeholder="Specify details..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                </div>
            </div>
            @error('scope_of_work')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            @error('scope_of_work_detail')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- ================================================================== --}}
        {{-- 5. DESIGN PROFESSIONAL, PLANS AND SPECIFICATIONS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Design Professional, Plans and Specifications
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="design_professional_name" class="block text-xs font-medium text-gray-600 mb-1">Name of Architect or Civil Engineer <span class="text-red-500">*</span></label>
                    <input type="text" name="design_professional_name" id="design_professional_name" required
                        value="{{ old('design_professional_name', $application->design_professional_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('design_professional_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="design_professional_address" class="block text-xs font-medium text-gray-600 mb-1">Address <span class="text-red-500">*</span></label>
                    <input type="text" name="design_professional_address" id="design_professional_address" required
                        value="{{ old('design_professional_address', $application->design_professional_address ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('design_professional_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="design_professional_prc_no" class="block text-xs font-medium text-gray-600 mb-1">PRC No. <span class="text-red-500">*</span></label>
                    <input type="text" name="design_professional_prc_no" id="design_professional_prc_no" required
                        value="{{ old('design_professional_prc_no', $application->design_professional_prc_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('design_professional_prc_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="design_professional_prc_validity" class="block text-xs font-medium text-gray-600 mb-1">Validity <span class="text-red-500">*</span></label>
                    <input type="date" name="design_professional_prc_validity" id="design_professional_prc_validity" required
                        value="{{ old('design_professional_prc_validity', optional($application->design_professional_prc_validity ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('design_professional_prc_validity')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="design_professional_ptr_no" class="block text-xs font-medium text-gray-600 mb-1">PTR No. <span class="text-red-500">*</span></label>
                    <input type="text" name="design_professional_ptr_no" id="design_professional_ptr_no" required
                        value="{{ old('design_professional_ptr_no', $application->design_professional_ptr_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('design_professional_ptr_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="design_professional_ptr_date_issued" class="block text-xs font-medium text-gray-600 mb-1">Date Issued <span class="text-red-500">*</span></label>
                    <input type="date" name="design_professional_ptr_date_issued" id="design_professional_ptr_date_issued" required
                        value="{{ old('design_professional_ptr_date_issued', optional($application->design_professional_ptr_date_issued ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('design_professional_ptr_date_issued')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="design_professional_ptr_issued_at" class="block text-xs font-medium text-gray-600 mb-1">Issued At <span class="text-red-500">*</span></label>
                    <input type="text" name="design_professional_ptr_issued_at" id="design_professional_ptr_issued_at" required
                        value="{{ old('design_professional_ptr_issued_at', $application->design_professional_ptr_issued_at ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('design_professional_ptr_issued_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="design_professional_tin" class="block text-xs font-medium text-gray-600 mb-1">TIN <span class="text-red-500">*</span></label>
                    <input type="text" name="design_professional_tin" id="design_professional_tin" required
                        value="{{ old('design_professional_tin', $application->design_professional_tin ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('design_professional_tin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 6. FULL-TIME INSPECTOR OR SUPERVISOR --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-3">
                <h3 class="text-base font-semibold text-gray-900 flex items-center">
                    <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Full-Time Inspector or Supervisor
                </h3>
                <label class="relative inline-flex items-center cursor-pointer" x-data="{ inspectorCopied: false }">
                    <input type="checkbox" id="inspector_same_as_dp"
                        @click="copyDesignProfessionalToInspector($event.target.checked); inspectorCopied = $event.target.checked"
                        class="sr-only peer">
                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-teal-600"></div>
                    <span class="ml-2 text-xs font-medium text-gray-600">Same as Design Professional</span>
                    <span x-show="inspectorCopied" x-cloak
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-1"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        class="ml-2 text-xs text-green-600 font-medium">Details copied</span>
                </label>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="inspector_name" class="block text-xs font-medium text-gray-600 mb-1">Name of Architect or Civil Engineer <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_name" id="inspector_name" required
                        value="{{ old('inspector_name', $application->inspector_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('inspector_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inspector_address" class="block text-xs font-medium text-gray-600 mb-1">Address <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_address" id="inspector_address" required
                        value="{{ old('inspector_address', $application->inspector_address ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('inspector_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="inspector_prc_no" class="block text-xs font-medium text-gray-600 mb-1">PRC No. <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_prc_no" id="inspector_prc_no" required
                        value="{{ old('inspector_prc_no', $application->inspector_prc_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('inspector_prc_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inspector_prc_validity" class="block text-xs font-medium text-gray-600 mb-1">Validity <span class="text-red-500">*</span></label>
                    <input type="date" name="inspector_prc_validity" id="inspector_prc_validity" required
                        value="{{ old('inspector_prc_validity', optional($application->inspector_prc_validity ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('inspector_prc_validity')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="inspector_ptr_no" class="block text-xs font-medium text-gray-600 mb-1">PTR No. <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_ptr_no" id="inspector_ptr_no" required
                        value="{{ old('inspector_ptr_no', $application->inspector_ptr_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('inspector_ptr_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inspector_ptr_date_issued" class="block text-xs font-medium text-gray-600 mb-1">Date Issued <span class="text-red-500">*</span></label>
                    <input type="date" name="inspector_ptr_date_issued" id="inspector_ptr_date_issued" required
                        value="{{ old('inspector_ptr_date_issued', optional($application->inspector_ptr_date_issued ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('inspector_ptr_date_issued')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="inspector_ptr_issued_at" class="block text-xs font-medium text-gray-600 mb-1">Issued At <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_ptr_issued_at" id="inspector_ptr_issued_at" required
                        value="{{ old('inspector_ptr_issued_at', $application->inspector_ptr_issued_at ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('inspector_ptr_issued_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="inspector_tin" class="block text-xs font-medium text-gray-600 mb-1">TIN <span class="text-red-500">*</span></label>
                    <input type="text" name="inspector_tin" id="inspector_tin" required
                        value="{{ old('inspector_tin', $application->inspector_tin ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('inspector_tin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 7. CONSENT OF LOT OWNER --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Consent of Lot Owner
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="owner_name" class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="owner_name" id="owner_name" required
                        value="{{ old('owner_name', $application->owner_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('owner_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="owner_address" class="block text-xs font-medium text-gray-600 mb-1">Address <span class="text-red-500">*</span></label>
                    <input type="text" name="owner_address" id="owner_address" required
                        value="{{ old('owner_address', $application->owner_address ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('owner_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="owner_ctc_no" class="block text-xs font-medium text-gray-600 mb-1">CTC No. <span class="text-red-500">*</span></label>
                    <input type="text" name="owner_ctc_no" id="owner_ctc_no" required
                        value="{{ old('owner_ctc_no', $application->owner_ctc_no ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('owner_ctc_no')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="owner_ctc_date_issued" class="block text-xs font-medium text-gray-600 mb-1">Date Issued <span class="text-red-500">*</span></label>
                    <input type="date" name="owner_ctc_date_issued" id="owner_ctc_date_issued" required
                        value="{{ old('owner_ctc_date_issued', optional($application->owner_ctc_date_issued ?? null)->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('owner_ctc_date_issued')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="owner_ctc_issued_at" class="block text-xs font-medium text-gray-600 mb-1">Issued At <span class="text-red-500">*</span></label>
                    <input type="text" name="owner_ctc_issued_at" id="owner_ctc_issued_at" required
                        value="{{ old('owner_ctc_issued_at', $application->owner_ctc_issued_at ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('owner_ctc_issued_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- FORM ACTIONS --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4">
        <a href="{{ route('fencing-applications.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
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
    function fencingApplicationForm() {
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

    function copyDesignProfessionalToInspector(checked) {
        var fields = [
            ['design_professional_name', 'inspector_name'],
            ['design_professional_address', 'inspector_address'],
            ['design_professional_prc_no', 'inspector_prc_no'],
            ['design_professional_prc_validity', 'inspector_prc_validity'],
            ['design_professional_ptr_no', 'inspector_ptr_no'],
            ['design_professional_ptr_date_issued', 'inspector_ptr_date_issued'],
            ['design_professional_ptr_issued_at', 'inspector_ptr_issued_at'],
            ['design_professional_tin', 'inspector_tin'],
        ];
        for (var i = 0; i < fields.length; i++) {
            var src = document.getElementById(fields[i][0]);
            var dst = document.getElementById(fields[i][1]);
            if (src && dst) {
                dst.value = checked ? src.value : '';
                dst.readOnly = checked;
                dst.style.backgroundColor = checked ? '#f3f4f6' : '';
            }
        }
    }
</script>
@if($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('validation-errors');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
@endif
@endpush
