<?php

namespace Database\Seeders;

use App\Models\ApplicationType;
use App\Models\BuildingPart;
use App\Models\FeeCategory;
use App\Models\FormOfOwnership;
use App\Models\LandClassification;
use App\Models\OccupancyDivision;
use App\Models\OccupancyGroup;
use App\Models\OccupancySubGroup;
use App\Models\PermitType;
use App\Models\ScopeOfWork;
use App\Models\Signatory;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedPermitTypes();
        $this->seedApplicationTypes();
        $this->seedScopeOfWorks();
        $this->seedFormOfOwnerships();
        $this->seedOccupancyGroups();
        $this->seedOccupancyDivisions();
        $this->seedBuildingParts();
        $this->seedLandClassifications();
        $this->seedSignatories();
        $this->seedFeeCategories();
    }

    /**
     * Seed permit types.
     */
    private function seedPermitTypes(): void
    {
        $types = [
            ['code' => 'BP',  'name' => 'Building Permit',     'sort_order' => 1,  'is_active' => true],
            ['code' => 'OP',  'name' => 'Occupancy Permit',    'sort_order' => 2,  'is_active' => true],
            ['code' => 'FP',  'name' => 'Fencing Permit',      'sort_order' => 3,  'is_active' => false],
            ['code' => 'EP',  'name' => 'Excavation Permit',   'sort_order' => 4,  'is_active' => false],
            ['code' => 'DP',  'name' => 'Demolition Permit',   'sort_order' => 5,  'is_active' => false],
            ['code' => 'SP',  'name' => 'Sign Permit',         'sort_order' => 6,  'is_active' => false],
            ['code' => 'ELP', 'name' => 'Electrical Permit',   'sort_order' => 7,  'is_active' => false],
            ['code' => 'MP',  'name' => 'Mechanical Permit',   'sort_order' => 8,  'is_active' => false],
            ['code' => 'PP',  'name' => 'Plumbing Permit',     'sort_order' => 9,  'is_active' => false],
            ['code' => 'ECP', 'name' => 'Electronics Permit',  'sort_order' => 10, 'is_active' => false],
        ];

        foreach ($types as $type) {
            PermitType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }

    /**
     * Seed application types.
     */
    private function seedApplicationTypes(): void
    {
        $bpPermitType = PermitType::where('code', 'BP')->first();
        $opPermitType = PermitType::where('code', 'OP')->first();

        // Assign existing unlinked types to BP
        if ($bpPermitType) {
            ApplicationType::whereNull('permit_type_id')
                ->whereIn('name', ['New', 'Renewal', 'Amendatory'])
                ->update(['permit_type_id' => $bpPermitType->id]);
        }

        $bpTypes = [
            ['name' => 'New',        'sort_order' => 1],
            ['name' => 'Renewal',    'sort_order' => 2],
            ['name' => 'Amendatory', 'sort_order' => 3],
        ];

        foreach ($bpTypes as $type) {
            ApplicationType::updateOrCreate(
                ['name' => $type['name'], 'permit_type_id' => $bpPermitType?->id],
                array_merge($type, ['permit_type_id' => $bpPermitType?->id])
            );
        }

        if ($opPermitType) {
            $opTypes = [
                ['name' => 'Full',    'sort_order' => 1],
                ['name' => 'Partial', 'sort_order' => 2],
            ];

            foreach ($opTypes as $type) {
                ApplicationType::updateOrCreate(
                    ['name' => $type['name'], 'permit_type_id' => $opPermitType->id],
                    array_merge($type, ['permit_type_id' => $opPermitType->id])
                );
            }
        }
    }

    /**
     * Seed scope of works.
     */
    private function seedScopeOfWorks(): void
    {
        $scopes = [
            ['name' => 'New Construction',    'category' => 'construction', 'sort_order' => 1],
            ['name' => 'Addition',            'category' => 'construction', 'sort_order' => 2],
            ['name' => 'Renovation',          'category' => 'construction', 'sort_order' => 3],
            ['name' => 'Alteration',          'category' => 'construction', 'sort_order' => 4],
            ['name' => 'Conversion',          'category' => 'construction', 'sort_order' => 5],
            ['name' => 'Repair',              'category' => 'construction', 'sort_order' => 6],
            ['name' => 'Raising',             'category' => 'other',        'sort_order' => 7],
            ['name' => 'Moving',              'category' => 'other',        'sort_order' => 8],
            ['name' => 'Demolition',          'category' => 'other',        'sort_order' => 9],
            ['name' => 'Accessory Structure', 'category' => 'other',        'sort_order' => 10],
            ['name' => 'Erection',            'category' => 'other',        'sort_order' => 11],
            ['name' => 'Legalization',        'category' => 'other',        'sort_order' => 12],
            ['name' => 'Others (Specify)',    'category' => 'other',        'sort_order' => 13],
        ];

        foreach ($scopes as $scope) {
            ScopeOfWork::updateOrCreate(
                ['name' => $scope['name']],
                $scope
            );
        }
    }

    /**
     * Seed form of ownerships.
     */
    private function seedFormOfOwnerships(): void
    {
        $ownerships = [
            'Sole Proprietorship',
            'Partnership',
            'Corporation',
            'Cooperative',
            'Government',
            'Others',
        ];

        foreach ($ownerships as $ownership) {
            FormOfOwnership::updateOrCreate(
                ['name' => $ownership],
                ['name' => $ownership]
            );
        }
    }

    /**
     * Seed occupancy groups and their sub-groups.
     */
    private function seedOccupancyGroups(): void
    {
        $groups = [
            [
                'code' => 'A', 'name' => 'Residential', 'sort_order' => 1,
                'sub_groups' => [
                    ['code' => 'A-1', 'name' => 'Single Family Dwelling',    'sort_order' => 1],
                    ['code' => 'A-2', 'name' => 'Multi-Family Dwelling',     'sort_order' => 2],
                    ['code' => 'A-3', 'name' => 'Boarding/Lodging House',    'sort_order' => 3],
                ],
            ],
            [
                'code' => 'B', 'name' => 'Educational', 'sort_order' => 2,
                'sub_groups' => [
                    ['code' => 'B-1', 'name' => 'School/University',         'sort_order' => 1],
                    ['code' => 'B-2', 'name' => 'Training Center',           'sort_order' => 2],
                ],
            ],
            [
                'code' => 'C', 'name' => 'Institutional', 'sort_order' => 3,
                'sub_groups' => [
                    ['code' => 'C-1', 'name' => 'Hospital/Clinic',           'sort_order' => 1],
                    ['code' => 'C-2', 'name' => 'Church/Religious',          'sort_order' => 2],
                    ['code' => 'C-3', 'name' => 'Government Office',         'sort_order' => 3],
                ],
            ],
            [
                'code' => 'D', 'name' => 'Business/Mercantile', 'sort_order' => 4,
                'sub_groups' => [
                    ['code' => 'D-1', 'name' => 'Commercial/Retail Store',   'sort_order' => 1],
                    ['code' => 'D-2', 'name' => 'Office Building',           'sort_order' => 2],
                    ['code' => 'D-3', 'name' => 'Market/Shopping Center',    'sort_order' => 3],
                ],
            ],
            [
                'code' => 'E', 'name' => 'Industrial', 'sort_order' => 5,
                'sub_groups' => [
                    ['code' => 'E-1', 'name' => 'Factory/Plant',             'sort_order' => 1],
                    ['code' => 'E-2', 'name' => 'Workshop',                  'sort_order' => 2],
                    ['code' => 'E-3', 'name' => 'Power Plant',               'sort_order' => 3],
                ],
            ],
            [
                'code' => 'F', 'name' => 'Storage/Hazardous', 'sort_order' => 6,
                'sub_groups' => [
                    ['code' => 'F-1', 'name' => 'Warehouse/Storage',         'sort_order' => 1],
                    ['code' => 'F-2', 'name' => 'Hazardous Materials',       'sort_order' => 2],
                ],
            ],
            [
                'code' => 'G', 'name' => 'Assembly', 'sort_order' => 7,
                'sub_groups' => [
                    ['code' => 'G-1', 'name' => 'Convention Hall/Theater',   'sort_order' => 1],
                    ['code' => 'G-2', 'name' => 'Sports Facility/Gym',      'sort_order' => 2],
                    ['code' => 'G-3', 'name' => 'Restaurant/Bar',            'sort_order' => 3],
                    ['code' => 'G-4', 'name' => 'Hotel/Resort',              'sort_order' => 4],
                ],
            ],
            [
                'code' => 'H', 'name' => 'Accessory', 'sort_order' => 8,
                'sub_groups' => [
                    ['code' => 'H-1', 'name' => 'Garage/Carport',            'sort_order' => 1],
                    ['code' => 'H-2', 'name' => 'Fence/Gate',                'sort_order' => 2],
                    ['code' => 'H-3', 'name' => 'Guard House',               'sort_order' => 3],
                    ['code' => 'H-4', 'name' => 'Swimming Pool',             'sort_order' => 4],
                ],
            ],
            [
                'code' => 'I', 'name' => 'Agricultural', 'sort_order' => 9,
                'sub_groups' => [
                    ['code' => 'I-1', 'name' => 'Farm Building',             'sort_order' => 1],
                    ['code' => 'I-2', 'name' => 'Greenhouse/Nursery',        'sort_order' => 2],
                ],
            ],
            [
                'code' => 'J', 'name' => 'Mixed Occupancy', 'sort_order' => 10,
                'sub_groups' => [
                    ['code' => 'J-1', 'name' => 'Residential/Commercial',    'sort_order' => 1],
                    ['code' => 'J-2', 'name' => 'Commercial/Industrial',     'sort_order' => 2],
                    ['code' => 'J-3', 'name' => 'Mixed Use Complex',         'sort_order' => 3],
                ],
            ],
        ];

        foreach ($groups as $groupData) {
            $subGroups = $groupData['sub_groups'];
            unset($groupData['sub_groups']);

            $group = OccupancyGroup::updateOrCreate(
                ['code' => $groupData['code']],
                $groupData
            );

            foreach ($subGroups as $subGroup) {
                OccupancySubGroup::updateOrCreate(
                    ['occupancy_group_id' => $group->id, 'code' => $subGroup['code']],
                    array_merge($subGroup, ['occupancy_group_id' => $group->id])
                );
            }
        }
    }

    /**
     * Seed occupancy divisions with assessment modes.
     *
     * Based on the original BOPMS system:
     * - Residential (A1, A2) use non_cumulative assessment
     * - Most other divisions use non_cumulative
     * - Larger commercial/industrial groups (D3, E2, E3, G3, G4) use cumulative
     */
    private function seedOccupancyDivisions(): void
    {
        $divisions = [
            // Group A - Residential (non_cumulative)
            ['group_code' => 'A', 'code' => 'A1', 'name' => 'Division A-1 (Single Family Dwelling)',        'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'A', 'code' => 'A2', 'name' => 'Division A-2 (Multi-Family Dwelling)',         'assessment_mode' => 'non_cumulative'],

            // Group B - Educational (non_cumulative)
            ['group_code' => 'B', 'code' => 'B1', 'name' => 'Division B-1 (School/University)',             'assessment_mode' => 'non_cumulative'],

            // Group C - Institutional (non_cumulative)
            ['group_code' => 'C', 'code' => 'C1', 'name' => 'Division C-1 (Hospital/Medical)',              'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'C', 'code' => 'C2', 'name' => 'Division C-2 (Religious/Civic)',               'assessment_mode' => 'non_cumulative'],

            // Group D - Business/Mercantile (mixed modes)
            ['group_code' => 'D', 'code' => 'D1', 'name' => 'Division D-1 (Small Commercial)',              'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'D', 'code' => 'D2', 'name' => 'Division D-2 (Office Building)',               'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'D', 'code' => 'D3', 'name' => 'Division D-3 (Large Commercial/Mall)',         'assessment_mode' => 'cumulative'],

            // Group E - Industrial (mixed modes)
            ['group_code' => 'E', 'code' => 'E1', 'name' => 'Division E-1 (Light Industrial)',              'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'E', 'code' => 'E2', 'name' => 'Division E-2 (Medium Industrial)',             'assessment_mode' => 'cumulative'],
            ['group_code' => 'E', 'code' => 'E3', 'name' => 'Division E-3 (Heavy Industrial)',              'assessment_mode' => 'cumulative'],

            // Group F - Storage/Hazardous (non_cumulative)
            ['group_code' => 'F', 'code' => 'F1', 'name' => 'Division F-1 (Storage/Warehouse)',             'assessment_mode' => 'non_cumulative'],

            // Group G - Assembly (mixed modes)
            ['group_code' => 'G', 'code' => 'G1', 'name' => 'Division G-1 (Small Assembly)',                'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'G', 'code' => 'G2', 'name' => 'Division G-2 (Medium Assembly)',               'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'G', 'code' => 'G3', 'name' => 'Division G-3 (Large Assembly)',                'assessment_mode' => 'cumulative'],
            ['group_code' => 'G', 'code' => 'G4', 'name' => 'Division G-4 (Hotel/Resort)',                  'assessment_mode' => 'cumulative'],

            // Group H - Accessory (non_cumulative)
            ['group_code' => 'H', 'code' => 'H1', 'name' => 'Division H-1 (Garage/Carport)',               'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'H', 'code' => 'H2', 'name' => 'Division H-2 (Fence/Gate)',                   'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'H', 'code' => 'H3', 'name' => 'Division H-3 (Guard House)',                  'assessment_mode' => 'non_cumulative'],
            ['group_code' => 'H', 'code' => 'H4', 'name' => 'Division H-4 (Swimming Pool)',                'assessment_mode' => 'non_cumulative'],

            // Group I - Agricultural (non_cumulative)
            ['group_code' => 'I', 'code' => 'I1', 'name' => 'Division I-1 (Farm Structure)',                'assessment_mode' => 'non_cumulative'],

            // Group J - Mixed Occupancy (cumulative - follows highest division)
            ['group_code' => 'J', 'code' => 'J1', 'name' => 'Division J-1 (Residential/Commercial Mix)',    'assessment_mode' => 'cumulative'],
            ['group_code' => 'J', 'code' => 'J2', 'name' => 'Division J-2 (Commercial/Industrial Mix)',     'assessment_mode' => 'cumulative'],
            ['group_code' => 'J', 'code' => 'J3', 'name' => 'Division J-3 (Multi-Use Complex)',             'assessment_mode' => 'cumulative'],
        ];

        foreach ($divisions as $division) {
            $group = OccupancyGroup::where('code', $division['group_code'])->first();

            if (! $group) {
                continue;
            }

            OccupancyDivision::updateOrCreate(
                ['code' => $division['code']],
                [
                    'occupancy_group_id' => $group->id,
                    'code' => $division['code'],
                    'name' => $division['name'],
                    'assessment_mode' => $division['assessment_mode'],
                ]
            );
        }
    }

    /**
     * Seed building parts / structural materials.
     */
    private function seedBuildingParts(): void
    {
        $parts = ['Concrete', 'Steel', 'Wood', 'Mixed'];

        foreach ($parts as $part) {
            BuildingPart::updateOrCreate(
                ['name' => $part],
                ['name' => $part]
            );
        }
    }

    /**
     * Seed land classifications.
     */
    private function seedLandClassifications(): void
    {
        $classifications = [
            ['id' => 1, 'code' => 'R', 'name' => 'Residential'],
            ['id' => 2, 'code' => 'A', 'name' => 'Agricultural'],
            ['id' => 3, 'code' => 'C', 'name' => 'Commercial'],
            ['id' => 4, 'code' => 'I', 'name' => 'Industrial'],
            ['id' => 5, 'code' => 'M', 'name' => 'Mineral'],
            ['id' => 6, 'code' => 'T', 'name' => 'Timberland/Forest'],
            ['id' => 7, 'code' => 'SPE', 'name' => 'Special'],
            ['id' => 8, 'code' => 'SH', 'name' => 'Hospital'],
            ['id' => 9, 'code' => 'SR', 'name' => 'Religious'],
            ['id' => 10, 'code' => 'SB', 'name' => 'Beach Lot'],
            ['id' => 11, 'code' => 'SRL', 'name' => 'Road Lot'],
            ['id' => 12, 'code' => 'SC', 'name' => 'Cultural'],
            ['id' => 13, 'code' => 'SS', 'name' => 'Scientific'],
            ['id' => 14, 'code' => 'SW', 'name' => 'Local Water District'],
            ['id' => 15, 'code' => 'SG', 'name' => 'Corp. engaged in Generation/distribution of electric Power'],
            ['id' => 16, 'code' => 'SCH', 'name' => 'School Lot'],
            ['id' => 17, 'code' => 'O', 'name' => 'Others'],
            ['id' => 18, 'code' => 'SP10', 'name' => 'Special 10'],
            ['id' => 19, 'code' => 'SP15', 'name' => 'Special 15'],
            ['id' => 20, 'code' => 'GOV', 'name' => 'Government'],
            ['id' => 21, 'code' => 'RGOV', 'name' => "Gov't/Res"],
            ['id' => 22, 'code' => 'CGOV', 'name' => "Gov't/Com"],
            ['id' => 23, 'code' => 'AGOV', 'name' => "Gov't/Agri"],
            ['id' => 24, 'code' => 'IGOV', 'name' => "Gov't/Ind"],
            ['id' => 25, 'code' => 'CI', 'name' => 'Charitable Institution'],
            ['id' => 26, 'code' => 'CEM', 'name' => 'Cemetery'],
            ['id' => 27, 'code' => 'RCL', 'name' => 'Recreational'],
            ['id' => 28, 'code' => 'INSB', 'name' => 'Institutional Bldg.'],
            ['id' => 29, 'code' => 'INS', 'name' => 'Institutional'],
            ['id' => 30, 'code' => 'GOCCS', 'name' => 'GOCCs'],
        ];

        foreach ($classifications as $c) {
            LandClassification::updateOrCreate(
                ['id' => $c['id']],
                ['code' => $c['code'], 'name' => $c['name'], 'is_active' => true]
            );
        }
    }

    /**
     * Seed signatories.
     */
    private function seedSignatories(): void
    {
        $signatories = [
            [
                'role' => 'building_official',
                'name' => 'City Engineer',
                'title' => 'Engr.',
                'designation' => 'City Engineer / Building Official',
            ],
            [
                'role' => 'planning_officer',
                'name' => 'Planning Officer',
                'title' => '',
                'designation' => 'City Planning and Development Coordinator',
            ],
            [
                'role' => 'treasury_officer',
                'name' => 'City Treasurer',
                'title' => '',
                'designation' => 'City Treasurer',
            ],
        ];

        foreach ($signatories as $signatory) {
            Signatory::updateOrCreate(
                ['role' => $signatory['role']],
                $signatory
            );
        }
    }

    /**
     * Seed fee categories for Building Permit and Occupancy Permit.
     */
    private function seedFeeCategories(): void
    {
        $bpPermitType = PermitType::where('code', 'BP')->first();
        $opPermitType = PermitType::where('code', 'OP')->first();

        if (! $bpPermitType || ! $opPermitType) {
            return;
        }

        // Building Permit fee categories
        $bpCategories = [
            ['code' => 'CONST',     'name' => 'Construction Fees',            'sort_order' => 1],
            ['code' => 'ELEC',      'name' => 'Electrical Fees',              'sort_order' => 2],
            ['code' => 'MECH',      'name' => 'Mechanical Fees',              'sort_order' => 3],
            ['code' => 'PLUMB',     'name' => 'Plumbing/Sanitary Fees',       'sort_order' => 4],
            ['code' => 'ELECT',     'name' => 'Electronics Fees',             'sort_order' => 5],
            ['code' => 'ACC_BLDG',  'name' => 'Accessories of the Building',  'sort_order' => 6],
            ['code' => 'ACC_FEE',   'name' => 'Accessory Fees',               'sort_order' => 7],
            ['code' => 'SURCHARGE', 'name' => 'Surcharges',                   'sort_order' => 8],
            ['code' => 'VIOLATION', 'name' => 'Violations',                   'sort_order' => 9],
        ];

        foreach ($bpCategories as $category) {
            FeeCategory::updateOrCreate(
                ['code' => $category['code']],
                array_merge($category, ['permit_type_id' => $bpPermitType->id])
            );
        }

        // Occupancy Permit fee categories
        FeeCategory::updateOrCreate(
            ['code' => 'OCC'],
            [
                'code' => 'OCC',
                'name' => 'Occupancy Fees',
                'permit_type_id' => $opPermitType->id,
                'sort_order' => 1,
            ]
        );

        // Zoning fee categories (associated with Building Permit)
        $zoningCategories = [
            ['code' => 'ZONING_LC',   'name' => 'Locational Clearance',  'sort_order' => 10],
            ['code' => 'ZONING_CERT', 'name' => 'Zoning Certification',  'sort_order' => 11],
        ];

        foreach ($zoningCategories as $category) {
            FeeCategory::updateOrCreate(
                ['code' => $category['code']],
                array_merge($category, ['permit_type_id' => $bpPermitType->id])
            );
        }
    }
}
