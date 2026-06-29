<?php

namespace Database\Seeders;

use App\Models\FeeCategory;
use App\Models\FeeSchedule;
use App\Models\FeeType;
use Illuminate\Database\Seeder;

class FeeScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Migrates all fee data from the original BOPMS (db_ebps) into the new
     * FeeType / FeeSchedule schema. Every row is hardcoded so the seeder is
     * fully self-contained and idempotent (updateOrCreate on FeeType.code).
     */
    public function run(): void
    {
        $this->seedConstructionFees();
        $this->seedAdditionalConstructionFees();
        $this->seedElectricalFees();
        $this->seedMechanicalFees();
        $this->seedPlumbingFees();
        $this->seedElectronicsFees();
        $this->seedAccessoryBuildingFees();
        $this->seedAccessoryFees();
        $this->seedSurcharges();
        $this->seedOccupancyFees();
        $this->seedZoningFees();
        $this->seedAnnualInspectionFees();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Create or update a FeeType and return its id.
     */
    private function upsertFeeType(
        string $categoryCode,
        string $code,
        string $name,
        string $computationMethod = 'range_based',
        bool $hasExcess = false,
        bool $hasMinimum = false,
        int $sortOrder = 0,
        ?string $description = null,
    ): int {
        $category = FeeCategory::where('code', $categoryCode)->first();

        if (! $category) {
            $this->command?->warn("FeeCategory [{$categoryCode}] not found. Skipping FeeType [{$code}].");

            return 0;
        }

        $feeType = FeeType::updateOrCreate(
            ['code' => $code],
            [
                'fee_category_id' => $category->id,
                'name' => $name,
                'description' => $description,
                'computation_method' => $computationMethod,
                'has_excess' => $hasExcess,
                'has_minimum' => $hasMinimum,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]
        );

        return $feeType->id;
    }

    /**
     * Bulk-insert fee schedules for a given FeeType, deleting old rows first.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncSchedules(int $feeTypeId, array $rows): void
    {
        if ($feeTypeId === 0) {
            return;
        }

        // Remove existing schedules for idempotency
        FeeSchedule::where('fee_type_id', $feeTypeId)->delete();

        foreach ($rows as $row) {
            FeeSchedule::create(array_merge(['fee_type_id' => $feeTypeId, 'is_active' => true], $row));
        }
    }

    // =========================================================================
    // 1. CONSTRUCTION FEES
    // =========================================================================

    private function seedConstructionFees(): void
    {
        // Old system: construction_fees (division_id, range_from, range_to, fee)
        // Division IDs 1-24 map to codes: A1, A2, B1, C1, C2, D1, D2, D3,
        // E1, E2, E3, F1, G1, G2, G3, G4, H1, H2, H3, H4, I1, J1, J2, J3

        $divisionMap = [
            1 => 'A1', 2 => 'A2', 3 => 'B1', 4 => 'C1', 5 => 'C2',
            6 => 'D1', 7 => 'D2', 8 => 'D3', 9 => 'E1', 10 => 'E2',
            11 => 'E3', 12 => 'F1', 13 => 'G1', 14 => 'G2', 15 => 'G3',
            16 => 'G4', 17 => 'H1', 18 => 'H2', 19 => 'H3', 20 => 'H4',
            21 => 'I1', 22 => 'J1', 23 => 'J2', 24 => 'J3',
        ];

        // All construction fee data grouped by division_id
        $data = [
            // Division A1 (Residential - Single Family)
            1 => [
                [1, 20, 2], [21, 50, 3.4], [51, 100, 4.8], [101, 150, 6], [151, 1000000, 7.2],
            ],
            // Division A2
            2 => [
                [1, 20, 3], [21, 50, 5.2], [51, 150, 8], [151, 1000000, 8.4],
            ],
            // Division B1
            3 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division C1
            4 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division C2
            5 => [
                [1, 500, 12], [501, 600, 11], [601, 700, 10.2], [701, 800, 9.6],
                [801, 900, 9], [901, 1000, 8.4], [1001, 1500, 7.2], [1501, 2000, 6.6],
                [2001, 3000, 6], [3000, 1000000, 5],
            ],
            // Division D1
            6 => [
                [1, 500, 12], [501, 600, 11], [601, 700, 10.2], [701, 800, 9.6],
                [801, 900, 9], [901, 1000, 8.4], [1001, 1500, 7.2], [1501, 2000, 6.6],
                [2001, 3000, 6], [3000, 1000000, 5],
            ],
            // Division D2
            7 => [
                [1, 500, 12], [501, 600, 11], [601, 700, 10.2], [701, 800, 9.6],
                [801, 900, 9], [901, 1000, 8.4], [1001, 1500, 7.2], [1501, 2000, 6.6],
                [2001, 3000, 6], [3001, 1000000, 5],
            ],
            // Division D3
            8 => [
                [1, 500, 12], [501, 600, 11], [601, 700, 9.6], [701, 800, 9],
                [801, 900, 8.4], [1001, 1500, 7.2], [1501, 2000, 6.6],
                [2001, 3000, 6], [3001, 1000000, 5],
            ],
            // Division E1
            9 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1000, 1500, 16], [1500, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division E2
            10 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division E3
            11 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division F1
            12 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division G1
            13 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division G2
            14 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division G3
            15 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division G4
            16 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division H1
            17 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division H2
            18 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division H3
            19 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division H4
            20 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division I1
            21 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
            // Division J1
            22 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14],
            ],
            // Division J2
            23 => [
                [1, 500, 11.5], [501, 600, 11], [601, 700, 10.25], [701, 800, 9.75],
                [801, 900, 9], [901, 1000, 8.5], [1001, 1500, 8], [1501, 2000, 7.5],
                [2001, 3000, 7], [3001, 1000000, 6],
            ],
            // Division J3
            24 => [
                [1, 500, 23], [501, 600, 22], [601, 700, 20.5], [701, 800, 19.5],
                [801, 900, 18], [901, 1000, 17], [1001, 1500, 16], [1501, 2000, 15],
                [2001, 3000, 14], [3001, 1000000, 12],
            ],
        ];

        foreach ($data as $divId => $ranges) {
            $divCode = $divisionMap[$divId] ?? "D{$divId}";
            $typeCode = "CONST_{$divCode}";
            $feeTypeId = $this->upsertFeeType(
                'CONST',
                $typeCode,
                "Construction Fee - Division {$divCode}",
                'range_based',
                false,
                false,
                $divId,
            );

            $rows = [];
            foreach ($ranges as $r) {
                $rows[] = [
                    'range_from' => $r[0],
                    'range_to' => $r[1],
                    'fee_per_unit' => $r[2],
                ];
            }
            $this->syncSchedules($feeTypeId, $rows);
        }
    }

    // =========================================================================
    // 2. ADDITIONAL CONSTRUCTION FEES
    // =========================================================================

    private function seedAdditionalConstructionFees(): void
    {
        // Division 1 = Residential (A1/A2), Division 2 = Commercial/Industrial
        $data = [
            1 => [
                [1, 20, 2.4], [21, 50, 3.4], [51, 100, 4.8], [101, 150, 6], [151, 1000000, 7.2],
            ],
            2 => [
                [1, 20, 3.4], [21, 50, 5.2], [51, 100, 8], [101, 1000000, 8.4],
            ],
        ];

        $names = [1 => 'Additional Construction Fee - Residential', 2 => 'Additional Construction Fee - Commercial/Industrial'];

        $order = 25;
        foreach ($data as $divId => $ranges) {
            $feeTypeId = $this->upsertFeeType(
                'CONST',
                "CONST_ADD_D{$divId}",
                $names[$divId],
                'range_based',
                false,
                false,
                $order++,
            );

            $rows = [];
            foreach ($ranges as $r) {
                $rows[] = ['range_from' => $r[0], 'range_to' => $r[1], 'fee_per_unit' => $r[2]];
            }
            $this->syncSchedules($feeTypeId, $rows);
        }
    }

    // =========================================================================
    // 3. ELECTRICAL FEES
    // =========================================================================

    private function seedElectricalFees(): void
    {
        $order = 0;

        // --- Total Connected Load ---
        $feeTypeId = $this->upsertFeeType(
            'ELEC', 'ELEC_TCL', 'Total Connected Load (kVA)',
            'range_based', false, false, ++$order,
            'Fee based on total connected load in kVA'
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 0, 'range_to' => 5.99, 'fixed_fee' => 200, 'fee_per_unit' => 0],
            ['range_from' => 6, 'range_to' => 50.99, 'fixed_fee' => 200, 'fee_per_unit' => 20],
            ['range_from' => 51, 'range_to' => 300.99, 'fixed_fee' => 1100, 'fee_per_unit' => 10],
            ['range_from' => 301, 'range_to' => 1500.99, 'fixed_fee' => 3600, 'fee_per_unit' => 5],
            ['range_from' => 1501, 'range_to' => 6000.99, 'fixed_fee' => 9600, 'fee_per_unit' => 2.5],
            ['range_from' => 6001, 'range_to' => 100000000, 'fixed_fee' => 20850, 'fee_per_unit' => 1.25],
        ]);

        // --- Total Transformer Capacity ---
        $feeTypeId = $this->upsertFeeType(
            'ELEC', 'ELEC_TRANS', 'Total Transformer Capacity (kVA)',
            'range_based', false, false, ++$order,
            'Fee based on total transformer capacity in kVA'
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 0, 'range_to' => 5.99, 'fixed_fee' => 40, 'fee_per_unit' => 0],
            ['range_from' => 6, 'range_to' => 50.99, 'fixed_fee' => 40, 'fee_per_unit' => 4],
            ['range_from' => 51, 'range_to' => 300.99, 'fixed_fee' => 220, 'fee_per_unit' => 2],
            ['range_from' => 301, 'range_to' => 1500.99, 'fixed_fee' => 720, 'fee_per_unit' => 1],
            ['range_from' => 1501, 'range_to' => 6000.99, 'fixed_fee' => 1920, 'fee_per_unit' => 0.5],
            ['range_from' => 6001, 'range_to' => 100000000, 'fixed_fee' => 4170, 'fee_per_unit' => 0.25],
        ]);

        // --- Total UPS/Generator Capacity ---
        $feeTypeId = $this->upsertFeeType(
            'ELEC', 'ELEC_UPS', 'Total UPS/Generator Capacity (kVA)',
            'range_based', false, false, ++$order,
            'Fee based on total UPS or generator capacity in kVA'
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 0, 'range_to' => 5.99, 'fixed_fee' => 40, 'fee_per_unit' => 0],
            ['range_from' => 6, 'range_to' => 50.99, 'fixed_fee' => 40, 'fee_per_unit' => 4],
            ['range_from' => 51, 'range_to' => 300.99, 'fixed_fee' => 220, 'fee_per_unit' => 2],
            ['range_from' => 301, 'range_to' => 1500.99, 'fixed_fee' => 720, 'fee_per_unit' => 1],
            ['range_from' => 1501, 'range_to' => 6000.99, 'fixed_fee' => 1920, 'fee_per_unit' => 0.5],
            ['range_from' => 6001, 'range_to' => 100000000, 'fixed_fee' => 4170, 'fee_per_unit' => 0.25],
        ]);

        // --- Pole Attachment / Location ---
        $feeTypeId = $this->upsertFeeType(
            'ELEC', 'ELEC_POLE', 'Pole Attachment / Location Fees',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['formula' => 'Power Supply Pole Location', 'fixed_fee' => 30],
            ['formula' => 'Guying Attachment', 'fixed_fee' => 30],
        ]);

        // --- Miscellaneous Electrical Fees (per occupancy) ---
        $feeTypeId = $this->upsertFeeType(
            'ELEC', 'ELEC_MISC_METER', 'Miscellaneous - Electric Meter Fee',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['formula' => 'Residential', 'fixed_fee' => 15],
            ['formula' => 'Commercial/Industrial', 'fixed_fee' => 60],
            ['formula' => 'Institutional', 'fixed_fee' => 30],
        ]);

        $feeTypeId = $this->upsertFeeType(
            'ELEC', 'ELEC_MISC_WIRING', 'Miscellaneous - Wiring Permit Fee',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['formula' => 'Residential', 'fixed_fee' => 15],
            ['formula' => 'Commercial/Industrial', 'fixed_fee' => 36],
            ['formula' => 'Institutional', 'fixed_fee' => 12],
        ]);

        // Deactivate old merged ELEC_TUG if it exists
        \App\Models\FeeType::where('code', 'ELEC_TUG')->update(['is_active' => false]);
    }

    // =========================================================================
    // 4. MECHANICAL FEES
    // =========================================================================

    private function seedMechanicalFees(): void
    {
        $order = 0;

        // --- Refrigeration / Aircon / Ventilation (unit-based items) ---
        $unitItems = [
            ['MECH_REFRIG', 'Refrigeration (Cold Storage), per ton', 40],
            ['MECH_ICE', 'Ice Plants, per ton', 60],
            ['MECH_WINDOW_AC', 'Window Type Air Conditioners, per unit', 60],
            ['MECH_VENT', 'Mechanical Ventilation, per kW', 40],
        ];
        foreach ($unitItems as $item) {
            $feeTypeId = $this->upsertFeeType(
                'MECH', $item[0], $item[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $item[2]]]);
        }

        // --- Packaged / Centralized AC (range-based) ---
        $feeTypeId = $this->upsertFeeType(
            'MECH', 'MECH_CENTRAL_AC', 'Packaged/Centralized Air Conditioning Systems',
            'range_based', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 0, 'range_to' => 100, 'fee_per_unit' => 90],
            ['range_from' => 101, 'range_to' => 1000, 'fee_per_unit' => 40],
        ]);

        // --- Escalators / Moving Walks ---
        $escItems = [
            ['MECH_ESC_KW', 'Escalator/Moving Walk, per kW', 10],
            ['MECH_FUNIC_KW', 'Funicular, per kW', 200],
            ['MECH_FUNIC_LM', 'Funicular, per lineal meter travel', 20],
            ['MECH_CABLE_KW', 'Cable Car, per kW', 40],
            ['MECH_CABLE_LM', 'Cable Car, per lineal meter travel', 5],
        ];
        foreach ($escItems as $item) {
            $feeTypeId = $this->upsertFeeType(
                'MECH', $item[0], $item[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $item[2]]]);
        }

        // --- Escalator / Moving Walk Range (for travel-based) ---
        $feeTypeId = $this->upsertFeeType(
            'MECH', 'MECH_ESC_RANGE', 'Escalator/Moving Walk Range (lineal meters)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 20, 'fee_per_unit' => 20],
            ['range_from' => 21, 'range_to' => 1000000, 'fee_per_unit' => 20, 'excess_threshold' => 20, 'excess_fee' => 10],
        ]);

        // --- Elevators ---
        $elevItems = [
            ['MECH_ELEV_DUMB', 'Motor Driven Dumbwaiters', 600],
            ['MECH_ELEV_CONST', 'Construction Elevators for Material', 2000],
            ['MECH_ELEV_PASS', 'Passenger Elevators', 5000],
            ['MECH_ELEV_FRT', 'Freight Elevators', 5000],
            ['MECH_ELEV_CAR', 'Car Elevators', 5000.5],
        ];
        foreach ($elevItems as $item) {
            $feeTypeId = $this->upsertFeeType(
                'MECH', $item[0], $item[1], 'fixed', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fixed_fee' => $item[2]]]);
        }

        // --- Boilers ---
        $feeTypeId = $this->upsertFeeType(
            'MECH', 'MECH_BOILER', 'Boilers (per rated capacity in kW)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 0, 'range_to' => 7.5, 'fixed_fee' => 500],
            ['range_from' => 7.51, 'range_to' => 22, 'fixed_fee' => 700],
            ['range_from' => 23, 'range_to' => 37, 'fixed_fee' => 900],
            ['range_from' => 38, 'range_to' => 52, 'fixed_fee' => 1200],
            ['range_from' => 53, 'range_to' => 67, 'fixed_fee' => 1400],
            ['range_from' => 68, 'range_to' => 74, 'fixed_fee' => 1600],
            ['range_from' => 75, 'range_to' => 1000000, 'fixed_fee' => 1600, 'fee_per_unit' => 5],
        ]);

        // --- Other Mechanical Fees (unit-based) ---
        $otherItems = [
            ['MECH_WATER_HEATER', 'Pressurized Water Heaters, per unit', 200],
            ['MECH_WATER_PUMP', 'Water/Sump/Sewage Pumps (Commercial/Industrial), per kW', 60],
            ['MECH_SPRINKLER', 'Automatic Fire Sprinkler System, per sprinkler head', 4],
            ['MECH_COMPRESSED', 'Compressed Air/Vacuum/Gases, per outlet', 20],
            ['MECH_GAS_METER', 'Gas Meter, per unit', 100],
            ['MECH_POWER_PIPE', 'Power Piping (gas/steam/etc.), per lineal meter', 4],
            ['MECH_PRESSURE_V', 'Pressure Vessels, per cu. meter', 60],
            ['MECH_OTHER_EQUIP', 'Other Machinery/Equipment (Commercial/Industrial), per kW', 60],
            ['MECH_PNEUMATIC', 'Pneumatic Tubes/Conveyors/Monorails, per lineal meter', 10],
            ['MECH_WEIGH_SCALE', 'Weighing Scale Structure, per ton', 50],
        ];
        foreach ($otherItems as $item) {
            $feeTypeId = $this->upsertFeeType(
                'MECH', $item[0], $item[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $item[2]]]);
        }

        // --- Diesel / Gasoline Engines ---
        $feeTypeId = $this->upsertFeeType(
            'MECH', 'MECH_DIESEL', 'Diesel/Gasoline Engines (per kW)',
            'range_based', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 0, 'range_to' => 50, 'fee_per_unit' => 25],
            ['range_from' => 51, 'range_to' => 100, 'fee_per_unit' => 20],
            ['range_from' => 101, 'range_to' => 100000, 'fee_per_unit' => 3],
        ]);

        // --- Other Internal Combustion ---
        $feeTypeId = $this->upsertFeeType(
            'MECH', 'MECH_INT_COMB', 'Other Internal Combustion Engines (per kW)',
            'range_based', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 50, 'fee_per_unit' => 10],
            ['range_from' => 51, 'range_to' => 100, 'fee_per_unit' => 12],
            ['range_from' => 101, 'range_to' => 1000000, 'fee_per_unit' => 3],
        ]);
    }

    // =========================================================================
    // 5. PLUMBING FEES
    // =========================================================================

    private function seedPlumbingFees(): void
    {
        $order = 0;

        // --- Installation Fee (1 unit = 1 WC + 2 floor drains + 1 lavatory + 1 sink + 3 faucets + 1 shower) ---
        $feeTypeId = $this->upsertFeeType(
            'PLUMB', 'PLUMB_INSTALL', 'Installation Fee (per unit)',
            'per_unit', false, false, ++$order,
            'One unit: 1 WC, 2 floor drains, 1 lavatory, 1 sink w/ trap, 3 faucets, 1 shower head'
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 24]]);

        // --- Every Fixture Fees ---
        $fixtures = [
            ['PLUMB_FIX_WC', 'Water Closet', 7],
            ['PLUMB_FIX_FD', 'Floor Drain', 3],
            ['PLUMB_FIX_SINK', 'Sink', 3],
            ['PLUMB_FIX_LAV', 'Lavatory', 7],
            ['PLUMB_FIX_FAUCET', 'Faucet', 2],
            ['PLUMB_FIX_SHOWER', 'Shower Head', 2],
        ];
        foreach ($fixtures as $f) {
            $feeTypeId = $this->upsertFeeType(
                'PLUMB', $f[0], "Every Fixture - {$f[1]}", 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $f[2]]]);
        }

        // --- Special Plumbing Fixtures ---
        $specials = [
            ['PLUMB_SP_SLOP', 'Slop Sink', 7],
            ['PLUMB_SP_URINAL', 'Urinal', 4],
            ['PLUMB_SP_BATH', 'Bath Tub', 7],
            ['PLUMB_SP_GREASE', 'Grease Trap', 7],
            ['PLUMB_SP_GARAGE', 'Garage Trap', 7],
            ['PLUMB_SP_BIDET', 'Bidet', 4],
            ['PLUMB_SP_DENTAL', 'Dental Cuspidor', 4],
            ['PLUMB_SP_GWH', 'Gas-fired Water Heater', 4],
            ['PLUMB_SP_DRINK', 'Drinking Fountain', 2],
            ['PLUMB_SP_BAR', 'Bar/Soda Fountain Sink', 4],
            ['PLUMB_SP_LAUNDRY', 'Laundry Sink', 4],
            ['PLUMB_SP_LAB', 'Laboratory Sink', 4],
            ['PLUMB_SP_STERIL', 'Fixed-type Sterilizer', 2],
        ];
        foreach ($specials as $s) {
            $feeTypeId = $this->upsertFeeType(
                'PLUMB', $s[0], "Special Fixture - {$s[1]}", 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $s[2]]]);
        }

        // --- Water Meter Range Fees ---
        $feeTypeId = $this->upsertFeeType(
            'PLUMB', 'PLUMB_WATER_METER', 'Water Meter Fees (by diameter in mm)',
            'range_based', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 11, 'fixed_fee' => 2],
            ['range_from' => 12, 'range_to' => 25, 'fixed_fee' => 8],
            ['range_from' => 26, 'range_to' => 100000, 'fixed_fee' => 10],
        ]);

        // --- Septic Tank Range Fees ---
        $feeTypeId = $this->upsertFeeType(
            'PLUMB', 'PLUMB_SEPTIC', 'Septic Tank Fee (by cu. meter volume)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 5, 'fixed_fee' => 24, 'excess_threshold' => 5, 'excess_fee' => 7],
        ]);
    }

    // =========================================================================
    // 6. ELECTRONICS FEES
    // =========================================================================

    private function seedElectronicsFees(): void
    {
        $items = [
            ['ELECT_SWITCH', 'Central Office Switching Equipment / PABX / PBX / Communications Systems', 2.4, 'per_unit'],
            ['ELECT_BROADCAST', 'Broadcast Station / CATV / Cell Sites / Communications Centers', 1000, 'fixed'],
            ['ELECT_ATM', 'ATM / Ticketing / Vending / Medical Equipment / Electronic Devices', 10, 'per_unit'],
            ['ELECT_OUTLET', 'Electronics/Communications Outlets (voice, data, video)', 2.4, 'per_unit'],
            ['ELECT_SECURITY', 'Security/Alarm/Fire Alarm/CATV/CCTV Systems Outlets', 2.4, 'per_unit'],
            ['ELECT_STUDIO', 'Studios/Auditoriums/Theaters for Broadcasting', 1000, 'fixed'],
            ['ELECT_TOWER', 'Antenna Towers/Masts for Transmission/Reception', 1000, 'fixed'],
            ['ELECT_SIGNAGE', 'Electronic Signage and Display Systems', 50, 'per_unit'],
            ['ELECT_POLE', 'Pole Location Fee (per pole)', 20, 'per_unit'],
            ['ELECT_ATTACH', 'Pole Attachment Fee (per attachment)', 20, 'per_unit'],
            ['ELECT_OTHER', 'Other Electronics Devices/Equipment', 50, 'per_unit'],
        ];

        $order = 0;
        foreach ($items as $item) {
            $feeTypeId = $this->upsertFeeType(
                'ELECT', $item[0], $item[1], $item[3], false, false, ++$order,
            );

            $schedule = $item[3] === 'fixed'
                ? ['fixed_fee' => $item[2]]
                : ['fee_per_unit' => $item[2]];

            $this->syncSchedules($feeTypeId, [$schedule]);
        }
    }

    // =========================================================================
    // 7. ACCESSORY BUILDING FEES (acc_*)
    // =========================================================================

    private function seedAccessoryBuildingFees(): void
    {
        $order = 0;

        // a. Open parts of buildings (percentage-based)
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_OPEN_PARTS', 'Open Parts of Buildings (balconies, terraces, lanais)',
            'percentage', false, false, ++$order,
            'Charged 50% of the rate of the principal building'
        );
        $this->syncSchedules($feeTypeId, [['percentage' => 0.5]]);

        // b. Buildings with height > 8m
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_HEIGHT', 'Buildings with Height > 8.00m',
            'per_unit', false, false, ++$order,
            'Additional P0.25 per cu. meter above 8.00 meters'
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 0.25, 'formula' => 'above_8m']]);

        // c. Bank / Records Vaults
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_VAULT', 'Bank and Records Vaults',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 1000000, 'fixed_fee' => 20, 'excess_threshold' => 20, 'excess_fee' => 8],
        ]);

        // d. Swimming Pools
        $poolItems = [
            ['ACC_POOL_RES', 'Swimming Pool - GROUP A Residential', 3],
            ['ACC_POOL_COM', 'Swimming Pool - Commercial/Industrial (B, E, F, G)', 36],
            ['ACC_POOL_SOC', 'Swimming Pool - Social/Recreational/Institutional (C, D, H, I)', 24],
            ['ACC_POOL_INDIG', 'Swimming Pool - Indigenous Materials', 12],
            ['ACC_POOL_SHR_RES', 'Pool Shower/Locker - Residential GROUP A', 6],
            ['ACC_POOL_SHR_COM', 'Pool Shower/Locker - Commercial (B, E, F, G)', 18],
            ['ACC_POOL_SHR_SOC', 'Pool Shower/Locker - Social (C, D, H)', 12],
        ];
        foreach ($poolItems as $p) {
            $feeTypeId = $this->upsertFeeType(
                'ACC_BLDG', $p[0], $p[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $p[2]]]);
        }

        // e. Firewalls
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_FIREWALL', 'Firewalls (per sq. meter)',
            'per_unit', false, true, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 3, 'minimum_fee' => 48]]);

        // f. Construction/Erection of Towers
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_TOWER_RES', 'Tower - Single Detached Dwelling Units',
            'fixed', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['fixed_fee' => 500, 'formula' => 'self_supporting'],
            ['fixed_fee' => 150, 'formula' => 'trilon_guyed'],
        ]);

        // Tower - Commercial/Industrial (range-based, ss and tg fees)
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_TOWER_COM_SS', 'Tower - Commercial/Industrial (Self-Supporting)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 2400, 'excess_threshold' => 10, 'excess_fee' => 120],
        ]);

        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_TOWER_COM_TG', 'Tower - Commercial/Industrial (Trilon/Guyed)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 240, 'excess_threshold' => 10, 'excess_fee' => 12],
        ]);

        // Tower - Educational/Recreational/Institutional
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_TOWER_EDU_SS', 'Tower - Educational/Recreational/Institutional (Self-Supporting)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 1800, 'excess_threshold' => 10, 'excess_fee' => 120],
        ]);

        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_TOWER_EDU_TG', 'Tower - Educational/Recreational/Institutional (Trilon/Guyed)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 120, 'excess_threshold' => 10, 'excess_fee' => 12],
        ]);

        // g. Storage Silos
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_SILO', 'Storage Silos (per meter)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 2400, 'excess_threshold' => 10, 'excess_fee' => 150],
        ]);

        // h. Smokestacks and Chimneys
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_SMOKESTACK', 'Construction of Smokestacks',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 240, 'excess_threshold' => 10, 'excess_fee' => 12],
        ]);

        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_CHIMNEY', 'Construction of Chimney',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 48, 'excess_threshold' => 10, 'excess_fee' => 2],
        ]);

        // i,j. Oven / Kiln / Furnace
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_OVEN', 'Commercial/Industrial Fixed Ovens (per sq.m interior)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 48]]);

        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_KILN', 'Industrial Kiln/Furnace (per cu.m volume)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 12]]);

        // k. Reinforced Concrete/Steel Tanks
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_RC_TANK_AG', 'Reinforced Concrete/Steel Tanks - Above Ground',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 480, 'excess_threshold' => 10, 'excess_fee' => 480],
        ]);

        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_RC_TANK_UG', 'Reinforced Concrete/Steel Tanks - Underground',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 20, 'fixed_fee' => 540, 'excess_threshold' => 20, 'excess_fee' => 24],
        ]);

        // l. Water/Waste Water Treatment Tanks
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_WATER_TREAT', 'Water/Waste Water Treatment Tanks (per cu.m volume)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 7]]);

        // m. Tanks Above Ground (group-based, 10 groups with different start ranges)
        // Groups 1-2: range 1-2, fee 12, excess 2, excess_fee 12
        // Groups 3-10: range 1-10, fee 480, excess 10, excess_fee 24
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_TANK_AG_SM', 'Tanks Above Ground - Small (Groups 1-2, per cu.m)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 2, 'fixed_fee' => 12, 'excess_threshold' => 2, 'excess_fee' => 12],
        ]);

        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_TANK_AG_LG', 'Tanks Above Ground - Large (Groups 3-10, per cu.m)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 480, 'excess_threshold' => 10, 'excess_fee' => 24],
        ]);

        // n. Pull-outs / Reinstallation of Steel Tanks
        $pullItems = [
            ['ACC_PULL_UG', 'Pullout/Reinstallation - Underground (per cu.m excavation)', 3],
            ['ACC_PULL_SADDLE', 'Pullout/Reinstallation - Saddle/Trestle Mounted (per cu.m)', 3],
        ];
        foreach ($pullItems as $p) {
            $feeTypeId = $this->upsertFeeType(
                'ACC_BLDG', $p[0], $p[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $p[2]]]);
        }

        // Reinstallation Vertical Storage Tanks (range-based, same as above ground tanks)
        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_REINST_SM', 'Reinstallation Vertical Storage Tanks - Small (Groups 1-2)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 2, 'fixed_fee' => 12, 'excess_threshold' => 2, 'excess_fee' => 12],
        ]);

        $feeTypeId = $this->upsertFeeType(
            'ACC_BLDG', 'ACC_REINST_LG', 'Reinstallation Vertical Storage Tanks - Large (Groups 3-10)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 480, 'excess_threshold' => 10, 'excess_fee' => 24],
        ]);

        // o. Booths / Kiosks / Platforms / Stages
        $boothItems = [
            ['ACC_BOOTH_PERM', 'Booth/Kiosk/Platform - Permanent Type (per sq.m)', 10],
            ['ACC_BOOTH_TEMP', 'Booth/Kiosk/Platform - Temporary Type (per sq.m)', 5],
            ['ACC_BOOTH_KNOCK', 'Booth/Kiosk/Platform - Knock-down Temporary (per unit inspection)', 24],
        ];
        foreach ($boothItems as $b) {
            $feeTypeId = $this->upsertFeeType(
                'ACC_BLDG', $b[0], $b[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $b[2]]]);
        }

        // p. Cemeteries / Memorial Parks
        $cemItems = [
            ['ACC_CEM_TOMB', 'Tombs (per sq.m covered ground area)', 5],
            ['ACC_CEM_SEMI', 'Semi-enclosed Mausoleums (per sq.m)', 5],
            ['ACC_CEM_ENCLOSED', 'Totally Enclosed Mausoleums (per sq.m floor area)', 12],
            ['ACC_CEM_MULTI', 'Multi-level Interment (per sq.m per level)', 5],
            ['ACC_CEM_COLUMB', 'Columbarium (per sq.m)', 18],
        ];
        foreach ($cemItems as $c) {
            $feeTypeId = $this->upsertFeeType(
                'ACC_BLDG', $c[0], $c[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $c[2]]]);
        }
    }

    // =========================================================================
    // 8. ACCESSORY FEES (ass_*)
    // =========================================================================

    private function seedAccessoryFees(): void
    {
        $order = 0;

        // a. Line and Grade
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_LINE_GRADE', 'Establishment of Line and Grade',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 24, 'excess_threshold' => 10, 'excess_fee' => 2.4],
        ]);

        // b. Ground Preparation and Excavation
        $gpItems = [
            ['ASS_GP_INSPECT', 'GP - Inspection and Verification Fee', 200],
            ['ASS_GP_EXCAV', 'GP - Per cu.m of excavation', 3],
            ['ASS_GP_ISSUANCE', 'GP - Issuance of GP & EP (valid 30 days)', 50],
            ['ASS_GP_FOUND', 'GP - Excavation for foundation with basement (per cu.m)', 4],
            ['ASS_GP_OTHER', 'GP - Excavation other than foundation (per cu.m)', 3],
            ['ASS_GP_ENCROACH', 'GP - Encroachment of footings to public areas (per sq.m)', 250],
        ];
        foreach ($gpItems as $g) {
            $feeTypeId = $this->upsertFeeType(
                'ACC_FEE', $g[0], $g[1],
                $g[2] >= 50 ? 'fixed' : 'per_unit', false, false, ++$order,
            );
            $schedule = $g[2] >= 50
                ? ['fixed_fee' => $g[2]]
                : ['fee_per_unit' => $g[2]];
            $this->syncSchedules($feeTypeId, [$schedule]);
        }

        // c. Fencing - Range-based
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_FENCE_MASONRY', 'Fencing - Masonry/Metal/Concrete',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 1.8, 'fee_per_unit' => 3, 'excess_threshold' => 1.8, 'excess_fee' => 4],
        ]);

        // Fencing - Indigenous / wire
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_FENCE_INDIG', 'Fencing - Indigenous/Barbed/Chicken/Hog Wire (per linear meter)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 2.4]]);

        // d. Construction of Pavement
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_PAVEMENT', 'Construction of Pavement',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 20, 'fixed_fee' => 24, 'excess_threshold' => 20, 'excess_fee' => 3],
        ]);

        // e/f. Streets and Sidewalks
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_SIDEWALK', 'Use of Streets and Sidewalks',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 20, 'fixed_fee' => 240, 'excess_threshold' => 20, 'excess_fee' => 12],
        ]);

        // g. Erection of Scaffoldings
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_SCAFFOLD', 'Erection of Scaffoldings (per calendar month)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 150, 'excess_threshold' => 10, 'excess_fee' => 12],
        ]);

        // h. Sign Fees
        // h.i. Erection/anchorage of display surface
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_SIGN_ERECT', 'Sign - Erection/Anchorage of Display Surface',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 4, 'fixed_fee' => 120, 'excess_threshold' => 4, 'excess_fee' => 24],
        ]);

        // h.ii. Sign Installation Fees
        $signInstall = [
            ['Neon', 36, 52], ['Illuminated', 24, 36], ['Painted-on', 9.6, 18], ['Others', 15, 24],
        ];
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_SIGN_INSTALL', 'Sign - Installation Fee (per sq.m)',
            'per_unit', false, false, ++$order,
        );
        $rows = [];
        foreach ($signInstall as $si) {
            $rows[] = ['formula' => "Business|{$si[0]}", 'fee_per_unit' => $si[1]];
            $rows[] = ['formula' => "Advertising|{$si[0]}", 'fee_per_unit' => $si[2]];
        }
        $this->syncSchedules($feeTypeId, $rows);

        // h.iii. Sign Annual Renewal Fees
        $signRenewal = [
            ['Neon', 36, 124, 46, 200],
            ['Illuminated', 18, 72, 38, 150],
            ['Painted-on', 8, 30, 20, 110],
            ['Others', 12, 40, 20, 110],
        ];
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_SIGN_RENEW', 'Sign - Annual Renewal Fee (per sq.m)',
            'per_unit', false, true, ++$order,
        );
        $rows = [];
        foreach ($signRenewal as $sr) {
            $rows[] = ['formula' => "Business|{$sr[0]}", 'fee_per_unit' => $sr[1], 'minimum_fee' => $sr[2]];
            $rows[] = ['formula' => "Advertising|{$sr[0]}", 'fee_per_unit' => $sr[3], 'minimum_fee' => $sr[4]];
        }
        $this->syncSchedules($feeTypeId, $rows);

        // i. Repairs Fee
        $repairItems = [
            ['ASS_REPAIR_VERT', 'Repairs - Alteration/Renovation on Vertical Dimensions (per sq.m)', 5, 'per_unit'],
            ['ASS_REPAIR_HORIZ', 'Repairs - Alteration/Renovation on Horizontal Dimensions (per sq.m)', 5, 'per_unit'],
            ['ASS_REPAIR_COST', 'Repairs - Costing more than P5,000 (1% of cost)', 0, 'percentage'],
        ];
        foreach ($repairItems as $r) {
            $feeTypeId = $this->upsertFeeType(
                'ACC_FEE', $r[0], $r[1], $r[3], false, false, ++$order,
            );
            if ($r[3] === 'percentage') {
                $this->syncSchedules($feeTypeId, [['percentage' => 0.01, 'excess_threshold' => 5000]]);
            } else {
                $this->syncSchedules($feeTypeId, [['fee_per_unit' => $r[2]]]);
            }
        }

        // j. Demolition Fees
        $demolItems = [
            ['ASS_DEMO_BLDG', 'Demolition - Buildings (per sq.m floor area)', 3],
            ['ASS_DEMO_FRAME', 'Demolition - Building Systems/Frames (per dimension)', 4],
            ['ASS_DEMO_MOVE', 'Moving Fee (per sq.m of area to be moved)', 3],
        ];
        foreach ($demolItems as $d) {
            $feeTypeId = $this->upsertFeeType(
                'ACC_FEE', $d[0], $d[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $d[2]]]);
        }

        // Demolition Structures Range
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_DEMO_STRUCT', 'Demolition - Structures (range-based)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 800, 'excess_threshold' => 10, 'excess_fee' => 50],
        ]);

        // Demolition Appendage Range
        $feeTypeId = $this->upsertFeeType(
            'ACC_FEE', 'ASS_DEMO_APPEND', 'Demolition - Appendages (range-based)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 3, 'fixed_fee' => 50, 'excess_threshold' => 3, 'excess_fee' => 50],
        ]);
    }

    // =========================================================================
    // 9. SURCHARGES
    // =========================================================================

    private function seedSurcharges(): void
    {
        $items = [
            ['SURCHARGE_LIGHT', 'Light Violation', 'fixed', 5000, null],
            ['SURCHARGE_LESS', 'Less Grave Violation', 'fixed', 8000, null],
            ['SURCHARGE_GRAVE', 'Grave Violation', 'fixed', 10000, null],
            ['SURCHARGE_EXCAV', 'Excavation for Foundation', 'percentage', null, 0.1],
            ['SURCHARGE_FOUND', 'Construction of Foundation (incl. pile driving & rebar)', 'percentage', null, 0.25],
            ['SURCHARGE_SUPER2', 'Superstructure up to 2.00m above grade', 'percentage', null, 0.5],
            ['SURCHARGE_SUPER', 'Superstructure above 2.00m', 'percentage', null, 1.0],
        ];

        $order = 0;
        foreach ($items as $item) {
            $feeTypeId = $this->upsertFeeType(
                'SURCHARGE', $item[0], $item[1], $item[2], false, false, ++$order,
            );
            $schedule = $item[2] === 'fixed'
                ? ['fixed_fee' => $item[3]]
                : ['percentage' => $item[4]];
            $this->syncSchedules($feeTypeId, [$schedule]);
        }
    }

    // =========================================================================
    // 10. OCCUPANCY FEES
    // =========================================================================

    private function seedOccupancyFees(): void
    {
        $order = 0;

        // a. Division A Buildings (based on cost of construction)
        $feeTypeId = $this->upsertFeeType(
            'OCC', 'OCC_DIV_A', 'Occupancy - Division A Buildings',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 150000, 'fixed_fee' => 100],
            ['range_from' => 150001, 'range_to' => 400000, 'fixed_fee' => 200],
            ['range_from' => 400001, 'range_to' => 850000, 'fixed_fee' => 400],
            ['range_from' => 850001, 'range_to' => 1000000000, 'fixed_fee' => 800, 'excess_threshold' => 1200000, 'excess_every' => 1000000, 'excess_fee' => 800],
        ]);

        // b. Division B Buildings
        $feeTypeId = $this->upsertFeeType(
            'OCC', 'OCC_DIV_B', 'Occupancy - Division B Buildings',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 150000, 'fixed_fee' => 200],
            ['range_from' => 150001, 'range_to' => 400000, 'fixed_fee' => 400],
            ['range_from' => 400001, 'range_to' => 850000, 'fixed_fee' => 800],
            ['range_from' => 850000, 'range_to' => 1000000000, 'fixed_fee' => 1000, 'excess_threshold' => 1200000, 'excess_every' => 1000000, 'excess_fee' => 1000],
        ]);

        // c. Division C/D Buildings
        $feeTypeId = $this->upsertFeeType(
            'OCC', 'OCC_DIV_CD', 'Occupancy - Division C/D Buildings',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 150000, 'fixed_fee' => 150],
            ['range_from' => 150001, 'range_to' => 400000, 'fixed_fee' => 250],
            ['range_from' => 400001, 'range_to' => 850000, 'fixed_fee' => 600],
            ['range_from' => 850001, 'range_to' => 1000000000, 'fixed_fee' => 900, 'excess_threshold' => 1200000, 'excess_every' => 1000000, 'excess_fee' => 900],
        ]);

        // d. Division J-I Buildings (floor area based)
        $feeTypeId = $this->upsertFeeType(
            'OCC', 'OCC_DIV_J1', 'Occupancy - Division J-I Buildings (by floor area)',
            'range_based', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 0, 'range_to' => 20, 'fixed_fee' => 50],
            ['range_from' => 21, 'range_to' => 500, 'fixed_fee' => 240],
            ['range_from' => 501, 'range_to' => 1000, 'fixed_fee' => 360],
            ['range_from' => 1001, 'range_to' => 5000, 'fixed_fee' => 480],
            ['range_from' => 5001, 'range_to' => 10000, 'fixed_fee' => 1200],
            ['range_from' => 10001, 'range_to' => 1000000000, 'fixed_fee' => 2400],
        ]);

        // e.i. Division J-II Rate (percentage of principal building fee)
        $feeTypeId = $this->upsertFeeType(
            'OCC', 'OCC_DIV_J2_RATE', 'Occupancy - Division J-II (garages/carports/balconies rate)',
            'percentage', false, false, ++$order,
            'Garages, carports, balconies, terraces, lanais: 50% of principal building rate'
        );
        $this->syncSchedules($feeTypeId, [['percentage' => 0.5]]);

        // e.ii. Division J-II E-II Range (aviaries, aquariums, zoo structures)
        $feeTypeId = $this->upsertFeeType(
            'OCC', 'OCC_DIV_J2_E2', 'Occupancy - Division J-II (aviaries/aquariums/zoo)',
            'range_based', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 0, 'range_to' => 20, 'fixed_fee' => 50],
            ['range_from' => 21, 'range_to' => 500, 'fixed_fee' => 240],
            ['range_from' => 501, 'range_to' => 1000, 'fixed_fee' => 360],
            ['range_from' => 1001, 'range_to' => 5000, 'fixed_fee' => 480],
            ['range_from' => 5001, 'range_to' => 10000, 'fixed_fee' => 1200],
            ['range_from' => 10001, 'range_to' => 1000000000, 'fixed_fee' => 2400],
        ]);

        // e.iii. Division J-II E-III Range (towers: radio/TV/cell site/water tank)
        $feeTypeId = $this->upsertFeeType(
            'OCC', 'OCC_DIV_J2_E3', 'Occupancy - Division J-II (towers: radio/TV/cell)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 800],
            ['range_from' => 11, 'range_to' => 1000000000, 'fixed_fee' => 800, 'excess_threshold' => 10, 'excess_every' => 1, 'excess_fee' => 50],
        ]);

        // f. Change in Use/Occupancy
        $feeTypeId = $this->upsertFeeType(
            'OCC', 'OCC_CHANGE_USE', 'Change in Use/Occupancy (per sq.m affected)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 5]]);
    }

    // =========================================================================
    // 11. ZONING FEES
    // =========================================================================

    private function seedZoningFees(): void
    {
        // --- Land Use and Zoning (Locational Clearance) ---
        // Organized by sub_group_id. The old system has 40 sub_groups.
        // Sub_groups 1-4: Residential (Group A) - lower fees
        // Sub_groups 5-11: Higher-fee groups
        // Sub_groups 12-40: Various commercial/institutional/industrial

        // Pattern 1: Residential-style (sub_groups 1-4, 10-11, 31, 34)
        $residentialPattern = [
            [1, 100000, 240, 0, 0],
            [100000.01, 200000, 480, 0, 0],
            [200000.01, 10000000000, 600, 200000, 0.001],
        ];

        // Pattern 2: Commercial-style (sub_groups 5-6, 15, 22-30, 33-36)
        $commercialPattern = [
            [1, 100000, 1200, 0, 0],
            [100000.01, 500000, 1800, 0, 0],
            [500000.01, 1000000, 2400, 0, 0],
            [1000000.01, 2000000, 3600, 0, 0],
            [2000000.01, 10000000000, 6000, 2000000, 0.001],
        ];

        // Pattern 3: Mid-tier (sub_groups 7, 9, 28-29)
        $midPattern = [
            [1, 500000, 1200, 0, 0],
            [500000.01, 2000000, 1800, 0, 0],
            [2000000.01, 10000000000, 3000, 2000000, 0.001],
        ];

        // Pattern 4: Heavy-tier (sub_groups 8, 26-27)
        $heavyPattern = [
            [1, 2000000, 3000, 0, 0],
            [2000000.01, 10000000000, 3000, 2000000, 0.001],
        ];

        // Pattern 5: Flat 2400 base (sub_groups 12-14, 16-21, 37-38, 48-53)
        $flatPattern = [
            [1, 2000000, 2400, 0, 0],
            [2000000.01, 10000000000, 2400, 2000000, 0.001],
        ];

        // Pattern 6: Top tier (sub_groups 39-40)
        $topPattern = [
            [1, 2000000, 6000, 0, 0],
            [2000000.01, 10000000000, 6000, 2000000, 0.001],
        ];

        // Map sub_group_id to pattern
        $subGroupPatterns = [];
        foreach ([1, 2, 3, 4, 10, 11, 31, 34] as $sg) {
            $subGroupPatterns[$sg] = $residentialPattern;
        }
        foreach ([5, 6, 15, 22, 23, 24, 25, 26, 27, 28, 29, 30, 32, 33, 35, 36] as $sg) {
            $subGroupPatterns[$sg] = $commercialPattern;
        }
        foreach ([7, 9] as $sg) {
            $subGroupPatterns[$sg] = $midPattern;
        }
        foreach ([8] as $sg) {
            $subGroupPatterns[$sg] = $heavyPattern;
        }
        foreach ([12, 13, 14, 16, 17, 18, 19, 20, 21, 37, 38, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59] as $sg) {
            $subGroupPatterns[$sg] = $flatPattern;
        }
        foreach ([39, 40] as $sg) {
            $subGroupPatterns[$sg] = $topPattern;
        }

        $feeTypeId = $this->upsertFeeType(
            'ZONING_LC', 'ZONING_LC_FEE', 'Locational Clearance Fee',
            'range_based', true, false, 1,
            'Based on estimated project cost, varies by occupancy sub-group'
        );

        $rows = [];
        foreach ($subGroupPatterns as $sgId => $pattern) {
            foreach ($pattern as $p) {
                $row = [
                    'range_from' => $p[0],
                    'range_to' => $p[1],
                    'fixed_fee' => $p[2],
                    'occupancy_sub_group_id' => $sgId,
                    'formula' => "sub_group_id:{$sgId}",
                ];
                if ($p[3] > 0) {
                    $row['excess_threshold'] = $p[3];
                    $row['percentage'] = $p[4];
                }
                $rows[] = $row;
            }
        }
        $this->syncSchedules($feeTypeId, $rows);

        // --- Land Use and Zoning Other Fees ---
        $feeTypeId = $this->upsertFeeType(
            'ZONING_LC', 'ZONING_LC_VARIANCE', 'Variance Application Fee',
            'fixed', false, false, 2,
        );
        $this->syncSchedules($feeTypeId, [['fixed_fee' => 0, 'formula' => 'For variance, each application']]);

        $feeTypeId = $this->upsertFeeType(
            'ZONING_LC', 'ZONING_LC_NONCONF', 'Non-Conforming Use Application Fee',
            'fixed', false, false, 3,
        );
        $this->syncSchedules($feeTypeId, [['fixed_fee' => 500]]);

        // --- Certification / Zoning Fees ---
        // All sub-groups get the same P500 flat fee
        $feeTypeId = $this->upsertFeeType(
            'ZONING_CERT', 'ZONING_CERT_FEE', 'Zoning Certification Fee',
            'fixed', false, false, 1,
            'Flat P500 fee applicable to all occupancy sub-groups'
        );
        $this->syncSchedules($feeTypeId, [['fixed_fee' => 500]]);
    }

    // =========================================================================
    // 12. ANNUAL INSPECTION FEES
    // =========================================================================

    private function seedAnnualInspectionFees(): void
    {
        // Annual inspection fees go under a new fee category within BP.
        // First ensure the category exists
        $bpPermitType = \App\Models\PermitType::where('code', 'BP')->first();
        if ($bpPermitType) {
            FeeCategory::updateOrCreate(
                ['code' => 'ANN_INSP'],
                [
                    'permit_type_id' => $bpPermitType->id,
                    'name' => 'Annual Inspection Fees',
                    'sort_order' => 12,
                ]
            );
        }

        $order = 0;

        // A. Building inspection (if owner requests)
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_A', 'Annual Building Inspection (per service, if requested)',
            'fixed', false, false, ++$order,
            'Land Use Conformity, Architectural Presentability, Structural Stability, Sanitary/Health, Fire-Resistive'
        );
        $this->syncSchedules($feeTypeId, [['fixed_fee' => 120]]);

        // B.i. Appendage range
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_BI_APPEND', 'Annual Inspection - Appendage (by number)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 3, 'fixed_fee' => 150, 'excess_threshold' => 3, 'excess_every' => 1, 'excess_fee' => 50],
        ]);

        // B.ii. Floor Area range
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_BI_FLOOR', 'Annual Inspection - Floor Area (sq.m)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 100, 'fixed_fee' => 120],
            ['range_from' => 101, 'range_to' => 200, 'fixed_fee' => 120],
            ['range_from' => 201, 'range_to' => 350, 'fixed_fee' => 480],
            ['range_from' => 351, 'range_to' => 500, 'fixed_fee' => 720],
            ['range_from' => 501, 'range_to' => 750, 'fixed_fee' => 960],
            ['range_from' => 751, 'range_to' => 1000, 'fixed_fee' => 1200, 'excess_threshold' => 1000, 'excess_every' => 1000, 'excess_fee' => 1200],
        ]);

        // C. Cinematographs / Theaters
        $cinemaItems = [
            ['AINSP_C_FIRST', 'Annual Inspection - First Class Cinematograph/Theater', 1200],
            ['AINSP_C_SECOND', 'Annual Inspection - Second Class Cinematograph/Theater', 720],
            ['AINSP_C_THIRD', 'Annual Inspection - Third Class Cinematograph/Theater', 520],
            ['AINSP_C_GRAND', 'Annual Inspection - Grandstands/Bleachers/Gymnasia', 720],
        ];
        foreach ($cinemaItems as $c) {
            $feeTypeId = $this->upsertFeeType(
                'ANN_INSP', $c[0], $c[1], 'fixed', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fixed_fee' => $c[2]]]);
        }

        // D. Annual plumbing inspection
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_D_PLUMB', 'Annual Plumbing Inspection (per plumbing unit)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 60]]);

        // E.i. Electrical inspection (percentage)
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_EI_ELEC', 'Annual Electrical Inspection (10% of Total Electrical Fees)',
            'percentage', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['percentage' => 0.1]]);

        // E.ii. Electronics inspection fees (per-unit items)
        $elecInspItems = [
            ['AINSP_ELEC_SWITCH', 'Electronics Insp. - Switching/Communications Equipment', 2.4],
            ['AINSP_ELEC_BCAST', 'Electronics Insp. - Broadcast Station/Cell Sites', 1000],
            ['AINSP_ELEC_ATM', 'Electronics Insp. - ATM/Vending/Medical Equipment', 10],
            ['AINSP_ELEC_OUTLET', 'Electronics Insp. - Communications Outlets', 2.4],
            ['AINSP_ELEC_SECUR', 'Electronics Insp. - Security/Alarm Systems', 2.4],
            ['AINSP_ELEC_STUDIO', 'Electronics Insp. - Studios/Auditoriums', 1000],
            ['AINSP_ELEC_TOWER', 'Electronics Insp. - Antenna Towers/Masts', 1000],
            ['AINSP_ELEC_SIGN', 'Electronics Insp. - Electronic Signage', 50],
            ['AINSP_ELEC_POLE', 'Electronics Insp. - Per Pole', 20],
            ['AINSP_ELEC_ATTACH', 'Electronics Insp. - Per Attachment', 20],
            ['AINSP_ELEC_OTHER', 'Electronics Insp. - Other Electronics Devices', 50],
        ];
        foreach ($elecInspItems as $ei) {
            $feeTypeId = $this->upsertFeeType(
                'ANN_INSP', $ei[0], $ei[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $ei[2]]]);
        }

        // F.i. Refrigeration/AC Range
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FI_REFRIG', 'Annual Mech Insp. - Refrigeration/AC (by ton)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 100, 'fee_per_unit' => 25],
            ['range_from' => 101, 'range_to' => 150, 'fee_per_unit' => 20],
            ['range_from' => 151, 'range_to' => 300, 'fee_per_unit' => 15],
            ['range_from' => 301, 'range_to' => 10000000, 'fee_per_unit' => 10, 'excess_threshold' => 500, 'excess_every' => 1, 'excess_fee' => 5],
        ]);

        // F.ii. Window type AC
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FII_WINAC', 'Annual Mech Insp. - Window Type AC (per unit)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 40]]);

        // F.iii. Centralized AC range
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FIII_CENAC', 'Annual Mech Insp. - Centralized AC (by ton)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 100, 'fee_per_unit' => 25],
            ['range_from' => 101, 'range_to' => 150, 'fee_per_unit' => 20],
            ['range_from' => 151, 'range_to' => 100000, 'fee_per_unit' => 20, 'excess_threshold' => 500, 'excess_every' => 1, 'excess_fee' => 8],
        ]);

        // F.iv. Escalator/Moving Walk range (by kW)
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FIV_ESC', 'Annual Mech Insp. - Escalator/Moving Walk (by kW)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 1.99, 'fee_per_unit' => 10],
            ['range_from' => 2, 'range_to' => 7.5, 'fee_per_unit' => 50],
            ['range_from' => 7.6, 'range_to' => 10000000, 'fee_per_unit' => 20, 'excess_threshold' => 7.5, 'excess_every' => 1, 'excess_fee' => 20],
        ]);

        // F.v. Escalator/Moving Walk - Other items
        $fvItems = [
            ['AINSP_FV_ESC', 'Annual Mech Insp. - Escalator/Moving Walk (per unit)', 120],
            ['AINSP_FV_FUNIC', 'Annual Mech Insp. - Funiculars (per kW)', 50],
            ['AINSP_FV_FUNIC_LM', 'Annual Mech Insp. - Funicular per lineal meter travel', 10],
            ['AINSP_FV_CABLE', 'Annual Mech Insp. - Cable Car (per kW)', 25],
            ['AINSP_FV_CABLE_LM', 'Annual Mech Insp. - Cable Car per lineal meter travel', 2],
        ];
        foreach ($fvItems as $fv) {
            $feeTypeId = $this->upsertFeeType(
                'ANN_INSP', $fv[0], $fv[1], 'per_unit', false, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [['fee_per_unit' => $fv[2]]]);
        }

        // F.vi. Elevators
        $fviItems = [
            ['AINSP_FVI_PASS', 'Annual Mech Insp. - Passenger Elevator', 500, 5, 50],
            ['AINSP_FVI_FRT', 'Annual Mech Insp. - Freight Elevator', 400, 5, 50],
            ['AINSP_FVI_DUMB', 'Annual Mech Insp. - Motor Driven Dumbwaiter', 50, 5, 50],
            ['AINSP_FVI_CONST', 'Annual Mech Insp. - Construction Elevator for Materials', 400, 5, 50],
            ['AINSP_FVI_CAR', 'Annual Mech Insp. - Car Elevator', 500, 5, 50],
        ];
        foreach ($fviItems as $fvi) {
            $feeTypeId = $this->upsertFeeType(
                'ANN_INSP', $fvi[0], $fvi[1], 'fixed', true, false, ++$order,
            );
            $this->syncSchedules($feeTypeId, [
                ['fixed_fee' => $fvi[2], 'excess_threshold' => $fvi[3], 'excess_fee' => $fvi[4]],
            ]);
        }

        // F.vii. Boilers range
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FVII_BOILER', 'Annual Mech Insp. - Boilers (by kW)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 7.5, 'fixed_fee' => 400],
            ['range_from' => 7.6, 'range_to' => 22, 'fixed_fee' => 550],
            ['range_from' => 22.01, 'range_to' => 37, 'fixed_fee' => 600],
            ['range_from' => 37.01, 'range_to' => 52, 'fixed_fee' => 650],
            ['range_from' => 52.01, 'range_to' => 67, 'fixed_fee' => 800],
            ['range_from' => 67.01, 'range_to' => 1000000, 'fixed_fee' => 900, 'excess_threshold' => 74, 'excess_every' => 1, 'excess_fee' => 4],
        ]);

        // F.viii. Pressurized Water Heaters
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FVIII_WHT', 'Annual Mech Insp. - Pressurized Water Heaters (per unit)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 120]]);

        // F.ix. Automatic Fire Extinguishers
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FIX_FIRE', 'Annual Mech Insp. - Fire Extinguishers (per sprinkler head)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 2]]);

        // F.x. Diesel/Gasoline range (annual)
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FX_DIESEL', 'Annual Mech Insp. - Diesel/Gasoline (by kW)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 5, 'fee_per_unit' => 55],
            ['range_from' => 6, 'range_to' => 10, 'fee_per_unit' => 90],
            ['range_from' => 11, 'range_to' => 1000000, 'fee_per_unit' => 90, 'excess_threshold' => 10, 'excess_every' => 1, 'excess_fee' => 2],
        ]);

        // F.xi. Other Internal Combustion range (annual)
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXI_INTCOMB', 'Annual Mech Insp. - Other Internal Combustion (by kW)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 50, 'fee_per_unit' => 15],
            ['range_from' => 51, 'range_to' => 100, 'fee_per_unit' => 10],
            ['range_from' => 101, 'range_to' => 1000000, 'fee_per_unit' => 10, 'excess_threshold' => 100, 'excess_every' => 1, 'excess_fee' => 2.4],
        ]);

        // F.xii. Compressed air / gases
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXII_COMP', 'Annual Mech Insp. - Compressed Air/Gases (per outlet)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 10]]);

        // F.xiii. Power piping
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXIII_PIPE', 'Annual Mech Insp. - Power Piping (per lineal meter)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 2]]);

        // F.xiv. Gas meter testing range
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXIV_GAS', 'Annual Mech Insp. - Gas Meter Range',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 1, 'range_to' => 10, 'fixed_fee' => 100],
            ['range_from' => 11, 'range_to' => 1000000, 'fixed_fee' => 100, 'excess_threshold' => 10, 'excess_every' => 1, 'excess_fee' => 3],
        ]);

        // F.xv. Water pumps range
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXV_PUMP', 'Annual Mech Insp. - Water Pumps (by kW)',
            'range_based', true, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [
            ['range_from' => 0.01, 'range_to' => 0.5, 'fixed_fee' => 8],
            ['range_from' => 0.51, 'range_to' => 1, 'fixed_fee' => 23],
            ['range_from' => 1.01, 'range_to' => 3, 'fixed_fee' => 39],
            ['range_from' => 3.01, 'range_to' => 5, 'fixed_fee' => 55],
            ['range_from' => 5.01, 'range_to' => 10, 'fixed_fee' => 80],
            ['range_from' => 11, 'range_to' => 1000000, 'fixed_fee' => 80, 'excess_threshold' => 10, 'excess_every' => 1, 'excess_fee' => 4],
        ]);

        // F.xvi. Pressure Vessels
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXVI_PRESS', 'Annual Mech Insp. - Pressure Vessels (per cu.m)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 40]]);

        // F.xvii. Pneumatic tubes / conveyors
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXVII_PNEU', 'Annual Mech Insp. - Pneumatic Tubes/Conveyors (per lineal meter)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 2.4]]);

        // F.xviii. Weighing Scale Structure
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXVIII_WEIGH', 'Annual Mech Insp. - Weighing Scale (per ton)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 30]]);

        // F.xix. Testing / Calibration
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXIX_CALIB', 'Annual Mech Insp. - Pressure Gauge Calibration (per unit)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 24]]);

        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXIX_GASM', 'Annual Mech Insp. - Gas Meter (tested/proved/sealed)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 30]]);

        // F.xx. Mechanical rides
        $feeTypeId = $this->upsertFeeType(
            'ANN_INSP', 'AINSP_FXX_RIDE', 'Annual Mech Insp. - Mechanical Rides (per unit)',
            'per_unit', false, false, ++$order,
        );
        $this->syncSchedules($feeTypeId, [['fee_per_unit' => 30]]);
    }
}
