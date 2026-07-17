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
@php
    $isOp = $isOp ?? false;
    $isDp = $isDp ?? false;
    $isSgp = $isSgp ?? false;
    $isFp = $isFp ?? false;
    $isMp = $isMp ?? false;
    $addItemRoute = $isDp ? route('assessments.addItem.dp', $application) : ($isSgp ? route('assessments.addItem.sgp', $application) : ($isFp ? route('assessments.addItem.fp', $application) : ($isMp ? route('assessments.addMechItem.mp', $application) : ($isOp ? route('assessments.addItem.op', $application) : route('assessments.addItem', $application)))));
    $finalizeRoute = $isDp ? route('assessments.finalize.dp', $application) : ($isSgp ? route('assessments.finalize.sgp', $application) : ($isFp ? route('assessments.finalize.fp', $application) : ($isMp ? route('assessments.finalize.mp', $application) : ($isOp ? route('assessments.finalize.op', $application) : route('assessments.finalize', $application)))));
    $revertRoute = $isDp ? route('assessments.revertFinalize.dp', $application) : ($isSgp ? route('assessments.revertFinalize.sgp', $application) : ($isFp ? route('assessments.revertFinalize.fp', $application) : ($isMp ? route('assessments.revertFinalize.mp', $application) : ($isOp ? route('assessments.revertFinalize.op', $application) : route('assessments.revertFinalize', $application)))));
    $backRoute = $isDp ? route('assessments.demolition') : ($isSgp ? route('assessments.signage') : ($isFp ? route('assessments.fencing') : ($isMp ? route('assessments.mechanical') : ($isOp ? route('assessments.occupancy') : route('assessments.index')))));
    $tabCategories = $tabCategories ?? $feeCategories;
    $activeTab = $activeTab ?? ($tabCategories->first()?->code ?? 'CONST');
    $itemsByCategory = $itemsByCategory ?? $assessmentItems->groupBy('fee_category_id');
    $isFinalized = $assessment && $assessment->status === 'finalized';
