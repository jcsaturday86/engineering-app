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
    $addItemRoute = $isOp ? route('assessments.addItem.op', $application) : route('assessments.addItem', $application);
    $finalizeRoute = $isOp ? route('assessments.finalize.op', $application) : route('assessments.finalize', $application);
    $backRoute = $isOp ? route('assessments.occupancy') : route('assessments.index');
    $tabCategories = $tabCategories ?? $feeCategories;
    $activeTab = $activeTab ?? ($tabCategories->first()?->code ?? 'CONST');
    $itemsByCategory = $itemsByCategory ?? $assessmentItems->groupBy('fee_category_id');
@endphp
<div class="space-y-6" x-data="{ activeTab: '{{ $activeTab }}' }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-bold text-gray-900">Assess Application</h2>
        <a href="{{ $backRoute }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
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
            @if($cat->code === 'CONST')
            {{-- Construction Fee Form (BOPMS-style) --}}
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
            @elseif($cat->code === 'ELEC')
            {{-- Electrical Fee Form (BOPMS-style) --}}
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
            @elseif($cat->code === 'MECH')
            {{-- Mechanical Fee Form (BOPMS-style) --}}
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
            @else
            {{-- Generic Fee Item Form (other tabs) --}}
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
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Inspection</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Amount</th>
                            @elseif($cat->code === 'MECH')
                            <th class="text-left px-4 py-3 font-medium text-gray-500">Mechanical Fee</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Fee/Unit</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Excess/Add.</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500">Inspection</th>
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
                            <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->inspection_fee, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @elseif($cat->code === 'MECH')
                            <td class="px-4 py-3 text-gray-900 text-xs">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">@if($item->unit_fee > 0)&#8369;{{ number_format($item->unit_fee, 2) }}@else-@endif</td>
                            <td class="px-4 py-3 text-right text-gray-700">@if($item->excess_fee > 0)&#8369;{{ number_format($item->excess_fee, 2) }}@else-@endif</td>
                            <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->inspection_fee, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @else
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $item->fee_code }}</td>
                            <td class="px-4 py-3 text-gray-900">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">&#8369;{{ number_format($item->unit_fee, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">&#8369;{{ number_format($item->amount, 2) }}</td>
                            @endif
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('assessments.removeItem', $item) }}" method="POST" class="inline" onsubmit="return confirm('Remove this item?');" autocomplete="off">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Remove">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
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
                            <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('inspection_fee'), 2) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('amount'), 2) }}</td>
                            <td></td>
                            @elseif($cat->code === 'MECH')
                            <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">&#8369;{{ number_format($catItems->sum('inspection_fee'), 2) }}</td>
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
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Inspection Fees</span>
                        <span class="font-medium text-gray-900">&#8369;{{ number_format($totals['inspection'], 2) }}</span>
                    </div>
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

            {{-- Finalize Button --}}
            @if($assessment && $assessment->status !== 'finalized')
            <div class="flex justify-end">
                <form action="{{ $finalizeRoute }}" method="POST" onsubmit="return confirm('Are you sure you want to finalize this assessment? This action cannot be undone.');" autocomplete="off">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition shadow-sm">
                        <i class="fas fa-check-circle"></i> Finalize Assessment
                    </button>
                </form>
            </div>
            @endif
            @endif
        </div>
    </div>
</div>
@endsection
