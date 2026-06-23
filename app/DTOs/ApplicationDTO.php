<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class ApplicationDTO
{
    public function __construct(
        // Application type
        public int $application_type_id,

        // Applicant
        public string $applicant_first_name,
        public string $applicant_last_name,
        public ?string $applicant_middle_name = null,
        public ?string $applicant_suffix = null,
        public ?string $applicant_tin = null,
        public ?string $applicant_contact_no = null,
        public ?string $applicant_email = null,
        public ?string $applicant_govt_id = null,
        public ?string $applicant_id_date_issued = null,
        public ?string $applicant_id_place_issued = null,

        // Enterprise / Ownership
        public ?string $enterprise_name = null,
        public ?int $form_of_ownership_id = null,

        // Applicant address
        public ?int $applicant_province_id = null,
        public ?int $applicant_city_id = null,
        public ?int $applicant_barangay_id = null,
        public ?string $applicant_street = null,
        public ?string $applicant_zip_code = null,

        // Project details
        public ?string $project_title = null,
        public ?int $scope_of_work_id = null,
        public ?string $scope_of_work_details = null,

        // Building location
        public ?string $lot_no = null,
        public ?string $block_no = null,
        public ?string $tct_no = null,
        public ?string $tax_dec_no = null,
        public ?int $land_classification_id = null,
        public ?string $building_street = null,
        public ?int $building_barangay_id = null,

        // Building specs
        public ?int $no_of_storeys = null,
        public ?int $no_of_units = null,
        public ?float $total_floor_area = null,
        public ?float $lot_area = null,

        // Cost estimates
        public float $building_cost = 0,
        public float $electrical_cost = 0,
        public float $mechanical_cost = 0,
        public float $electronics_cost = 0,
        public float $plumbing_cost = 0,
        public float $other_equipment_cost = 0,
        public float $total_estimated_cost = 0,

        // Timeline
        public ?string $proposed_construction_date = null,
        public ?string $expected_completion_date = null,
        public ?string $remarks = null,

        // For occupancy permits
        public ?string $bp_number = null,
        public ?string $bp_issued_date = null,
        public ?string $completion_date = null,

        // Engineer / Architect info
        public ?string $engineer_name = null,
        public ?string $engineer_prc_no = null,
        public ?string $engineer_prc_validity = null,
        public ?string $engineer_ptr_no = null,
        public ?string $engineer_ptr_date_issued = null,
        public ?string $engineer_ptr_issued_at = null,
        public ?string $engineer_tin = null,
        public ?string $engineer_address = null,
        public ?string $engineer_date_signed = null,

        // Owner info
        public ?string $owner_name = null,
        public ?string $owner_address = null,
        public ?string $owner_govt_id = null,
        public ?string $owner_id_date_issued = null,
        public ?string $owner_date_signed = null,

        // Electrical permit data
        public bool $include_electrical = false,
        public ?float $total_connected_load = null,
        public ?float $total_transformer_capacity = null,
        public ?float $total_generator_capacity = null,

        // Source
        public string $source = 'walk_in',

        // Area number
        public ?string $area_number = null,
    ) {}

    /**
     * Create a DTO from a form request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            application_type_id: (int) $request->input('application_type_id'),
            applicant_first_name: $request->input('applicant_first_name'),
            applicant_last_name: $request->input('applicant_last_name'),
            applicant_middle_name: $request->input('applicant_middle_name'),
            applicant_suffix: $request->input('applicant_suffix'),
            applicant_tin: $request->input('applicant_tin'),
            applicant_contact_no: $request->input('applicant_contact_no'),
            applicant_email: $request->input('applicant_email'),
            applicant_govt_id: $request->input('applicant_govt_id'),
            applicant_id_date_issued: $request->input('applicant_id_date_issued'),
            applicant_id_place_issued: $request->input('applicant_id_place_issued'),
            enterprise_name: $request->input('enterprise_name'),
            form_of_ownership_id: $request->filled('form_of_ownership_id')
                ? (int) $request->input('form_of_ownership_id')
                : null,
            applicant_province_id: $request->filled('applicant_province_id')
                ? (int) $request->input('applicant_province_id')
                : null,
            applicant_city_id: $request->filled('applicant_city_id')
                ? (int) $request->input('applicant_city_id')
                : null,
            applicant_barangay_id: $request->filled('applicant_barangay_id')
                ? (int) $request->input('applicant_barangay_id')
                : null,
            applicant_street: $request->input('applicant_street'),
            applicant_zip_code: $request->input('applicant_zip_code'),
            project_title: $request->input('project_title'),
            scope_of_work_id: $request->filled('scope_of_work_id')
                ? (int) $request->input('scope_of_work_id')
                : null,
            scope_of_work_details: $request->input('scope_of_work_details'),
            lot_no: $request->input('lot_no'),
            block_no: $request->input('block_no'),
            tct_no: $request->input('tct_no'),
            tax_dec_no: $request->input('tax_dec_no'),
            land_classification_id: $request->filled('land_classification_id')
                ? (int) $request->input('land_classification_id')
                : null,
            building_street: $request->input('building_street'),
            building_barangay_id: $request->filled('building_barangay_id')
                ? (int) $request->input('building_barangay_id')
                : null,
            no_of_storeys: $request->filled('no_of_storeys')
                ? (int) $request->input('no_of_storeys')
                : null,
            no_of_units: $request->filled('no_of_units')
                ? (int) $request->input('no_of_units')
                : null,
            total_floor_area: $request->filled('total_floor_area')
                ? (float) $request->input('total_floor_area')
                : null,
            lot_area: $request->filled('lot_area')
                ? (float) $request->input('lot_area')
                : null,
            building_cost: (float) $request->input('building_cost', 0),
            electrical_cost: (float) $request->input('electrical_cost', 0),
            mechanical_cost: (float) $request->input('mechanical_cost', 0),
            electronics_cost: (float) $request->input('electronics_cost', 0),
            plumbing_cost: (float) $request->input('plumbing_cost', 0),
            other_equipment_cost: (float) $request->input('other_equipment_cost', 0),
            total_estimated_cost: (float) $request->input('total_estimated_cost', 0),
            proposed_construction_date: $request->input('proposed_construction_date'),
            expected_completion_date: $request->input('expected_completion_date'),
            remarks: $request->input('remarks'),
            bp_number: $request->input('bp_number'),
            bp_issued_date: $request->input('bp_issued_date'),
            completion_date: $request->input('completion_date'),
            engineer_name: $request->input('engineer_name'),
            engineer_prc_no: $request->input('engineer_prc_no'),
            engineer_prc_validity: $request->input('engineer_prc_validity'),
            engineer_ptr_no: $request->input('engineer_ptr_no'),
            engineer_ptr_date_issued: $request->input('engineer_ptr_date_issued'),
            engineer_ptr_issued_at: $request->input('engineer_ptr_issued_at'),
            engineer_tin: $request->input('engineer_tin'),
            engineer_address: $request->input('engineer_address'),
            engineer_date_signed: $request->input('engineer_date_signed'),
            owner_name: $request->input('owner_name'),
            owner_address: $request->input('owner_address'),
            owner_govt_id: $request->input('owner_govt_id'),
            owner_id_date_issued: $request->input('owner_id_date_issued'),
            owner_date_signed: $request->input('owner_date_signed'),
            include_electrical: (bool) $request->input('include_electrical', false),
            total_connected_load: $request->filled('total_connected_load')
                ? (float) $request->input('total_connected_load')
                : null,
            total_transformer_capacity: $request->filled('total_transformer_capacity')
                ? (float) $request->input('total_transformer_capacity')
                : null,
            total_generator_capacity: $request->filled('total_generator_capacity')
                ? (float) $request->input('total_generator_capacity')
                : null,
            source: $request->input('source', 'walk_in'),
            area_number: $request->input('area_number'),
        );
    }

    /**
     * Convert the DTO to an array suitable for model creation/update.
     */
    public function toArray(): array
    {
        return array_filter(
            get_object_vars($this),
            fn ($value) => $value !== null
        );
    }
}