@endphp
<div class="space-y-6" x-data="{ activeTab: '{{ $activeTab }}' }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Assess Application</h2>
        <div class="flex flex-wrap items-center gap-2">
            {{-- Return to Zoning Button + Password Modal (BP only, ongoing/not-yet-finalized assessment) --}}
            {{-- Placed in the header so it's visible immediately regardless of which tab is active --}}
            @can('return-to-zoning')
            @if(!$isOp && $application->status === 'zoning_assessed' && !$application->assessments()->where('assessment_type', '!=', 'zoning')->where('status', 'finalized')->exists())
            <div x-data="{ open: false, pw: '' }" class="inline-block">
                <button @click="open = true; pw = ''"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-amber-300 text-amber-700 text-sm font-medium rounded-lg hover:bg-amber-50 transition">
                    <i class="fas fa-arrow-left"></i> Return to Zoning
                </button>

                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center"
                    @keydown.escape.window="open = false">
                    <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6 z-10">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="flex-shrink-0 w-10 h-10 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-lock"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Confirm Return to Zoning</h3>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    This will send the application back to Planning Office for Zoning Assessment, un-finalize the zoning record, and permanently delete all engineering assessment entries entered so far. Enter your password to proceed.
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('assessments.returnToZoning', $application) }}" method="POST" autocomplete="off">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Your Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="password" x-model="pw" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                    placeholder="Enter your password">
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="open = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="!pw"
                                    class="inline-flex items-center gap-1 px-4 py-2 text-sm font-semibold text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-arrow-left"></i> Return to Zoning
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
            @endcan

            {{-- Revert to Draft Button + Password Modal (OP/DP only, ongoing/not-yet-finalized assessment) --}}
            @can('revert-submission')
            @if(($isOp && $application->status === 'zoning_assessed') || (($isDp || $isSgp || $isFp || $isMp) && $application->status === 'submitted'))
            <div x-data="{ open: false, pw: '' }" class="inline-block">
                <button @click="open = true; pw = ''"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
                    <i class="fas fa-undo"></i> Revert to Draft
                </button>

                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center"
                    @keydown.escape.window="open = false">
                    <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6 z-10">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="flex-shrink-0 w-10 h-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-lock"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Confirm Revert to Draft</h3>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    This will send the application back to Draft and permanently delete all fee entries entered so far. Enter your password to proceed.
                                </p>
                            </div>
                        </div>

                        <form action="{{ $isDp ? route('assessments.revertToDraft.dp', $application) : ($isSgp ? route('assessments.revertToDraft.sgp', $application) : ($isFp ? route('assessments.revertToDraft.fp', $application) : ($isMp ? route('assessments.revertToDraft.mp', $application) : route('assessments.revertToDraft.op', $application)))) }}" method="POST" autocomplete="off">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Your Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="password" x-model="pw" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                    placeholder="Enter your password">
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="open = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="!pw"
                                    class="inline-flex items-center gap-1 px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-undo"></i> Revert to Draft
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
            @endcan

            <a href="{{ $backRoute }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    {{-- Application Summary Card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Application Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                <p class="text-sm font-semibold text-blue-700">&#8369;{{ number_format($application->total_estimated_cost ?? 0, 2) }}</p>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 overflow-x-auto overflow-y-hidden scrollbar-hide" style="scrollbar-width: none; -ms-overflow-style: none;">
            <nav class="flex -mb-px min-w-max">
                @foreach($tabCategories as $cat)
                @php $catItemCount = ($itemsByCategory[$cat->id] ?? collect())->count(); @endphp
                <button type="button"
                    @click="activeTab = '{{ $cat->code }}'"
                    :class="activeTab === '{{ $cat->code }}'
                        ? 'border-blue-500 text-blue-600 bg-blue-50/50'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap px-4 py-3 border-b-2 text-xs font-medium transition">
                    {{ $cat->name }}
                    @if($catItemCount > 0)
                        <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full"
                            :class="activeTab === '{{ $cat->code }}' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'">{{ $catItemCount }}</span>
                    @endif
                </button>
                @endforeach
                <button type="button"
                    @click="activeTab = 'SUMMARY'"
                    :class="activeTab === 'SUMMARY'
                        ? 'border-green-500 text-green-600 bg-green-50/50'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap px-4 py-3 border-b-2 text-xs font-medium transition">
                    <i class="fas fa-list-alt mr-1"></i> Summary
                    @if($assessmentItems->count() > 0)
                        <span class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full"
                            :class="activeTab === 'SUMMARY' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600'">{{ $assessmentItems->count() }}</span>
                    @endif
                </button>
            </nav>
        </div>

        {{-- Per-Category Tab Content --}}
        @foreach($tabCategories as $cat)
        @php
            $catItems = $itemsByCategory[$cat->id] ?? collect();
            $catFeeTypes = $cat->feeTypes;
        @endphp
        <div x-show="activeTab === '{{ $cat->code }}'" x-cloak class="p-5 space-y-4">
            @if($isFinalized)
            <div class="flex items-center gap-2 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                <i class="fas fa-lock"></i>
                <span>This assessment has been <strong>finalized</strong>. No further changes can be made.</span>
            </div>
            @endif
            @if($cat->code === 'CONST')
            {{-- Construction Fee Form (BOPMS-style) --}}
            @if(!$isFinalized)
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Construction Item
                </h4>
                <form action="{{ route('assessments.constructionItem', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Part of Building <span class="text-red-500">*</span></label>
                            <select name="building_part_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                @foreach($buildingParts as $bp)
                                    <option value="{{ $bp->id }}">{{ $bp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Division <span class="text-red-500">*</span></label>
                            <select name="occupancy_division_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                @foreach($occupancyDivisions as $div)
                                    <option value="{{ $div->id }}">{{ $div->code }} - {{ $div->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Area (sq.m.) <span class="text-red-500">*</span></label>
                            <input type="number" name="area" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee per unit and amount are auto-computed based on division and area range.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'ELEC')
            {{-- Electrical Fee Form (BOPMS-style) --}}
            @if(!$isFinalized)
            <div x-data="{
                selected: '',
                feeTypeCode: '',
                poleType: '',
                occupancyType: '',
                kva: '',
                get showKva() { return ['tcl','trans','ups'].includes(this.selected); },
                get showOccupancy() { return ['meter','wiring'].includes(this.selected); },
                setSelection(val) {
                    this.selected = val;
                    this.kva = '';
                    this.poleType = '';
                    this.occupancyType = '';
                    const map = {
                        tcl: 'ELEC_TCL', trans: 'ELEC_TRANS', ups: 'ELEC_UPS',
                        pole_supply: 'ELEC_POLE', pole_guying: 'ELEC_POLE',
                        meter: 'ELEC_MISC_METER', wiring: 'ELEC_MISC_WIRING'
                    };
                    this.feeTypeCode = map[val] || '';
                    if (val === 'pole_supply') this.poleType = 'Power Supply Pole Location';
                    if (val === 'pole_guying') this.poleType = 'Guying Attachment';
                }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Electrical Fee Item
                </h4>
                <form action="{{ route('assessments.electricalItem', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="electrical_fee_type" :value="feeTypeCode">
                    <input type="hidden" name="pole_type" :value="poleType">
                    <input type="hidden" name="occupancy_type" :value="occupancyType">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Electrical Fee Type <span class="text-red-500">*</span></label>
                            <select x-model="selected" @change="setSelection($event.target.value)" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <option value="tcl">Total Connected Load (kVA)</option>
                                <option value="trans">Total Transformer Capacity (kVA)</option>
                                <option value="ups">Total UPS/Generator Capacity (kVA)</option>
                                <option value="pole_supply">Power Supply Pole Location</option>
                                <option value="pole_guying">Guying Attachment</option>
                                <option value="meter">Electric Meter Fee</option>
                                <option value="wiring">Wiring Permit Issuance</option>
                            </select>
                        </div>
                        <div x-show="showKva" x-cloak>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Capacity (kVA) <span class="text-red-500">*</span></label>
                            <input type="number" name="kva" x-model="kva" step="0.01" min="0.01" :required="showKva"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div x-show="showOccupancy" x-cloak>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Occupancy Type <span class="text-red-500">*</span></label>
                            <select x-model="occupancyType" :required="showOccupancy"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <option value="Residential">Residential</option>
                                <option value="Commercial/Industrial">Commercial/Industrial</option>
                                <option value="Institutional">Institutional</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee and amount are auto-computed based on BOPMS electrical fee schedule.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'PLUMB')
            {{-- Plumbing Fee Form (BOPMS-style) --}}
            @if(!$isFinalized)
            <div x-data="{
                feeCode: '',
                unitLabels: {
                    PLUMB_INSTALL:'unit(s)',
                    PLUMB_FIX_WC:'fixture(s)', PLUMB_FIX_FD:'fixture(s)', PLUMB_FIX_SINK:'fixture(s)',
                    PLUMB_FIX_LAV:'fixture(s)', PLUMB_FIX_FAUCET:'fixture(s)', PLUMB_FIX_SHOWER:'fixture(s)',
                    PLUMB_SP_SLOP:'fixture(s)', PLUMB_SP_URINAL:'fixture(s)', PLUMB_SP_BATH:'fixture(s)',
                    PLUMB_SP_GREASE:'fixture(s)', PLUMB_SP_GARAGE:'fixture(s)', PLUMB_SP_BIDET:'fixture(s)',
                    PLUMB_SP_DENTAL:'fixture(s)', PLUMB_SP_GWH:'fixture(s)', PLUMB_SP_DRINK:'fixture(s)',
                    PLUMB_SP_BAR:'fixture(s)', PLUMB_SP_LAUNDRY:'fixture(s)', PLUMB_SP_LAB:'fixture(s)',
                    PLUMB_SP_STERIL:'fixture(s)',
                    PLUMB_WATER_METER:'mm (diameter)', PLUMB_SEPTIC:'cu. meter(s)'
                },
                get unitLabel() { return this.unitLabels[this.feeCode] || 'unit'; }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Plumbing Fee Item
                </h4>
                <form action="{{ route('assessments.plumbingItem', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="plumbing_fee_type" :value="feeCode">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Plumbing Fee <span class="text-red-500">*</span></label>
                            <select @change="feeCode = $event.target.value" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <optgroup label="Installation:">
                                    <option value="PLUMB_INSTALL">Installation Fee (per unit: 1 WC, 2 FD, 1 lav, 1 sink, 3 faucets, 1 shower)</option>
                                </optgroup>
                                <optgroup label="Every Fixture:">
                                    <option value="PLUMB_FIX_WC">Water Closet</option>
                                    <option value="PLUMB_FIX_FD">Floor Drain</option>
                                    <option value="PLUMB_FIX_SINK">Sink</option>
                                    <option value="PLUMB_FIX_LAV">Lavatory</option>
                                    <option value="PLUMB_FIX_FAUCET">Faucet</option>
                                    <option value="PLUMB_FIX_SHOWER">Shower Head</option>
                                </optgroup>
                                <optgroup label="Special Plumbing Fixtures:">
                                    <option value="PLUMB_SP_SLOP">Slop Sink</option>
                                    <option value="PLUMB_SP_URINAL">Urinal</option>
                                    <option value="PLUMB_SP_BATH">Bath Tub</option>
                                    <option value="PLUMB_SP_GREASE">Grease Trap</option>
                                    <option value="PLUMB_SP_GARAGE">Garage Trap</option>
                                    <option value="PLUMB_SP_BIDET">Bidet</option>
                                    <option value="PLUMB_SP_DENTAL">Dental Cuspidor</option>
                                    <option value="PLUMB_SP_GWH">Gas-fired Water Heater</option>
                                    <option value="PLUMB_SP_DRINK">Drinking Fountain</option>
                                    <option value="PLUMB_SP_BAR">Bar / Soda Fountain Sink</option>
                                    <option value="PLUMB_SP_LAUNDRY">Laundry Sink</option>
                                    <option value="PLUMB_SP_LAB">Laboratory Sink</option>
                                    <option value="PLUMB_SP_STERIL">Fixed-type Sterilizer</option>
                                </optgroup>
                                <optgroup label="Range-Based:">
                                    <option value="PLUMB_WATER_METER">Water Meter Fee (by diameter in mm)</option>
                                    <option value="PLUMB_SEPTIC">Septic Tank Fee (by cu. meter volume)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                Unit
                                <span x-show="feeCode" x-cloak class="ml-1 text-blue-600 font-semibold" x-text="'(' + unitLabel + ')'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee and amount are auto-computed based on BOPMS plumbing fee schedule.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'ELECT')
            {{-- Electronics Fee Form (BOPMS-style) --}}
            @if(!$isFinalized)
            <div x-data="{
                feeCode: '',
                unitLabels: {
                    ELECT_SWITCH:'outlet(s)', ELECT_BROADCAST:'unit(s)', ELECT_ATM:'unit(s)',
                    ELECT_OUTLET:'outlet(s)', ELECT_SECURITY:'outlet(s)', ELECT_STUDIO:'unit(s)',
                    ELECT_TOWER:'unit(s)', ELECT_SIGNAGE:'unit(s)',
                    ELECT_POLE:'pole(s)', ELECT_ATTACH:'attachment(s)', ELECT_OTHER:'unit(s)'
                },
                get unitLabel() { return this.unitLabels[this.feeCode] || 'unit'; }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Electronics Fee Item
                </h4>
                <form action="{{ route('assessments.electronicsItem', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="electronics_fee_type" :value="feeCode">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Electronics Fee Type <span class="text-red-500">*</span></label>
                            <select @change="feeCode = $event.target.value" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <optgroup label="Communications &amp; Broadcasting:">
                                    <option value="ELECT_SWITCH">Central Office Switching Equipment / PABX / PBX / Communications Systems</option>
                                    <option value="ELECT_BROADCAST">Broadcast Station / CATV / Cell Sites / Communications Centers</option>
                                    <option value="ELECT_STUDIO">Studios / Auditoriums / Theaters for Broadcasting</option>
                                    <option value="ELECT_TOWER">Antenna Towers / Masts for Transmission / Reception</option>
                                </optgroup>
                                <optgroup label="Devices, Outlets &amp; Systems:">
                                    <option value="ELECT_ATM">ATM / Ticketing / Vending / Medical Equipment / Electronic Devices</option>
                                    <option value="ELECT_OUTLET">Electronics / Communications Outlets (voice, data, video)</option>
                                    <option value="ELECT_SECURITY">Security / Alarm / Fire Alarm / CATV / CCTV Systems Outlets</option>
                                    <option value="ELECT_SIGNAGE">Electronic Signage and Display Systems</option>
                                </optgroup>
                                <optgroup label="Pole &amp; Attachment Fees:">
                                    <option value="ELECT_POLE">Pole Location Fee (per pole)</option>
                                    <option value="ELECT_ATTACH">Pole Attachment Fee (per attachment)</option>
                                </optgroup>
                                <optgroup label="Other:">
                                    <option value="ELECT_OTHER">Other Electronics Devices / Equipment</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                Unit
                                <span x-show="feeCode" x-cloak class="ml-1 text-blue-600 font-semibold" x-text="'(' + unitLabel + ')'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee and amount are auto-computed based on BOPMS electronics fee schedule.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'ACC_BLDG')
            {{-- Accessory Fee Form (BOPMS-style) --}}
            @if(!$isFinalized)
            <div x-data="{
                feeCode: '',
                unitLabels: {
                    ACC_OPEN_PARTS:'base fee (PHP)', ACC_HEIGHT:'cu.m.', ACC_VAULT:'sq.m.', ACC_FIREWALL:'sq.m.',
                    ACC_POOL_RES:'sq.m.', ACC_POOL_COM:'sq.m.', ACC_POOL_SOC:'sq.m.', ACC_POOL_INDIG:'sq.m.',
                    ACC_POOL_SHR_RES:'sq.m.', ACC_POOL_SHR_COM:'sq.m.', ACC_POOL_SHR_SOC:'sq.m.',
                    ACC_TOWER_RES:'unit(s)', ACC_TOWER_COM_SS:'meter(s)', ACC_TOWER_COM_TG:'meter(s)',
                    ACC_TOWER_EDU_SS:'meter(s)', ACC_TOWER_EDU_TG:'meter(s)',
                    ACC_SILO:'meter(s)', ACC_SMOKESTACK:'meter(s)', ACC_CHIMNEY:'meter(s)',
                    ACC_OVEN:'sq.m.', ACC_KILN:'cu.m.',
                    ACC_RC_TANK_AG:'cu.m.', ACC_RC_TANK_UG:'cu.m.', ACC_WATER_TREAT:'cu.m.',
                    ACC_TANK_AG_SM:'cu.m.', ACC_TANK_AG_LG:'cu.m.',
                    ACC_PULL_UG:'cu.m.', ACC_PULL_SADDLE:'cu.m.', ACC_REINST_SM:'cu.m.', ACC_REINST_LG:'cu.m.',
                    ACC_BOOTH_PERM:'sq.m.', ACC_BOOTH_TEMP:'sq.m.', ACC_BOOTH_KNOCK:'unit(s)',
                    ACC_CEM_TOMB:'sq.m.', ACC_CEM_SEMI:'sq.m.', ACC_CEM_ENCLOSED:'sq.m.', ACC_CEM_MULTI:'sq.m.', ACC_CEM_COLUMB:'sq.m.'
                },
                get unitLabel() { return this.unitLabels[this.feeCode] || 'unit'; }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Accessory Fee Item
                </h4>
                <form action="{{ route('assessments.accessoryItem', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="accessory_fee_type" :value="feeCode">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Accessory Fee Type <span class="text-red-500">*</span></label>
                            <select @change="feeCode = $event.target.value" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <optgroup label="General:">
                                    <option value="ACC_OPEN_PARTS">Open Parts of Buildings (balconies, terraces, lanais)</option>
                                    <option value="ACC_HEIGHT">Buildings with Height &gt; 8.00m (per cu.m above 8m)</option>
                                    <option value="ACC_VAULT">Bank and Records Vaults</option>
                                    <option value="ACC_FIREWALL">Firewalls (per sq.m)</option>
                                </optgroup>
                                <optgroup label="Swimming Pools:">
                                    <option value="ACC_POOL_RES">Swimming Pool - GROUP A Residential</option>
                                    <option value="ACC_POOL_COM">Swimming Pool - Commercial/Industrial (B, E, F, G)</option>
                                    <option value="ACC_POOL_SOC">Swimming Pool - Social/Recreational/Institutional (C, D, H, I)</option>
                                    <option value="ACC_POOL_INDIG">Swimming Pool - Indigenous Materials</option>
                                    <option value="ACC_POOL_SHR_RES">Pool Shower/Locker - Residential GROUP A</option>
                                    <option value="ACC_POOL_SHR_COM">Pool Shower/Locker - Commercial (B, E, F, G)</option>
                                    <option value="ACC_POOL_SHR_SOC">Pool Shower/Locker - Social (C, D, H)</option>
                                </optgroup>
                                <optgroup label="Towers &amp; Structures:">
                                    <option value="ACC_TOWER_RES">Tower - Single Detached Dwelling Units</option>
                                    <option value="ACC_TOWER_COM_SS">Tower - Commercial/Industrial (Self-Supporting)</option>
                                    <option value="ACC_TOWER_COM_TG">Tower - Commercial/Industrial (Trilon/Guyed)</option>
                                    <option value="ACC_TOWER_EDU_SS">Tower - Educational/Recreational/Institutional (Self-Supporting)</option>
                                    <option value="ACC_TOWER_EDU_TG">Tower - Educational/Recreational/Institutional (Trilon/Guyed)</option>
                                </optgroup>
                                <optgroup label="Industrial:">
                                    <option value="ACC_SILO">Storage Silos (per meter)</option>
                                    <option value="ACC_SMOKESTACK">Construction of Smokestacks</option>
                                    <option value="ACC_CHIMNEY">Construction of Chimney</option>
                                    <option value="ACC_OVEN">Commercial/Industrial Fixed Ovens (per sq.m interior)</option>
                                    <option value="ACC_KILN">Industrial Kiln/Furnace (per cu.m volume)</option>
                                </optgroup>
                                <optgroup label="Tanks &amp; Storage:">
                                    <option value="ACC_RC_TANK_AG">Reinforced Concrete/Steel Tanks - Above Ground</option>
                                    <option value="ACC_RC_TANK_UG">Reinforced Concrete/Steel Tanks - Underground</option>
                                    <option value="ACC_WATER_TREAT">Water/Waste Water Treatment Tanks (per cu.m)</option>
                                    <option value="ACC_TANK_AG_SM">Tanks Above Ground - Small (Groups 1-2)</option>
                                    <option value="ACC_TANK_AG_LG">Tanks Above Ground - Large (Groups 3-10)</option>
                                    <option value="ACC_PULL_UG">Pullout/Reinstallation - Underground (per cu.m)</option>
                                    <option value="ACC_PULL_SADDLE">Pullout/Reinstallation - Saddle/Trestle Mounted (per cu.m)</option>
                                    <option value="ACC_REINST_SM">Reinstallation Vertical Storage Tanks - Small (Groups 1-2)</option>
                                    <option value="ACC_REINST_LG">Reinstallation Vertical Storage Tanks - Large (Groups 3-10)</option>
                                </optgroup>
                                <optgroup label="Booths &amp; Kiosks:">
                                    <option value="ACC_BOOTH_PERM">Booth/Kiosk/Platform - Permanent Type (per sq.m)</option>
                                    <option value="ACC_BOOTH_TEMP">Booth/Kiosk/Platform - Temporary Type (per sq.m)</option>
                                    <option value="ACC_BOOTH_KNOCK">Booth/Kiosk/Platform - Knock-down Temporary (per unit)</option>
                                </optgroup>
                                <optgroup label="Cemetery Structures:">
                                    <option value="ACC_CEM_TOMB">Tombs (per sq.m covered ground area)</option>
                                    <option value="ACC_CEM_SEMI">Semi-enclosed Mausoleums (per sq.m)</option>
                                    <option value="ACC_CEM_ENCLOSED">Totally Enclosed Mausoleums (per sq.m floor area)</option>
                                    <option value="ACC_CEM_MULTI">Multi-level Interment (per sq.m per level)</option>
                                    <option value="ACC_CEM_COLUMB">Columbarium (per sq.m)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                Unit
                                <span x-show="feeCode" x-cloak class="ml-1 text-blue-600 font-semibold" x-text="'(' + unitLabel + ')'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee and amount are auto-computed based on BOPMS accessory building fee schedule.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'ACC_FEE')
            {{-- Accessory Misc. Fee Form (BOPMS-style) --}}
            @if(!$isFinalized)
            <div x-data="{
                feeCode: '',
                unitLabels: {
                    ASS_LINE_GRADE:'linear meter(s)',
                    ASS_GP_INSPECT:'inspection(s)', ASS_GP_EXCAV:'cu.m.', ASS_GP_ISSUANCE:'permit(s)',
                    ASS_GP_FOUND:'cu.m.', ASS_GP_OTHER:'cu.m.', ASS_GP_ENCROACH:'sq.m.',
                    ASS_FENCE_MASONRY:'meter(s) height', ASS_FENCE_INDIG:'linear meter(s)',
                    ASS_PAVEMENT:'sq.m.', ASS_SIDEWALK:'sq.m.', ASS_SCAFFOLD:'sq.m.',
                    ASS_SIGN_ERECT:'sq.m.',
                    'ASS_SIGN_INSTALL|Business|Neon':'sq.m.', 'ASS_SIGN_INSTALL|Advertising|Neon':'sq.m.',
                    'ASS_SIGN_INSTALL|Business|Illuminated':'sq.m.', 'ASS_SIGN_INSTALL|Advertising|Illuminated':'sq.m.',
                    'ASS_SIGN_INSTALL|Business|Painted-on':'sq.m.', 'ASS_SIGN_INSTALL|Advertising|Painted-on':'sq.m.',
                    'ASS_SIGN_INSTALL|Business|Others':'sq.m.', 'ASS_SIGN_INSTALL|Advertising|Others':'sq.m.',
                    'ASS_SIGN_RENEW|Business|Neon':'sq.m.', 'ASS_SIGN_RENEW|Advertising|Neon':'sq.m.',
                    'ASS_SIGN_RENEW|Business|Illuminated':'sq.m.', 'ASS_SIGN_RENEW|Advertising|Illuminated':'sq.m.',
                    'ASS_SIGN_RENEW|Business|Painted-on':'sq.m.', 'ASS_SIGN_RENEW|Advertising|Painted-on':'sq.m.',
                    'ASS_SIGN_RENEW|Business|Others':'sq.m.', 'ASS_SIGN_RENEW|Advertising|Others':'sq.m.',
                    ASS_REPAIR_VERT:'sq.m.', ASS_REPAIR_HORIZ:'sq.m.', ASS_REPAIR_COST:'repair cost (PHP)',
                    ASS_DEMO_BLDG:'sq.m.', ASS_DEMO_FRAME:'dimension', ASS_DEMO_MOVE:'sq.m.',
                    ASS_DEMO_STRUCT:'cu.m.', ASS_DEMO_APPEND:'cu.m.'
                },
                get unitLabel() { return this.unitLabels[this.feeCode] || 'unit'; }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Accessory Misc. Fee Item
                </h4>
                <form action="{{ route('assessments.accFeeItem', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="acc_fee_type" :value="feeCode">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Fee Type <span class="text-red-500">*</span></label>
                            <select @change="feeCode = $event.target.value" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <optgroup label="Line &amp; Grade:">
                                    <option value="ASS_LINE_GRADE">Establishment of Line and Grade</option>
                                </optgroup>
                                <optgroup label="Ground Preparation &amp; Excavation:">
                                    <option value="ASS_GP_INSPECT">GP - Inspection and Verification Fee</option>
                                    <option value="ASS_GP_EXCAV">GP - Per cu.m of Excavation</option>
                                    <option value="ASS_GP_ISSUANCE">GP - Issuance of GP &amp; EP (valid 30 days)</option>
                                    <option value="ASS_GP_FOUND">GP - Excavation for Foundation with Basement (per cu.m)</option>
                                    <option value="ASS_GP_OTHER">GP - Excavation Other than Foundation (per cu.m)</option>
                                    <option value="ASS_GP_ENCROACH">GP - Encroachment of Footings to Public Areas</option>
                                </optgroup>
                                <optgroup label="Fencing:">
                                    <option value="ASS_FENCE_MASONRY">Fencing - Masonry/Metal/Concrete</option>
                                    <option value="ASS_FENCE_INDIG">Fencing - Indigenous/Barbed/Chicken/Hog Wire (per linear meter)</option>
                                </optgroup>
                                <optgroup label="Pavement, Streets &amp; Scaffolding:">
                                    <option value="ASS_PAVEMENT">Construction of Pavement</option>
                                    <option value="ASS_SIDEWALK">Use of Streets and Sidewalks</option>
                                    <option value="ASS_SCAFFOLD">Erection of Scaffoldings (per calendar month)</option>
                                </optgroup>
                                <optgroup label="Signs:">
                                    <option value="ASS_SIGN_ERECT">Sign - Erection/Anchorage of Display Surface</option>
                                    <option value="ASS_SIGN_INSTALL|Business|Neon">Sign Install - Business / Neon</option>
                                    <option value="ASS_SIGN_INSTALL|Advertising|Neon">Sign Install - Advertising / Neon</option>
                                    <option value="ASS_SIGN_INSTALL|Business|Illuminated">Sign Install - Business / Illuminated</option>
                                    <option value="ASS_SIGN_INSTALL|Advertising|Illuminated">Sign Install - Advertising / Illuminated</option>
                                    <option value="ASS_SIGN_INSTALL|Business|Painted-on">Sign Install - Business / Painted-on</option>
                                    <option value="ASS_SIGN_INSTALL|Advertising|Painted-on">Sign Install - Advertising / Painted-on</option>
                                    <option value="ASS_SIGN_INSTALL|Business|Others">Sign Install - Business / Others</option>
                                    <option value="ASS_SIGN_INSTALL|Advertising|Others">Sign Install - Advertising / Others</option>
                                    <option value="ASS_SIGN_RENEW|Business|Neon">Sign Renewal - Business / Neon</option>
                                    <option value="ASS_SIGN_RENEW|Advertising|Neon">Sign Renewal - Advertising / Neon</option>
                                    <option value="ASS_SIGN_RENEW|Business|Illuminated">Sign Renewal - Business / Illuminated</option>
                                    <option value="ASS_SIGN_RENEW|Advertising|Illuminated">Sign Renewal - Advertising / Illuminated</option>
                                    <option value="ASS_SIGN_RENEW|Business|Painted-on">Sign Renewal - Business / Painted-on</option>
                                    <option value="ASS_SIGN_RENEW|Advertising|Painted-on">Sign Renewal - Advertising / Painted-on</option>
                                    <option value="ASS_SIGN_RENEW|Business|Others">Sign Renewal - Business / Others</option>
                                    <option value="ASS_SIGN_RENEW|Advertising|Others">Sign Renewal - Advertising / Others</option>
                                </optgroup>
                                <optgroup label="Repairs &amp; Renovations:">
                                    <option value="ASS_REPAIR_VERT">Repairs - Alteration/Renovation on Vertical Dimensions (per sq.m)</option>
                                    <option value="ASS_REPAIR_HORIZ">Repairs - Alteration/Renovation on Horizontal Dimensions (per sq.m)</option>
                                    <option value="ASS_REPAIR_COST">Repairs - Costing more than ₱5,000 (1% of cost)</option>
                                </optgroup>
                                <optgroup label="Demolition:">
                                    <option value="ASS_DEMO_BLDG">Demolition - Buildings (per sq.m floor area)</option>
                                    <option value="ASS_DEMO_FRAME">Demolition - Building Systems/Frames (per dimension)</option>
                                    <option value="ASS_DEMO_MOVE">Moving Fee (per sq.m of area to be moved)</option>
                                    <option value="ASS_DEMO_STRUCT">Demolition - Structures (range-based)</option>
                                    <option value="ASS_DEMO_APPEND">Demolition - Appendages (range-based)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                Unit
                                <span x-show="feeCode" x-cloak class="ml-1 text-blue-600 font-semibold" x-text="'(' + unitLabel + ')'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee and amount are auto-computed based on BOPMS accessory miscellaneous fee schedule.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'SURCHARGE')
            {{-- Surcharge Form --}}
            @if(!$isFinalized)
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Surcharge
                </h4>
                <form action="{{ route('assessments.surchargeItem', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Surcharge Type <span class="text-red-500">*</span></label>
                            <select name="surcharge_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <optgroup label="Violations (Fixed Amount):">
                                    <option value="SURCHARGE_LIGHT">Light Violation — ₱5,000</option>
                                    <option value="SURCHARGE_LESS">Less Grave Violation — ₱8,000</option>
                                    <option value="SURCHARGE_GRAVE">Grave Violation — ₱10,000</option>
                                </optgroup>
                                <optgroup label="Construction Without Permit (% of Total BP Fee):">
                                    <option value="SURCHARGE_EXCAV">Excavation for Foundation — 10%</option>
                                    <option value="SURCHARGE_FOUND">Construction of Foundation (incl. pile driving &amp; rebar) — 25%</option>
                                    <option value="SURCHARGE_SUPER2">Superstructure up to 2.00m above grade — 50%</option>
                                    <option value="SURCHARGE_SUPER">Superstructure above 2.00m above grade — 100%</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Violation surcharges use a fixed penalty amount. Construction-stage surcharges are computed as a percentage of the total BP assessment (Construction + Electrical + Mechanical + Plumbing + Electronics + Accessory Building + Accessory Misc. amounts) at the time of adding.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'MECH')
            {{-- Mechanical Fee Form (BOPMS-style) --}}
            @if(!$isFinalized)
            <div x-data="{
                feeCode: '',
                unitLabels: {
                    MECH_REFRIG:'ton(s)', MECH_ICE:'ton(s)', MECH_CENTRAL_AC:'ton(s) TR',
                    MECH_WINDOW_AC:'unit(s)', MECH_VENT:'kW',
                    MECH_ESC_KW:'kW', MECH_ESC_RANGE:'lineal meter(s)',
                    MECH_FUNIC_KW:'kW', MECH_FUNIC_LM:'lineal meter(s)',
                    MECH_CABLE_KW:'kW', MECH_CABLE_LM:'lineal meter(s)',
                    MECH_ELEV_DUMB:'unit(s)', MECH_ELEV_CONST:'unit(s)',
                    MECH_ELEV_PASS:'unit(s)', MECH_ELEV_FRT:'unit(s)', MECH_ELEV_CAR:'unit(s)',
                    MECH_BOILER:'kW', MECH_DIESEL:'kW', MECH_INT_COMB:'kW',
                    MECH_WATER_HEATER:'unit(s)', MECH_WATER_PUMP:'kW',
                    MECH_SPRINKLER:'head(s)', MECH_COMPRESSED:'outlet(s)',
                    MECH_GAS_METER:'unit(s)', MECH_POWER_PIPE:'lineal meter(s)',
                    MECH_PRESSURE_V:'cu. meter(s)', MECH_OTHER_EQUIP:'kW',
                    MECH_PNEUMATIC:'lineal meter(s)', MECH_WEIGH_SCALE:'ton(s)'
                },
                get unitLabel() { return this.unitLabels[this.feeCode] || 'unit'; }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Mechanical Fee Item
                </h4>
                <form action="{{ route('assessments.mechanicalItem', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="mechanical_fee_type" :value="feeCode">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Mechanical Fee <span class="text-red-500">*</span></label>
                            <select @change="feeCode = $event.target.value" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <optgroup label="a. Refrigeration, Air Conditioning and Mech. Ventilation:">
                                    <option value="MECH_REFRIG">i. Refrigeration (cold storage), per ton or fraction thereof</option>
                                    <option value="MECH_ICE">ii. Ice Plants, per ton or fraction thereof</option>
                                    <option value="MECH_CENTRAL_AC">iii. Packaged/Centralized Air Conditioning Systems</option>
                                    <option value="MECH_WINDOW_AC">v. Window type air conditioners, per unit</option>
                                    <option value="MECH_VENT">vi. Mechanical Ventilation, per kW or fraction thereof</option>
                                </optgroup>
                                <optgroup label="b. Escalators and Moving Walks, Funiculars and the like:">
                                    <option value="MECH_ESC_KW">i. Escalator and moving walk, per kW or fraction thereof</option>
                                    <option value="MECH_ESC_RANGE">ii. Escalator and moving walks, per lineal meter travel</option>
                                    <option value="MECH_FUNIC_KW">iv. Funicular, per kW or fraction thereof</option>
                                    <option value="MECH_FUNIC_LM">iv.a. Funicular, per lineal meter travel</option>
                                    <option value="MECH_CABLE_KW">v. Cable car, per kW or fraction thereof</option>
                                    <option value="MECH_CABLE_LM">v.a. Cable car, per lineal meter travel</option>
                                </optgroup>
                                <optgroup label="c. Elevators per unit:">
                                    <option value="MECH_ELEV_DUMB">i. Motor driven dumbwaiters</option>
                                    <option value="MECH_ELEV_CONST">ii. Construction elevators for material</option>
                                    <option value="MECH_ELEV_PASS">iii. Passenger elevators</option>
                                    <option value="MECH_ELEV_FRT">iv. Freight elevators</option>
                                    <option value="MECH_ELEV_CAR">v. Car elevators</option>
                                </optgroup>
                                <optgroup label="Others:">
                                    <option value="MECH_BOILER">d. Boilers, per rated capacity in kW</option>
                                    <option value="MECH_WATER_HEATER">e. Pressurized water heaters, per unit</option>
                                    <option value="MECH_WATER_PUMP">f. Water/sump/sewage pumps (commercial/industrial), per kW</option>
                                    <option value="MECH_SPRINKLER">g. Automatic fire sprinkler system, per sprinkler head</option>
                                    <option value="MECH_DIESEL">h. Diesel/Gasoline ICE, Steam, Gas Turbine/Engine and the like, per kW</option>
                                    <option value="MECH_COMPRESSED">i. Compressed Air/Vacuum/Industrial Gases, per outlet</option>
                                    <option value="MECH_GAS_METER">j. Gas Meter, per unit</option>
                                    <option value="MECH_POWER_PIPE">k. Power piping for gas/steam/etc., per lineal meter</option>
                                    <option value="MECH_INT_COMB">l. Other Internal Combustion Engines (cranes, forklifts, etc.), per kW</option>
                                    <option value="MECH_PRESSURE_V">m. Pressure Vessels, per cu. meter</option>
                                    <option value="MECH_OTHER_EQUIP">n. Other Machinery/Equipment (commercial/industrial), per kW</option>
                                    <option value="MECH_PNEUMATIC">o. Pneumatic tubes/Conveyors/Monorails, per lineal meter</option>
                                    <option value="MECH_WEIGH_SCALE">p. Weighing Scale Structure, per ton</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                Unit
                                <span x-show="feeCode" x-cloak class="ml-1 text-blue-600 font-semibold" x-text="'(' + unitLabel + ')'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee and amount are auto-computed based on BOPMS mechanical fee schedule.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'OCC')
            {{-- Occupancy Fee Form (BOPMS-style) --}}
            @if(!$isFinalized)
            <div x-data="{
                feeCode: '',
                unitLabels: {
                    'OCC_DIV_A':      'Costing (₱)',
                    'OCC_DIV_B':      'Costing (₱)',
                    'OCC_DIV_CD':     'Costing (₱)',
                    'OCC_DIV_J1':     'Area (sq.m)',
                    'OCC_DIV_J2_RATE':'Amount (₱)',
                    'OCC_DIV_J2_E2':  'Area (sq.m)',
                    'OCC_DIV_J2_E3':  'Meters / Units',
                    'OCC_CHANGE_USE': 'Area (sq.m)',
                },
                get unitLabel() { return this.unitLabels[this.feeCode] || 'unit'; }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Occupancy Fee Item
                </h4>
                <form action="{{ route('assessments.occupancyFeeItem', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="occupancy_fee_type" :value="feeCode">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Occupancy Fee Type <span class="text-red-500">*</span></label>
                            <select @change="feeCode = $event.target.value" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <option value="OCC_DIV_A">Division A – Residential (by construction cost)</option>
                                <option value="OCC_DIV_B">Division B – Residential Hotel (by construction cost)</option>
                                <option value="OCC_DIV_CD">Division C/D – Educational/Institutional (by construction cost)</option>
                                <option value="OCC_DIV_J1">Division J-I – Agricultural/Special (by floor area)</option>
                                <option value="OCC_DIV_J2_RATE">Division J-II – Garages/Carports/Balconies (50% of principal rate)</option>
                                <option value="OCC_DIV_J2_E2">Division J-II – Aviaries/Aquariums/Zoo (by floor area)</option>
                                <option value="OCC_DIV_J2_E3">Division J-II – Towers: Radio/TV/Cell (by meters/units)</option>
                                <option value="OCC_CHANGE_USE">Change in Use/Occupancy (per sq.m affected)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                Unit
                                <span x-show="feeCode" x-cloak class="ml-1 text-blue-600 font-semibold" x-text="'(' + unitLabel + ')'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee and amount are auto-computed based on BOPMS occupancy fee schedule.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'DEMO_FEE')
            {{-- Demolition Fee Form (quantity is measured in the Unit configured in Demolition Fee Settings) --}}
            @if(!$isFinalized)
            <div x-data="{
                feeCode: '',
                unitLabels: {
                    @foreach($catFeeTypes as $ft)
                    {{ $ft->code }}: '{{ $ft->unit_label ?? 'unit' }}',
                    @endforeach
                },
                get unitLabel() { return this.unitLabels[this.feeCode] || 'unit'; }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Demolition Fee Item
                </h4>
                <form action="{{ route('assessments.demolitionItem.dp', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Demolition Fee <span class="text-red-500">*</span></label>
                            <select name="demolition_fee_type" @change="feeCode = $event.target.value" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                @foreach($catFeeTypes as $ft)
                                <option value="{{ $ft->code }}">{{ $ft->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                Quantity
                                <span x-show="feeCode" x-cloak class="ml-1 text-blue-600 font-semibold" x-text="'(' + unitLabel + ')'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="quantity" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee and amount are auto-computed based on the Demolition Fee Settings schedule.</p>
                </form>
            </div>
            @endif
            @elseif($cat->code === 'FP_FEE')
            {{-- Fencing Fee Form — reuses the ASS_FENCE_MASONRY/ASS_FENCE_INDIG/ASS_LINE_GRADE/
                 ASS_GP_* rates from Settings > Fee Schedules > Accessory. --}}
            @if(!$isFinalized)
            <div x-data="{
                feeCode: '',
                unitLabels: {
                    ASS_LINE_GRADE: 'linear meter(s)',
                    ASS_GP_INSPECT: 'inspection(s)', ASS_GP_EXCAV: 'cu.m.', ASS_GP_ISSUANCE: 'permit(s)',
                    ASS_GP_FOUND: 'cu.m.', ASS_GP_OTHER: 'cu.m.', ASS_GP_ENCROACH: 'sq.m.',
                    ASS_FENCE_MASONRY: 'meter(s) height',
                    ASS_FENCE_INDIG: 'linear meter(s)',
                },
                get unitLabel() { return this.unitLabels[this.feeCode] || 'unit'; }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add Fencing Fee Item
                </h4>
                <form action="{{ route('assessments.addFenceItem.fp', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Fencing Fee <span class="text-red-500">*</span></label>
                            <select name="fence_fee_type" @change="feeCode = $event.target.value" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                <optgroup label="Line &amp; Grade:">
                                    <option value="ASS_LINE_GRADE">Establishment of Line and Grade</option>
                                </optgroup>
                                <optgroup label="Ground Preparation &amp; Excavation:">
                                    <option value="ASS_GP_INSPECT">GP - Inspection and Verification Fee</option>
                                    <option value="ASS_GP_EXCAV">GP - Per cu.m of Excavation</option>
                                    <option value="ASS_GP_ISSUANCE">GP - Issuance of GP &amp; EP (valid 30 days)</option>
                                    <option value="ASS_GP_FOUND">GP - Excavation for Foundation with Basement (per cu.m)</option>
                                    <option value="ASS_GP_OTHER">GP - Excavation Other than Foundation (per cu.m)</option>
                                    <option value="ASS_GP_ENCROACH">GP - Encroachment of Footings to Public Areas</option>
                                </optgroup>
                                <optgroup label="Fencing:">
                                    <option value="ASS_FENCE_MASONRY">Fencing - Masonry/Metal/Concrete</option>
                                    <option value="ASS_FENCE_INDIG">Fencing - Indigenous/Barbed/Chicken/Hog Wire (per linear meter)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                Unit
                                <span x-show="feeCode" x-cloak class="ml-1 text-blue-600 font-semibold" x-text="'(' + unitLabel + ')'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Fee and amount are auto-computed based on the Accessory fee schedule.</p>
                </form>
            </div>
            @endif
            @elseif(in_array($cat->code, ['MP_AC', 'MP_MACH', 'MP_ESC', 'MP_ELEV', 'MP_GENSET']))
            {{-- Mechanical Permit equipment tabs — each reuses the existing MECH_*/INSP_* rates
                 from Settings > Fee Schedules > Mechanical. New applications post MECH_* codes
                 (plus a separate inspection-fee line computed server-side); Yearly applications
                 post the matching INSP_* codes directly as the primary fee. --}}
            @php
                $mpGroupCode = substr($cat->code, 3); // MP_AC -> AC, MP_MACH -> MACH, etc.
                $mpIsYearly = ($application->application_kind ?? 'new') === 'yearly';
                $mpOptionGroups = [
                    'AC' => [
                        'label' => 'Air Conditioning / Refrigeration',
                        'options' => [
                            'MECH_REFRIG' => 'Refrigeration (Cold Storage), per ton',
                            'MECH_ICE' => 'Ice Plants, per ton',
                            'MECH_WINDOW_AC' => 'Window Type Air Conditioners, per unit',
                            'MECH_VENT' => 'Mechanical Ventilation, per kW',
                            'MECH_CENTRAL_AC' => 'Packaged/Centralized Air Conditioning Systems',
                        ],
                        'units' => [
                            'REFRIG' => 'ton(s)', 'ICE' => 'ton(s)', 'WINDOW_AC' => 'unit(s)',
                            'VENT' => 'kW', 'CENTRAL_AC' => 'ton(s) TR',
                        ],
                    ],
                    'MACH' => [
                        'label' => 'Machinery',
                        'options' => [
                            'MECH_BOILER' => 'Boilers, per rated capacity in kW',
                            'MECH_DIESEL' => 'Diesel/Gasoline Engines, per kW',
                            'MECH_INT_COMB' => 'Other Internal Combustion Engines, per kW',
                            'MECH_WATER_HEATER' => 'Pressurized Water Heaters, per unit',
                            'MECH_WATER_PUMP' => 'Water/Sump/Sewage Pumps, per kW',
                            'MECH_SPRINKLER' => 'Automatic Fire Sprinkler System, per head',
                            'MECH_COMPRESSED' => 'Compressed Air/Vacuum/Industrial Gases, per outlet',
                            'MECH_GAS_METER' => 'Gas Meter, per unit',
                            'MECH_POWER_PIPE' => 'Power Piping, per lineal meter',
                            'MECH_PRESSURE_V' => 'Pressure Vessels, per cu. meter',
                            'MECH_OTHER_EQUIP' => 'Other Machinery/Equipment, per kW',
                            'MECH_PNEUMATIC' => 'Pneumatic Tubes/Conveyors/Monorails, per lineal meter',
                            'MECH_WEIGH_SCALE' => 'Weighing Scale Structure, per ton',
                        ],
                        'units' => [
                            'BOILER' => 'kW', 'DIESEL' => 'kW', 'INT_COMB' => 'kW',
                            'WATER_HEATER' => 'unit(s)', 'WATER_PUMP' => 'kW', 'SPRINKLER' => 'head(s)',
                            'COMPRESSED' => 'outlet(s)', 'GAS_METER' => 'unit(s)', 'POWER_PIPE' => 'lineal meter(s)',
                            'PRESSURE_V' => 'cu. meter(s)', 'OTHER_EQUIP' => 'kW', 'PNEUMATIC' => 'lineal meter(s)',
                            'WEIGH_SCALE' => 'ton(s)',
                        ],
                    ],
                    'ESC' => [
                        'label' => 'Escalators / Funiculars / Cable Cars',
                        'options' => [
                            'MECH_ESC_KW' => 'Escalator/Moving Walk, per kW',
                            'MECH_ESC_RANGE' => 'Escalator/Moving Walk, per lineal meter travel',
                            'MECH_FUNIC_KW' => 'Funicular, per kW',
                            'MECH_FUNIC_LM' => 'Funicular, per lineal meter travel',
                            'MECH_CABLE_KW' => 'Cable Car, per kW',
                            'MECH_CABLE_LM' => 'Cable Car, per lineal meter travel',
                        ],
                        'units' => [
                            'ESC_KW' => 'kW', 'ESC_RANGE' => 'lineal meter(s)', 'FUNIC_KW' => 'kW',
                            'FUNIC_LM' => 'lineal meter(s)', 'CABLE_KW' => 'kW', 'CABLE_LM' => 'lineal meter(s)',
                        ],
                    ],
                    'ELEV' => [
                        'label' => 'Elevators',
                        'options' => [
                            'MECH_ELEV_DUMB' => 'Motor Driven Dumbwaiters',
                            'MECH_ELEV_CONST' => 'Construction Elevators for Material',
                            'MECH_ELEV_PASS' => 'Passenger Elevators',
                            'MECH_ELEV_FRT' => 'Freight Elevators',
                            'MECH_ELEV_CAR' => 'Car Elevators',
                        ],
                        'units' => [
                            'ELEV_DUMB' => 'unit(s)', 'ELEV_CONST' => 'unit(s)', 'ELEV_PASS' => 'unit(s)',
                            'ELEV_FRT' => 'unit(s)', 'ELEV_CAR' => 'unit(s)',
                        ],
                    ],
                    'GENSET' => [
                        'label' => 'Generator Set',
                        'options' => [
                            'MECH_GENSET' => 'Generator Set, per kVA',
                        ],
                        'units' => [
                            'GENSET' => 'kVA',
                        ],
                    ],
                ][$mpGroupCode];
            @endphp
            @if(!$isFinalized)
            <div x-data="{
                feeCode: '',
                unitLabels: {
                    @foreach($mpOptionGroups['units'] as $suffix => $label)
                    {{ $mpIsYearly ? 'INSP_' . $suffix : 'MECH_' . $suffix }}: '{{ $label }}',
                    @endforeach
                },
                get unitLabel() { return this.unitLabels[this.feeCode] || 'unit'; }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add {{ $mpOptionGroups['label'] }} Item
                </h4>
                <form action="{{ route('assessments.addMechItem.mp', $application) }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="mechanical_group" value="{{ $mpGroupCode }}">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $mpOptionGroups['label'] }} Fee <span class="text-red-500">*</span></label>
                            <select name="mechanical_fee_type" @change="feeCode = $event.target.value" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                @foreach($mpOptionGroups['options'] as $code => $label)
                                <option value="{{ $mpIsYearly ? 'INSP_' . substr($code, 5) : $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">
                                Unit
                                <span x-show="feeCode" x-cloak class="ml-1 text-blue-600 font-semibold" x-text="'(' + unitLabel + ')'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        {{ $mpIsYearly ? 'Annual inspection fee, auto-computed based on the Mechanical inspection fee schedule.' : 'Permit fee (plus inspection fee), auto-computed based on the Mechanical fee schedule.' }}
                    </p>
                </form>
            </div>
            @endif
            @else
            {{-- Generic Fee Item Form (other tabs) --}}
            @if(!$isFinalized)
            <div x-data="{
                quantity: 1,
                unitFee: 0,
                get amount() { return (this.quantity * this.unitFee).toFixed(2); }
            }">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Add {{ $cat->name }} Item
                </h4>
                <form action="{{ $addItemRoute }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="fee_category_id" value="{{ $cat->id }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Fee Type</label>
                            <select name="fee_type_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Select --</option>
                                @foreach($catFeeTypes as $ft)
                                    <option value="{{ $ft->id }}">{{ $ft->code }} - {{ $ft->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Quantity</label>
                            <input type="number" name="quantity" x-model.number="quantity" step="0.01" min="0.01" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Unit Fee</label>
                            <input type="number" name="unit_fee" x-model.number="unitFee" step="0.01" min="0" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Amount</label>
                                <div class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 font-medium">
                                    &#8369;<span x-text="parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">0.00</span>
                                </div>
                            </div>
                            <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            @endif
            @endif

            {{-- Category Items Table --}}
            @if($catItems->count())
            <div class="border-t border-gray-200 pt-4 overflow-x-auto" x-data="{
                selected: [],
                allIds: @js($catItems->pluck('id')->values()),
                get allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
                toggleAll() { this.selected = this.allSelected ? [] : [...this.allIds]; }
            }">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-semibold text-gray-700">{{ $cat->name }} Items</h4>
                    <div class="flex items-center gap-3">
                        <p class="text-xs text-gray-500" x-show="selected.length > 0" x-cloak>
                            <span x-text="selected.length"></span> selected
                        </p>
                        <button x-show="selected.length > 0" x-cloak type="button"
                            @click="
                                if(confirm('Remove ' + selected.length + ' selected item(s)?')) {
                                    selected.forEach(id => {
                                        fetch('/assessments/item/' + id, {
                                            method: 'DELETE',
                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                        });
                                    });
                                    setTimeout(() => window.location.href = window.location.pathname + '?tab={{ $cat->code }}', 300);
                                }
                            "
                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-trash-alt"></i> Remove Selected
                        </button>
                    </div>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-3 w-10">
                                <input type="checkbox" @click="toggleAll()" :checked="allSelected"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            @if($cat->code === 'CONST')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Part of Building</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Division</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Area (sq.m.)</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fee per Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @elseif($cat->code === 'ELEC')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Description</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">kVA/Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fixed Fee</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Additional</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @elseif($cat->code === 'PLUMB')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Plumbing Fee</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fee/Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @elseif($cat->code === 'DEMO_FEE')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Demolition Fee</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Qty</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fee/Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @elseif($cat->code === 'ELECT')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Electronics Fee</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fee/Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @elseif($cat->code === 'ACC_BLDG')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Accessory Fee</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fee/Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @elseif($cat->code === 'ACC_FEE')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Acc. Misc. Fee</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fee/Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @elseif($cat->code === 'SURCHARGE')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Surcharge Type</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @elseif($cat->code === 'MECH')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Mechanical Fee</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fee/Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Excess/Add.</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @else
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Fee Code</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Description</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Qty</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fee per Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @endif
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($catItems as $item)
                        <tr class="hover:bg-gray-50" :class="selected.includes({{ $item->id }}) && 'bg-blue-50'">
                            <td class="px-3 py-3">
                                <input type="checkbox" value="{{ $item->id }}" x-model.number="selected"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </td>
                            @if($cat->code === 'CONST')
                            @php $compDetails = is_array($item->computation_details) ? $item->computation_details : json_decode($item->computation_details ?? '{}', true); @endphp
                            <td class="px-4 py-3 text-gray-900">{{ $compDetails['building_part'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700 text-xs">{{ $compDetails['division_code'] ?? $item->fee_code }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->unit_fee, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @elseif($cat->code === 'ELEC')
                            @php
                                $compDetails = is_array($item->computation_details) ? $item->computation_details : json_decode($item->computation_details ?? '{}', true);
                                $fixedFee = $compDetails['fixed_fee'] ?? 0;
                                $additionalFee = ($item->quantity > 1 || ($compDetails['fee_per_unit'] ?? 0) > 0) ? round($item->quantity * ($compDetails['fee_per_unit'] ?? 0), 2) : 0;
                            @endphp
                            <td class="px-4 py-3 text-gray-900">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ $item->quantity > 1 ? number_format($item->quantity, 2) : '-' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($fixedFee, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($additionalFee, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @elseif($cat->code === 'PLUMB')
                            <td class="px-4 py-3 text-gray-900 text-xs">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">@if($item->unit_fee > 0)&#8369;{{ number_format($item->unit_fee, 2) }}@else-@endif</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @elseif($cat->code === 'DEMO_FEE')
                            @php $compDetails = is_array($item->computation_details) ? $item->computation_details : json_decode($item->computation_details ?? '{}', true); @endphp
                            <td class="px-4 py-3 text-gray-900 text-xs">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }} {{ $compDetails['unit_label'] ?? '' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">@if($item->unit_fee > 0)&#8369;{{ number_format($item->unit_fee, 2) }}@else-@endif</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @elseif($cat->code === 'ELECT')
                            <td class="px-4 py-3 text-gray-900 text-xs">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">@if($item->unit_fee > 0)&#8369;{{ number_format($item->unit_fee, 2) }}@else-@endif</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @elseif($cat->code === 'ACC_BLDG')
                            <td class="px-4 py-3 text-gray-900 text-xs">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">@if($item->unit_fee > 0)&#8369;{{ number_format($item->unit_fee, 2) }}@else-@endif</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @elseif($cat->code === 'ACC_FEE')
                            <td class="px-4 py-3 text-gray-900 text-xs">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">@if($item->unit_fee > 0)&#8369;{{ number_format($item->unit_fee, 2) }}@else-@endif</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @elseif($cat->code === 'SURCHARGE')
                            <td class="px-4 py-3 text-gray-900 text-xs">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @elseif($cat->code === 'MECH')
                            <td class="px-4 py-3 text-gray-900 text-xs">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">@if($item->unit_fee > 0)&#8369;{{ number_format($item->unit_fee, 2) }}@else-@endif</td>
                            <td class="px-4 py-3 text-right text-gray-700">@if($item->excess_fee > 0)&#8369;{{ number_format($item->excess_fee, 2) }}@else-@endif</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @else
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $item->fee_code }}</td>
                            <td class="px-4 py-3 text-gray-900">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->unit_fee, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @endif
                            <td class="px-4 py-3 text-right">
                                @if(!$isFinalized)
                                <form action="{{ route('assessments.removeItem', $item) }}" method="POST" class="inline" onsubmit="return confirm('Remove this item?');" autocomplete="off">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Remove">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            @if($cat->code === 'CONST')
                            <td></td>
                            <td colspan="2" class="px-4 py-3 text-right font-semibold text-gray-700">Total Area</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">{{ number_format($catItems->sum('quantity'), 2) }} sq.m.</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @elseif($cat->code === 'ELEC')
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @elseif($cat->code === 'PLUMB')
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @elseif($cat->code === 'ELECT')
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @elseif($cat->code === 'ACC_BLDG')
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @elseif($cat->code === 'ACC_FEE')
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @elseif($cat->code === 'SURCHARGE')
                            <td colspan="2" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @elseif($cat->code === 'MECH')
                            <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @else
                            <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
        @endforeach

        {{-- Summary Tab Content --}}
        <div x-show="activeTab === 'SUMMARY'" x-cloak class="p-5 space-y-4">
            @foreach($tabCategories as $cat)
            @php $catItems = $itemsByCategory[$cat->id] ?? collect(); @endphp
            @if($catItems->count())
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-gray-800">{{ $cat->name }}</h4>
                    <span class="text-xs text-gray-500">{{ $catItems->count() }} item(s)</span>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 text-xs">Fee Code</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 text-xs">Description</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-500 text-xs">Qty</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-500 text-xs">Unit Fee</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-500 text-xs">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($catItems as $item)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $item->fee_code }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $item->description }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-2 text-right text-gray-700">&#8369;{{ number_format($item->unit_fee, 2) }}</td>
                            <td class="px-4 py-2 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-right font-semibold text-gray-600 text-xs">{{ $cat->name }} Subtotal</td>
                            <td class="px-4 py-2 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
            @endforeach

            @if($assessmentItems->count() === 0)
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-clipboard-list text-3xl mb-3"></i>
                <p>No assessment items yet. Use the category tabs above to add fee items.</p>
            </div>
            @endif

            {{-- Grand Total --}}
            @if($assessmentItems->count())
            <div class="bg-gray-50 rounded-lg p-5">
                <div class="space-y-2 max-w-sm ml-auto">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Items Subtotal</span>
                        <span class="font-medium text-gray-900">&#8369;{{ number_format($totals['subtotal'], 2) }}</span>
                    </div>
                    @if($isMp)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Inspection Fees</span>
                        <span class="font-medium text-gray-900">&#8369;{{ number_format($totals['inspection'], 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Filing Fee</span>
                        <span class="font-medium text-gray-900">&#8369;{{ number_format($totals['filing'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Processing Fee</span>
                        <span class="font-medium text-gray-900">&#8369;{{ number_format($totals['processing'], 2) }}</span>
                    </div>
                    <hr class="border-gray-300">
                    <div class="flex justify-between text-base">
                        <span class="font-bold text-gray-900">Grand Total</span>
                        <span class="font-bold text-blue-700 text-lg">&#8369;{{ number_format($totals['total'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Print Button (finalized only) --}}
            @if($assessment && $assessment->status === 'finalized')
            <div class="flex justify-end gap-2">
                <a href="{{ $isDp ? route('assessments.print.dp', $application) : ($isSgp ? route('assessments.print.sgp', $application) : ($isFp ? route('assessments.print.fp', $application) : ($isMp ? route('assessments.print.mp', $application) : ($isOp ? route('assessments.print.op', $application) : route('assessments.print', $application))))) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition shadow-sm">
                    <i class="fas fa-print"></i> Print Summary of Computation
                </a>

                @can('revert-assessments')
                @if(in_array($application->status, ['engineering_assessed', 'billed']))
                <div x-data="{ open: false, pw: '' }">
                    <button @click="open = true; pw = ''"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-red-300 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-50 transition shadow-sm">
                        <i class="fas fa-undo"></i> Revert Finalization
                    </button>

                    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center"
                        @keydown.escape.window="open = false">
                        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
                        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6 z-10">
                            <div class="flex items-start gap-3 mb-4">
                                <span class="flex-shrink-0 w-10 h-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Confirm Revert</h3>
                                    <p class="text-sm text-gray-500 mt-0.5">
                                        This will unlock the assessment for editing and delete the auto-generated billing (if unpaid). Enter your password to proceed.
                                    </p>
                                </div>
                            </div>

                            <form action="{{ $revertRoute }}" method="POST" autocomplete="off">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                        Your Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password" name="password" x-model="pw" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                        placeholder="Enter your password">
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="open = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" :disabled="!pw"
                                        class="inline-flex items-center gap-1 px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-undo"></i> Revert
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
                @endcan
            </div>
            @endif

            {{-- Finalize Button + Password Modal --}}
            @if($assessment && $assessment->status !== 'finalized')
            <div x-data="{ open: false, pw: '' }" class="flex justify-end">

                {{-- Trigger button --}}
                <button @click="open = true; pw = ''"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition shadow-sm">
                    <i class="fas fa-check-circle"></i> Finalize Assessment
                </button>

                {{-- Backdrop + modal --}}
                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center"
                    @keydown.escape.window="open = false">
                    <div class="absolute inset-0 bg-black/40" @click="open = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6 z-10">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="flex-shrink-0 w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-lock"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Confirm Finalization</h3>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    This will lock the assessment and cannot be undone. Enter your password to proceed.
                                </p>
                            </div>
                        </div>

                        <form action="{{ $finalizeRoute }}" method="POST" autocomplete="off">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Your Password <span class="text-red-500">*</span>
                                </label>
                                <input type="password" name="password" x-model="pw" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="Enter your password">
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="open = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="!pw"
                                    class="inline-flex items-center gap-1 px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas fa-check-circle"></i> Finalize
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>
</div>
@endsection
