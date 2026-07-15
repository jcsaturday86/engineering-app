@extends('layouts.app')

@section('title', $application ? 'Edit Signage Permit Application' : 'New Signage Permit Application')

@section('breadcrumbs')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <a href="{{ route('signage-applications.index') }}" class="text-gray-500 hover:text-gray-700">Signage Applications</a>
    <i class="fas fa-chevron-right text-xs mx-2 text-gray-400"></i>
    <span class="text-gray-900 font-medium">{{ $application ? 'Edit ' . $application->application_number : 'New Application' }}</span>
@endsection

@section('content')
@php
    $sectionNum = 0;
@endphp

<form
    method="POST"
    action="{{ $application ? route('signage-applications.update', $application) : route('signage-applications.store') }}"
    x-data="signageApplicationForm()"
    onsubmit="return validateScope();"
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
                <span class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold rounded-md bg-indigo-600 text-white">SGP</span>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $application ? 'Edit Application' : 'New Signage Permit Application' }}
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
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Information
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label for="applicant_first_name" class="block text-xs font-medium text-gray-600 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_first_name" id="applicant_first_name" required
                        value="{{ old('applicant_first_name', $application->applicant_first_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_first_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="applicant_middle_name" class="block text-xs font-medium text-gray-600 mb-1">Middle Name</label>
                    <input type="text" name="applicant_middle_name" id="applicant_middle_name"
                        value="{{ old('applicant_middle_name', $application->applicant_middle_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="applicant_last_name" class="block text-xs font-medium text-gray-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="applicant_last_name" id="applicant_last_name" required
                        value="{{ old('applicant_last_name', $application->applicant_last_name ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('applicant_last_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 2. APPLICANT ADDRESS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->hasAny(['applicant_province_id','applicant_city_id','applicant_barangay_id']) ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Applicant Address
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Province <span class="text-red-500">*</span></label>
                    <select name="applicant_province_id" x-model="selectedProvince" @change="selectedCity=''; selectedBarangay='';" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="applicant_zip_code" class="block text-xs font-medium text-gray-600 mb-1">Zip Code</label>
                    <input type="text" name="applicant_zip_code" id="applicant_zip_code"
                        value="{{ old('applicant_zip_code', $application->applicant_zip_code ?? '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 3. SCOPE OF WORK --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border {{ $errors->has('install') ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }} p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Scope of Work <span class="text-red-500 ml-1">*</span>
            </h3>
            <div id="scope-error" class="hidden mb-2 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                Please select at least one Scope of Work item.
            </div>
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 rounded-lg" :class="install ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50'">
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0 sm:w-40">
                        <input type="hidden" name="install" value="0">
                        <input type="checkbox" name="install" value="1" x-model="install"
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            {{ old('install', $application->install ?? false) ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">a. Install</span>
                    </label>
                    <input type="text" name="install_detail" x-show="install" x-cloak
                        value="{{ old('install_detail', $application->install_detail ?? '') }}"
                        placeholder="Specify details..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 rounded-lg" :class="attach ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50'">
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0 sm:w-40">
                        <input type="hidden" name="attach" value="0">
                        <input type="checkbox" name="attach" value="1" x-model="attach"
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            {{ old('attach', $application->attach ?? false) ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">b. Attach</span>
                    </label>
                    <input type="text" name="attach_detail" x-show="attach" x-cloak
                        value="{{ old('attach_detail', $application->attach_detail ?? '') }}"
                        placeholder="Specify details..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 rounded-lg" :class="paint ? 'bg-indigo-50 ring-1 ring-indigo-200' : 'hover:bg-gray-50'">
                    <label class="inline-flex items-center gap-2 cursor-pointer shrink-0 sm:w-40">
                        <input type="hidden" name="paint" value="0">
                        <input type="checkbox" name="paint" value="1" x-model="paint"
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            {{ old('paint', $application->paint ?? false) ? 'checked' : '' }}>
                        <span class="text-sm text-gray-700">c. Paint</span>
                    </label>
                    <input type="text" name="paint_detail" x-show="paint" x-cloak
                        value="{{ old('paint_detail', $application->paint_detail ?? '') }}"
                        placeholder="Specify details..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- 4. WORDINGS --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Wordings
            </h3>
            <textarea name="wordings" rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Exact wordings to appear on the signage">{{ old('wordings', $application->wordings ?? '') }}</textarea>
        </div>

        {{-- ================================================================== --}}
        {{-- 5. PREMISES OF --}}
        {{-- ================================================================== --}}
        @php $sectionNum++ @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-3 flex items-center">
                <span class="inline-flex items-center justify-center w-7 h-7 bg-indigo-600 text-white text-xs font-bold rounded-full mr-2">{{ $sectionNum }}</span>Premises of
            </h3>
            <input type="text" name="premises_of"
                value="{{ old('premises_of', $application->premises_of ?? '') }}"
                placeholder="Name/description of the premises where the signage will be installed"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        {{-- ================================================================== --}}
        {{-- REMARKS --}}
        {{-- ================================================================== --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <label for="remarks" class="block text-xs font-medium text-gray-600 mb-1">Remarks</label>
            <textarea name="remarks" id="remarks" rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('remarks', $application->remarks ?? '') }}</textarea>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- FORM ACTIONS --}}
    {{-- ================================================================== --}}
    <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4">
        <a href="{{ route('signage-applications.index') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
            <i class="fas fa-times text-xs"></i> Cancel
        </a>
        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-2.5 bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
            <i class="fas fa-save text-xs"></i> {{ $application ? 'Update Application' : 'Create Application' }}
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function signageApplicationForm() {
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

            install: {{ old('install', $application->install ?? false) ? 'true' : 'false' }},
            attach: {{ old('attach', $application->attach ?? false) ? 'true' : 'false' }},
            paint: {{ old('paint', $application->paint ?? false) ? 'true' : 'false' }},
        }
    }

    function validateScope() {
        var checked = document.querySelectorAll('input[type="checkbox"][name="install"]:checked, input[type="checkbox"][name="attach"]:checked, input[type="checkbox"][name="paint"]:checked');
        var errorEl = document.getElementById('scope-error');
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
