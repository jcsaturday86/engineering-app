<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationOccupancyGroup;
use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\Barangay;
use App\Models\City;
use App\Models\FeeCategory;
use App\Models\FeeSchedule;
use App\Models\FeeType;
use App\Models\OccupancyDivision;
use App\Models\OccupancySubGroup;
use App\Models\PermitType;
use App\Models\Province;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Creates 1 BP application at 'submitted' status with pre-seeded Construction
 * and Electrical assessment items so QA can verify the Assessment tab UI
 * without manually entering data each time.
 *
 * Run: php artisan db:seed --class=AssessmentTestSeeder
 */
class AssessmentTestSeeder extends Seeder
{
    public function run(): void
    {
        $province  = Province::where('name', 'like', '%LA UNION%')->firstOrFail();
        $city      = City::where('province_id', $province->id)->where('name', 'like', '%SAN FERNANDO%')->firstOrFail();
        $barangay  = Barangay::where('city_id', $city->id)->first();
        $bpType    = PermitType::where('code', 'BP')->firstOrFail();

        $now   = now();
        $year  = $now->year;
        $month = $now->month;

        // ---------------------------------------------------------------
        // 1. BP APPLICATION
        // ---------------------------------------------------------------
        $counter = Application::whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;

        $app = Application::create([
            'permit_type_id'             => $bpType->id,
            'application_type_id'        => 1,   // New
            'complexity'                 => 'Simple',
            'applies_to'                 => 'SKIP_LC',
            'status'                     => 'submitted',
            'app_year'                   => $year,
            'app_month'                  => $month,
            'app_counter'                => $counter,
            'application_number'         => sprintf('BP-%d-%02d-%05d', $year, $month, $counter),
            'source'                     => 'walk_in',
            'applicant_first_name'       => 'Juan',
            'applicant_middle_name'      => 'Santos',
            'applicant_last_name'        => 'Dela Cruz',
            'applicant_suffix'           => '',
            'applicant_tin'              => '100-200-300-000',
            'applicant_contact_no'       => '09171001001',
            'applicant_email'            => 'juan.delacruz@test.epms',
            'applicant_province_id'      => $province->id,
            'applicant_city_id'          => $city->id,
            'applicant_barangay_id'      => $barangay->id,
            'enterprise_name'            => 'Juan DC Commercial Center',
            'form_of_ownership_id'       => 1,
            'project_title'              => '[TEST] Commercial Building – Assessment QA',
            'scope_of_work_id'           => 1,
            'scope_of_work_details'      => '',
            'lot_no'                     => '1',
            'block_no'                   => '1',
            'tct_no'                     => 'T-99001',
            'tax_dec_no'                 => 'TD-QA-001',
            'land_classification_id'     => 3,
            'no_of_storeys'              => 2,
            'no_of_units'                => 1,
            'total_floor_area'           => 280.00,
            'lot_area'                   => 400.00,
            'building_cost'              => 3000000,
            'electrical_cost'            => 200000,
            'mechanical_cost'            => 100000,
            'electronics_cost'           => 0,
            'plumbing_cost'              => 80000,
            'other_equipment_cost'       => 0,
            'total_estimated_cost'       => 3380000,
            'proposed_construction_date' => '2026-08-01',
            'expected_completion_date'   => '2027-08-01',
            'engineer_name'              => 'Engr. Test Engineer',
            'engineer_prc_no'            => 'CE-0099001',
            'engineer_prc_validity'      => '2028-12-31',
            'engineer_ptr_no'            => 'PTR-2026-QA1',
            'engineer_ptr_date_issued'   => '2026-01-15',
            'engineer_ptr_issued_at'     => 'San Fernando, La Union',
            'engineer_tin'               => '900-800-700-000',
            'engineer_address'           => 'Poro, SFC La Union',
            'engineer_date_signed'       => '2026-06-30',
            'owner_name'                 => 'Juan Santos Dela Cruz',
            'owner_address'              => 'Parian, SFC La Union',
            'building_street'            => 'National Highway',
            'building_barangay_id'       => $barangay->id,
            'include_electrical'         => true,
            'total_connected_load'       => 23.78,
            'total_transformer_capacity' => 25.00,
            'total_generator_capacity'   => 0,
            'pee_name'                   => 'Engr. Test PEE',
            'pee_prc_no'                 => 'REE-0099001',
            'pee_prc_validity'           => '2028-06-30',
            'pee_date_signed'            => '2026-06-30',
            'pee_ptr_no'                 => 'PTR-2026-QA2',
            'pee_ptr_date_issued'        => '2026-01-20',
            'pee_ptr_issued_at'          => 'San Fernando, La Union',
            'pee_address'                => 'Catbangen, SFC La Union',
            'pee_tin'                    => '100-200-300-001',
            'sew_profession'             => 'REE',
            'sew_name'                   => 'Engr. Test PEE',
            'sew_prc_no'                 => 'REE-0099001',
            'sew_prc_validity'           => '2028-06-30',
            'sew_date_signed'            => '2026-06-30',
            'sew_ptr_no'                 => 'PTR-2026-QA2',
            'sew_ptr_date_issued'        => '2026-01-20',
            'sew_ptr_issued_at'          => 'San Fernando, La Union',
            'sew_address'                => 'Catbangen, SFC La Union',
            'sew_tin'                    => '100-200-300-001',
            'entered_by'                 => 1,
            'submitted_at'               => $now,
        ]);

        // Occupancy groups: A (residential) + B (mercantile)
        foreach ([1, 3] as $subGroupId) {
            $subGroup = OccupancySubGroup::find($subGroupId);
            if ($subGroup) {
                ApplicationOccupancyGroup::create([
                    'application_id'          => $app->id,
                    'applicationable_type'    => 'bp',
                    'applicationable_id'      => $app->id,
                    'occupancy_group_id'      => $subGroup->occupancy_group_id,
                    'occupancy_sub_group_id'  => $subGroupId,
                ]);
            }
        }

        // ---------------------------------------------------------------
        // 2. ASSESSMENT (draft — ready for testing)
        // ---------------------------------------------------------------
        $assessment = Assessment::create([
            'applicationable_type' => 'bp',
            'applicationable_id'   => $app->id,
            'assessment_type'      => 'building',
            'status'               => 'draft',
            'assessed_by'          => 1,
        ]);

        $constCategory = FeeCategory::where('code', 'CONST')->firstOrFail();
        $elecCategory  = FeeCategory::where('code', 'ELEC')->firstOrFail();
        $inspPct       = (float) (Setting::where('key', 'assessment.electrical_inspection_percentage')->value('value') ?? 10);

        // ---------------------------------------------------------------
        // 3. CONSTRUCTION ITEMS
        // ---------------------------------------------------------------

        // Item 1 — Division A1, 80 sq.m.
        // Range 51-100: fee_per_unit = 4.80 → amount = 80 × 4.80 = ₱384.00
        $this->addConstructionItem($assessment, $constCategory, 'A1', 'Building Residential', 80.00);

        // Item 2 — Division A1, 120 sq.m. (Mezzanine)
        // Range 101-150: fee_per_unit = 6.00 → amount = 120 × 6.00 = ₱720.00
        $this->addConstructionItem($assessment, $constCategory, 'A1', 'Mezzanine', 120.00);

        // ---------------------------------------------------------------
        // 4. ELECTRICAL ITEMS
        // ---------------------------------------------------------------

        // Item 1 — TCL: 23.78 kVA
        // Range 6-50.99: fixed_fee = 200, fee_per_unit = 20
        // base = 200 + (23.78 × 20) = 675.60 | inspection (10%) = 67.56 | total = 743.16
        $this->addElectricalKvaItem($assessment, $elecCategory, 'ELEC_TCL', 23.78, $inspPct);

        // Item 2 — Transformer: 25 kVA
        // Range 6-50.99: fixed_fee = 40, fee_per_unit = 4
        // base = 40 + (25 × 4) = 140.00 | inspection (10%) = 14.00 | total = 154.00
        $this->addElectricalKvaItem($assessment, $elecCategory, 'ELEC_TRANS', 25.00, $inspPct);

        // Item 3 — Wiring Permit: Commercial/Industrial
        // fixed_fee = 36 | inspection = 3.60 | total = 39.60
        $this->addElectricalFixedItem($assessment, $elecCategory, 'ELEC_MISC_WIRING', 'Commercial/Industrial', $inspPct);

        $this->command->info(sprintf(
            'Assessment test record created: %s (ID: %d) — 2 CONST items, 3 ELEC items. Status: submitted.',
            $app->application_number,
            $app->id
        ));
    }

