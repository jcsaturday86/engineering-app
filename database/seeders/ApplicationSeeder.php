<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationOccupancyGroup;
use App\Models\Barangay;
use App\Models\City;
use App\Models\OccupancyApplication;
use App\Models\OccupancySubGroup;
use App\Models\PermitType;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $province = Province::where('name', 'like', '%LA UNION%')->first();
        $city = City::where('province_id', $province->id)->where('name', 'like', '%SAN FERNANDO%')->first();
        $barangays = Barangay::where('city_id', $city->id)->take(10)->pluck('id')->toArray();
        $bpPermitType = PermitType::where('code', 'BP')->first();

        $now = now();
        $year = $now->year;
        $month = $now->month;

        $bpRecords = [
            [
                'application_type_id' => 1, // New
                'complexity' => 'Simple',
                'applies_to' => '',
                'status' => 'for_zoning_assessment',
                'applicant_first_name' => 'Maria',
                'applicant_middle_name' => 'Santos',
                'applicant_last_name' => 'Reyes',
                'applicant_suffix' => '',
                'applicant_tin' => '123-456-789-000',
                'applicant_contact_no' => '09171234567',
                'applicant_email' => 'maria.reyes@email.com',
                'enterprise_name' => 'Reyes Builders',
                'form_of_ownership_id' => 1,
                'project_title' => 'Two-Storey Residential House',
                'scope_of_work_id' => 1,
                'scope_of_work_details' => '',
                'lot_no' => '12', 'block_no' => '5', 'tct_no' => 'T-12345', 'tax_dec_no' => 'TD-2024-001',
                'land_classification_id' => 1,
                'no_of_storeys' => 2, 'no_of_units' => 1, 'total_floor_area' => 150.00, 'lot_area' => 200.00,
                'building_cost' => 2500000, 'electrical_cost' => 150000, 'mechanical_cost' => 100000,
                'electronics_cost' => 50000, 'plumbing_cost' => 80000, 'other_equipment_cost' => 0,
                'proposed_construction_date' => '2026-07-15', 'expected_completion_date' => '2027-07-15',
                'engineer_name' => 'Engr. Roberto Cruz', 'engineer_prc_no' => 'CE-0012345',
                'engineer_prc_validity' => '2027-12-31', 'engineer_ptr_no' => 'PTR-2026-001',
                'engineer_ptr_date_issued' => '2026-01-15', 'engineer_ptr_issued_at' => 'San Fernando, La Union',
                'engineer_tin' => '987-654-321-000', 'engineer_address' => 'Poro, SFC La Union',
                'engineer_date_signed' => '2026-06-20',
                'owner_name' => 'Maria Santos Reyes', 'owner_address' => 'Bacsil, SFC La Union',
                'occupancy_sub_groups' => [1, 3],
                'include_electrical' => false,
            ],
            [
                'application_type_id' => 2, // Renewal
                'complexity' => 'Complex',
                'applies_to' => 'SKIP_LC',
                'status' => 'submitted',
                'applicant_first_name' => 'Jose',
                'applicant_middle_name' => 'Dela',
                'applicant_last_name' => 'Cruz',
                'applicant_suffix' => 'Jr.',
                'applicant_tin' => '111-222-333-000',
                'applicant_contact_no' => '09281234567',
                'applicant_email' => 'jose.delacruz@email.com',
                'enterprise_name' => 'JDC Commercial Center',
                'form_of_ownership_id' => 3,
                'project_title' => 'Commercial Building Renovation',
                'scope_of_work_id' => 3,
                'scope_of_work_details' => 'Interior renovation of 2nd and 3rd floor',
                'lot_no' => '8', 'block_no' => '2', 'tct_no' => 'T-67890', 'tax_dec_no' => 'TD-2024-015',
                'land_classification_id' => 3,
                'no_of_storeys' => 3, 'no_of_units' => 6, 'total_floor_area' => 450.00, 'lot_area' => 300.00,
                'building_cost' => 5000000, 'electrical_cost' => 300000, 'mechanical_cost' => 200000,
                'electronics_cost' => 100000, 'plumbing_cost' => 150000, 'other_equipment_cost' => 50000,
                'proposed_construction_date' => '2026-08-01', 'expected_completion_date' => '2027-02-01',
                'engineer_name' => 'Engr. Ana Garcia', 'engineer_prc_no' => 'CE-0054321',
                'engineer_prc_validity' => '2028-06-30', 'engineer_ptr_no' => 'PTR-2026-088',
                'engineer_ptr_date_issued' => '2026-02-10', 'engineer_ptr_issued_at' => 'San Fernando, La Union',
                'engineer_tin' => '555-666-777-000', 'engineer_address' => 'Parian, SFC La Union',
                'engineer_date_signed' => '2026-06-18',
                'owner_name' => 'Jose Dela Cruz Jr.', 'owner_address' => 'Bangcusay, SFC La Union',
                'occupancy_sub_groups' => [22, 23],
                'include_electrical' => true,
                'total_connected_load' => 150.50, 'total_transformer_capacity' => 200.00, 'total_generator_capacity' => 100.00,
                'pee_name' => 'Engr. Marco Tan', 'pee_prc_no' => 'REE-0011223', 'pee_prc_validity' => '2028-03-31',
                'pee_date_signed' => '2026-06-18', 'pee_ptr_no' => 'PTR-2026-112',
                'pee_ptr_date_issued' => '2026-01-20', 'pee_ptr_issued_at' => 'San Fernando, La Union',
                'pee_address' => 'Catbangen, SFC La Union', 'pee_tin' => '444-555-666-000',
                'sew_profession' => 'REE', 'sew_name' => 'Engr. Marco Tan', 'sew_prc_no' => 'REE-0011223',
                'sew_prc_validity' => '2028-03-31', 'sew_date_signed' => '2026-06-18',
                'sew_ptr_no' => 'PTR-2026-112', 'sew_ptr_date_issued' => '2026-01-20',
                'sew_ptr_issued_at' => 'San Fernando, La Union', 'sew_address' => 'Catbangen, SFC La Union',
                'sew_tin' => '444-555-666-000',
            ],
            [
                'application_type_id' => 1, // New
                'complexity' => 'Simple',
                'applies_to' => 'SKIP_LC',
                'status' => 'submitted',
                'applicant_first_name' => 'Elena',
                'applicant_middle_name' => 'Ramos',
                'applicant_last_name' => 'Fernandez',
                'applicant_suffix' => '',
                'applicant_tin' => '222-333-444-000',
                'applicant_contact_no' => '09391234567',
                'applicant_email' => 'elena.fernandez@email.com',
                'enterprise_name' => '',
                'form_of_ownership_id' => null,
                'project_title' => 'Single-Storey Bungalow',
                'scope_of_work_id' => 1,
                'scope_of_work_details' => '',
                'lot_no' => '3', 'block_no' => '1', 'tct_no' => 'T-11111', 'tax_dec_no' => 'TD-2025-003',
                'land_classification_id' => 1,
                'no_of_storeys' => 1, 'no_of_units' => 1, 'total_floor_area' => 80.00, 'lot_area' => 150.00,
                'building_cost' => 1200000, 'electrical_cost' => 80000, 'mechanical_cost' => 0,
                'electronics_cost' => 0, 'plumbing_cost' => 50000, 'other_equipment_cost' => 0,
                'proposed_construction_date' => '2026-09-01', 'expected_completion_date' => '2027-03-01',
                'engineer_name' => 'Engr. Roberto Cruz', 'engineer_prc_no' => 'CE-0012345',
                'engineer_prc_validity' => '2027-12-31', 'engineer_ptr_no' => 'PTR-2026-001',
                'engineer_ptr_date_issued' => '2026-01-15', 'engineer_ptr_issued_at' => 'San Fernando, La Union',
                'engineer_tin' => '987-654-321-000', 'engineer_address' => 'Poro, SFC La Union',
                'engineer_date_signed' => '2026-06-22',
                'owner_name' => 'Elena Ramos Fernandez', 'owner_address' => 'Apaleng, SFC La Union',
                'occupancy_sub_groups' => [1],
                'include_electrical' => false,
            ],
            [
                'application_type_id' => 3, // Amendatory
                'complexity' => 'Complex',
                'applies_to' => '',
                'status' => 'for_zoning_assessment',
                'applicant_first_name' => 'Ricardo',
                'applicant_middle_name' => 'Bautista',
                'applicant_last_name' => 'Mendoza',
                'applicant_suffix' => '',
                'applicant_tin' => '333-444-555-000',
                'applicant_contact_no' => '09451234567',
                'applicant_email' => 'ricardo.mendoza@email.com',
                'enterprise_name' => 'Mendoza Industrial Corp.',
                'form_of_ownership_id' => 3,
                'project_title' => 'Warehouse Expansion',
                'scope_of_work_id' => 2,
                'scope_of_work_details' => 'Addition of 500 sqm warehouse wing',
                'lot_no' => '45', 'block_no' => '10', 'tct_no' => 'T-99999', 'tax_dec_no' => 'TD-2024-088',
                'land_classification_id' => 4,
                'no_of_storeys' => 1, 'no_of_units' => 2, 'total_floor_area' => 800.00, 'lot_area' => 1200.00,
                'building_cost' => 8000000, 'electrical_cost' => 500000, 'mechanical_cost' => 400000,
                'electronics_cost' => 200000, 'plumbing_cost' => 150000, 'other_equipment_cost' => 100000,
                'proposed_construction_date' => '2026-10-01', 'expected_completion_date' => '2027-10-01',
                'engineer_name' => 'Engr. Patricia Lim', 'engineer_prc_no' => 'CE-0098765',
                'engineer_prc_validity' => '2028-12-31', 'engineer_ptr_no' => 'PTR-2026-055',
                'engineer_ptr_date_issued' => '2026-03-01', 'engineer_ptr_issued_at' => 'San Fernando, La Union',
                'engineer_tin' => '888-999-000-000', 'engineer_address' => 'Sevilla, SFC La Union',
                'engineer_date_signed' => '2026-06-15',
                'owner_name' => 'Ricardo Bautista Mendoza', 'owner_address' => 'Biday, SFC La Union',
                'occupancy_sub_groups' => [28, 30],
                'include_electrical' => true,
                'total_connected_load' => 500.00, 'total_transformer_capacity' => 750.00, 'total_generator_capacity' => 300.00,
                'pee_name' => 'Engr. Luis Santos', 'pee_prc_no' => 'REE-0055667', 'pee_prc_validity' => '2028-06-30',
                'pee_date_signed' => '2026-06-15', 'pee_ptr_no' => 'PTR-2026-200',
                'pee_ptr_date_issued' => '2026-02-01', 'pee_ptr_issued_at' => 'San Fernando, La Union',
                'pee_address' => 'Pagdalagan, SFC La Union', 'pee_tin' => '777-888-999-000',
                'sew_profession' => 'ME', 'sew_name' => 'Engr. Luis Santos', 'sew_prc_no' => 'REE-0055667',
                'sew_prc_validity' => '2028-06-30', 'sew_date_signed' => '2026-06-15',
                'sew_ptr_no' => 'PTR-2026-200', 'sew_ptr_date_issued' => '2026-02-01',
                'sew_ptr_issued_at' => 'San Fernando, La Union', 'sew_address' => 'Pagdalagan, SFC La Union',
                'sew_tin' => '777-888-999-000',
            ],
            [
                'application_type_id' => 1, // New
                'complexity' => 'Simple',
                'applies_to' => '',
                'status' => 'draft',
                'applicant_first_name' => 'Carmen',
                'applicant_middle_name' => 'Lopez',
                'applicant_last_name' => 'Villanueva',
                'applicant_suffix' => '',
                'applicant_tin' => '444-555-666-111',
                'applicant_contact_no' => '09551234567',
                'applicant_email' => 'carmen.villanueva@email.com',
                'enterprise_name' => '',
                'form_of_ownership_id' => null,
                'project_title' => 'Residential Townhouse Unit',
                'scope_of_work_id' => 1,
                'scope_of_work_details' => '',
                'lot_no' => '7', 'block_no' => '3', 'tct_no' => 'T-55555', 'tax_dec_no' => 'TD-2025-012',
                'land_classification_id' => 1,
                'no_of_storeys' => 2, 'no_of_units' => 1, 'total_floor_area' => 120.00, 'lot_area' => 100.00,
                'building_cost' => 1800000, 'electrical_cost' => 100000, 'mechanical_cost' => 50000,
                'electronics_cost' => 30000, 'plumbing_cost' => 60000, 'other_equipment_cost' => 0,
                'proposed_construction_date' => '2026-11-01', 'expected_completion_date' => '2027-11-01',
                'engineer_name' => 'Engr. Roberto Cruz', 'engineer_prc_no' => 'CE-0012345',
                'engineer_prc_validity' => '2027-12-31', 'engineer_ptr_no' => 'PTR-2026-001',
                'engineer_ptr_date_issued' => '2026-01-15', 'engineer_ptr_issued_at' => 'San Fernando, La Union',
                'engineer_tin' => '987-654-321-000', 'engineer_address' => 'Poro, SFC La Union',
                'engineer_date_signed' => '2026-06-25',
                'owner_name' => 'Carmen Lopez Villanueva', 'owner_address' => 'Bangbangolan, SFC La Union',
                'occupancy_sub_groups' => [7],
                'include_electrical' => false,
            ],
        ];

        $existingBpCount = Application::whereYear('created_at', $year)->whereMonth('created_at', $month)->count();

        foreach ($bpRecords as $i => $data) {
            $counter = $existingBpCount + $i + 1;
            $occupancyGroups = $data['occupancy_sub_groups'];
            unset($data['occupancy_sub_groups']);

            $brgyIndex = $i % count($barangays);
            $data = array_merge($data, [
                'permit_type_id' => $bpPermitType->id,
                'app_year' => $year,
                'app_month' => $month,
                'app_counter' => $counter,
                'application_number' => sprintf('BP-%d-%02d-%05d', $year, $month, $counter),
                'source' => 'walk_in',
                'applicant_province_id' => $province->id,
                'applicant_city_id' => $city->id,
                'applicant_barangay_id' => $barangays[$brgyIndex],
                'building_barangay_id' => $barangays[($brgyIndex + 1) % count($barangays)],
                'building_street' => ['Rizal St.', 'Quezon Ave.', 'Mabini St.', 'Burgos St.', 'Luna St.'][$i],
                'entered_by' => 1,
                'submitted_at' => $data['status'] !== 'draft' ? $now : null,
                'total_estimated_cost' => ($data['building_cost'] ?? 0) + ($data['electrical_cost'] ?? 0) +
                    ($data['mechanical_cost'] ?? 0) + ($data['electronics_cost'] ?? 0) +
                    ($data['plumbing_cost'] ?? 0) + ($data['other_equipment_cost'] ?? 0),
            ]);

            $app = Application::create($data);

            foreach ($occupancyGroups as $subGroupId) {
                $subGroup = OccupancySubGroup::find($subGroupId);
                ApplicationOccupancyGroup::create([
                    'application_id' => $app->id,
                    'applicationable_type' => 'bp',
                    'applicationable_id' => $app->id,
                    'occupancy_group_id' => $subGroup->occupancy_group_id,
                    'occupancy_sub_group_id' => $subGroupId,
                ]);
            }
        }

        $opRecords = [
            [
                'application_type_id' => 7, // Full
                'status' => 'zoning_assessed',
                'applicant_first_name' => 'Antonio',
                'applicant_middle_name' => 'Garcia',
                'applicant_last_name' => 'Ramos',
                'applicant_suffix' => '',
                'applicant_tin' => '555-111-222-000',
                'applicant_contact_no' => '09171112233',
                'applicant_email' => 'antonio.ramos@email.com',
                'enterprise_name' => 'Ramos Hardware',
                'form_of_ownership_id' => 1,
                'project_title' => 'Commercial Space Occupancy',
                'bp_number' => 'BP-2025-03-00001', 'bp_issued_date' => '2025-03-15',
                'fsec_no' => 'FSEC-2025-001', 'fsec_issued_date' => '2025-04-01',
                'completion_date' => '2026-05-30',
                'no_of_storeys' => 2, 'no_of_units' => 4, 'total_floor_area' => 320.00, 'lot_area' => 250.00,
                'owner_name' => 'Antonio Garcia Ramos', 'owner_address' => 'Parian, SFC La Union',
                'occupancy_sub_groups' => [22, 25],
            ],
            [
                'application_type_id' => 8, // Partial
                'status' => 'draft',
                'applicant_first_name' => 'Rosario',
                'applicant_middle_name' => 'Aquino',
                'applicant_last_name' => 'Santos',
                'applicant_suffix' => '',
                'applicant_tin' => '666-222-333-000',
                'applicant_contact_no' => '09282223344',
                'applicant_email' => 'rosario.santos@email.com',
                'enterprise_name' => '',
                'form_of_ownership_id' => null,
                'project_title' => 'Residential Occupancy - Ground Floor',
                'bp_number' => '', 'bp_issued_date' => null,
                'fsec_no' => '', 'fsec_issued_date' => null,
                'completion_date' => '2026-06-15',
                'no_of_storeys' => 2, 'no_of_units' => 1, 'total_floor_area' => 90.00, 'lot_area' => 120.00,
                'owner_name' => 'Rosario Aquino Santos', 'owner_address' => 'Bacsil, SFC La Union',
                'occupancy_sub_groups' => [1],
            ],
            [
                'application_type_id' => 7, // Full
                'status' => 'zoning_assessed',
                'applicant_first_name' => 'Fernando',
                'applicant_middle_name' => 'Tan',
                'applicant_last_name' => 'Lim',
                'applicant_suffix' => '',
                'applicant_tin' => '777-333-444-000',
                'applicant_contact_no' => '09393334455',
                'applicant_email' => 'fernando.lim@email.com',
                'enterprise_name' => 'Lim Hotel & Resort',
                'form_of_ownership_id' => 3,
                'project_title' => 'Hotel Building Occupancy',
                'bp_number' => 'BP-2025-08-00003', 'bp_issued_date' => '2025-08-20',
                'fsec_no' => 'FSEC-2025-015', 'fsec_issued_date' => '2025-09-05',
                'completion_date' => '2026-04-20',
                'no_of_storeys' => 4, 'no_of_units' => 20, 'total_floor_area' => 1200.00, 'lot_area' => 800.00,
                'owner_name' => 'Fernando Tan Lim', 'owner_address' => 'Poro Point, SFC La Union',
                'occupancy_sub_groups' => [5, 6],
            ],
            [
                'application_type_id' => 8, // Partial
                'status' => 'draft',
                'applicant_first_name' => 'Gloria',
                'applicant_middle_name' => 'Dizon',
                'applicant_last_name' => 'Navarro',
                'applicant_suffix' => '',
                'applicant_tin' => '888-444-555-000',
                'applicant_contact_no' => '09454445566',
                'applicant_email' => 'gloria.navarro@email.com',
                'enterprise_name' => 'Navarro School of Learning',
                'form_of_ownership_id' => 1,
                'project_title' => 'School Building Partial Occupancy',
                'bp_number' => 'BP-2025-11-00007', 'bp_issued_date' => '2025-11-10',
                'fsec_no' => '', 'fsec_issued_date' => null,
                'completion_date' => '2026-06-01',
                'no_of_storeys' => 3, 'no_of_units' => 12, 'total_floor_area' => 600.00, 'lot_area' => 400.00,
                'owner_name' => 'Gloria Dizon Navarro', 'owner_address' => 'Catbangen, SFC La Union',
                'occupancy_sub_groups' => [12, 13],
            ],
            [
                'application_type_id' => 7, // Full
                'status' => 'draft',
                'applicant_first_name' => 'Roberto',
                'applicant_middle_name' => 'Pascual',
                'applicant_last_name' => 'Aguilar',
                'applicant_suffix' => 'Sr.',
                'applicant_tin' => '999-555-666-000',
                'applicant_contact_no' => '09555556677',
                'applicant_email' => 'roberto.aguilar@email.com',
                'enterprise_name' => 'Aguilar Medical Clinic',
                'form_of_ownership_id' => 1,
                'project_title' => 'Medical Clinic Occupancy',
                'bp_number' => 'BP-2026-01-00002', 'bp_issued_date' => '2026-01-25',
                'fsec_no' => 'FSEC-2026-003', 'fsec_issued_date' => '2026-02-10',
                'completion_date' => '2026-05-15',
                'no_of_storeys' => 2, 'no_of_units' => 3, 'total_floor_area' => 250.00, 'lot_area' => 180.00,
                'owner_name' => 'Roberto Pascual Aguilar Sr.', 'owner_address' => 'Sevilla, SFC La Union',
                'occupancy_sub_groups' => [18],
            ],
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $existingOpCount = OccupancyApplication::whereYear('created_at', $year)->whereMonth('created_at', $month)->count();

        foreach ($opRecords as $i => $data) {
            $counter = $existingOpCount + $i + 1;
            $occupancyGroups = $data['occupancy_sub_groups'];
            unset($data['occupancy_sub_groups']);

            $brgyIndex = ($i + 5) % count($barangays);
            $data = array_merge($data, [
                'app_year' => $year,
                'app_month' => $month,
                'app_counter' => $counter,
                'application_number' => sprintf('OP-%d-%02d-%05d', $year, $month, $counter),
                'source' => 'walk_in',
                'applicant_province_id' => $province->id,
                'applicant_city_id' => $city->id,
                'applicant_barangay_id' => $barangays[$brgyIndex],
                'building_street' => ['National Highway', 'Quezon Ave.', 'Gov. Luna St.', 'Zandueta St.', 'P. Burgos St.'][$i],
                'building_barangay_id' => $barangays[($brgyIndex + 1) % count($barangays)],
                'entered_by' => 1,
                'submitted_at' => $data['status'] !== 'draft' ? $now : null,
            ]);

            $app = OccupancyApplication::create($data);

            foreach ($occupancyGroups as $subGroupId) {
                $subGroup = OccupancySubGroup::find($subGroupId);
                ApplicationOccupancyGroup::create([
                    'application_id' => 0,
                    'applicationable_type' => 'op',
                    'applicationable_id' => $app->id,
                    'occupancy_group_id' => $subGroup->occupancy_group_id,
                    'occupancy_sub_group_id' => $subGroupId,
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Created 5 BP and 5 OP test applications.');
    }
}