    private function addConstructionItem(Assessment $assessment, $category, string $divCode, string $partName, float $area): void
    {
        $feeType = FeeType::where('code', 'CONST_' . $divCode)->first();
        if (!$feeType) {
            $this->command->warn("Fee type CONST_{$divCode} not found — skipping.");
            return;
        }

        $schedule = FeeSchedule::where('fee_type_id', $feeType->id)
            ->where('range_from', '<=', $area)
            ->where('range_to', '>=', $area)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            $this->command->warn("No fee schedule found for CONST_{$divCode} at {$area} sq.m. — skipping.");
            return;
        }

        $division  = OccupancyDivision::where('code', $divCode)->first();
        $unitFee   = (float) $schedule->fee_per_unit;
        $amount    = round($area * $unitFee, 2);

        AssessmentItem::create([
            'assessment_id'      => $assessment->id,
            'fee_category_id'    => $category->id,
            'fee_type_id'        => $feeType->id,
            'fee_code'           => $feeType->code,
            'description'        => $partName . ' - ' . ($division->name ?? $divCode),
            'quantity'           => $area,
            'unit_fee'           => $unitFee,
            'excess_fee'         => 0,
            'inspection_fee'     => 0,
            'amount'             => $amount,
            'computation_details' => [
                'building_part'   => $partName,
                'division_code'   => $divCode,
                'fee_schedule_id' => $schedule->id,
            ],
            'is_active'          => true,
        ]);
    }

    private function addElectricalKvaItem(Assessment $assessment, $category, string $typeCode, float $kva, float $inspPct): void
    {
        $feeType = FeeType::where('code', $typeCode)->first();
        if (!$feeType) {
            $this->command->warn("Fee type {$typeCode} not found — skipping.");
            return;
        }

        $schedule = FeeSchedule::where('fee_type_id', $feeType->id)
            ->where('range_from', '<=', $kva)
            ->where('range_to', '>=', $kva)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            $this->command->warn("No fee schedule found for {$typeCode} at {$kva} kVA — skipping.");
            return;
        }

        $fixedFee     = (float) $schedule->fixed_fee;
        $feePerUnit   = (float) $schedule->fee_per_unit;
        $baseFee      = round($fixedFee + ($kva * $feePerUnit), 2);
        $inspectionFee = round($baseFee * $inspPct / 100, 2);
        $amount       = $baseFee + $inspectionFee;

        AssessmentItem::create([
            'assessment_id'      => $assessment->id,
            'fee_category_id'    => $category->id,
            'fee_type_id'        => $feeType->id,
            'fee_code'           => $feeType->code,
            'description'        => $feeType->name . ' - ' . number_format($kva, 2) . ' kVA',
            'quantity'           => $kva,
            'unit_fee'           => $feePerUnit,
            'excess_fee'         => 0,
            'inspection_fee'     => $inspectionFee,
            'amount'             => $amount,
            'computation_details' => [
                'fee_type_code'   => $typeCode,
                'fee_schedule_id' => $schedule->id,
                'input_kva'       => $kva,
                'fixed_fee'       => $fixedFee,
                'fee_per_unit'    => $feePerUnit,
                'range'           => $schedule->range_from . ' - ' . $schedule->range_to,
            ],
            'is_active'          => true,
        ]);
    }

    private function addElectricalFixedItem(Assessment $assessment, $category, string $typeCode, string $formulaKey, float $inspPct): void
    {
        $feeType = FeeType::where('code', $typeCode)->first();
        if (!$feeType) {
            $this->command->warn("Fee type {$typeCode} not found — skipping.");
            return;
        }

        $schedule = FeeSchedule::where('fee_type_id', $feeType->id)
            ->where('formula', $formulaKey)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            $this->command->warn("No fee schedule found for {$typeCode} / '{$formulaKey}' — skipping.");
            return;
        }

        $baseFee       = (float) $schedule->fixed_fee;
        $inspectionFee = round($baseFee * $inspPct / 100, 2);
        $amount        = $baseFee + $inspectionFee;

        AssessmentItem::create([
            'assessment_id'      => $assessment->id,
            'fee_category_id'    => $category->id,
            'fee_type_id'        => $feeType->id,
            'fee_code'           => $feeType->code,
            'description'        => $feeType->name . ' - ' . $formulaKey,
            'quantity'           => 1,
            'unit_fee'           => 0,
            'excess_fee'         => 0,
            'inspection_fee'     => $inspectionFee,
            'amount'             => $amount,
            'computation_details' => [
                'fee_type_code'   => $typeCode,
                'fee_schedule_id' => $schedule->id,
                'occupancy_type'  => $formulaKey,
                'fixed_fee'       => $baseFee,
            ],
            'is_active'          => true,
        ]);
    }
}
