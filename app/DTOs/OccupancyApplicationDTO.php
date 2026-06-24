<?php

namespace App\DTOs;

use Illuminate\Http\Request;

readonly class OccupancyApplicationDTO
{
    public function __construct(
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
        public ?string $applicant_date_signed = null,

        // Enterprise
        public ?string $enterprise_name = null,
        public ?int $form_of_ownership_id = null,

        // Address
        public ?int $applicant_province_id = null,
        public ?int $applicant_city_id = null,
        public ?int $applicant_barangay_id = null,
        public ?string $applicant_street = null,
        public ?string $applicant_zip_code = null,

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

        // Owner
        public ?string $owner_name = null,
        public ?string $owner_address = null,
        public ?string $owner_govt_id = null,
        public ?string $owner_id_date_issued = null,
        public ?string $owner_id_place_issued = null,
        public ?string $owner_date_signed = null,

        // OP-specific
        public ?string $bp_number = null,
        public ?string $bp_issued_date = null,
        public ?string $fsec_no = null,
        public ?string $fsec_issued_date = null,
        public ?string $completion_date = null,
        public ?string $applies_for = null,

        // Misc
        public ?string $remarks = null,
        public string $source = 'walk_in',
        public ?string $area_number = null,
    ) {}

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
            applicant_date_signed: $request->input('applicant_date_signed'),
            enterprise_name: $request->input('enterprise_name'),
            form_of_ownership_id: $request->filled('form_of_ownership_id') ? (int) $request->input('form_of_ownership_id') : null,
            applicant_province_id: $request->filled('applicant_province_id') ? (int) $request->input('applicant_province_id') : null,
            applicant_city_id: $request->filled('applicant_city_id') ? (int) $request->input('applicant_city_id') : null,
            applicant_barangay_id: $request->filled('applicant_barangay_id') ? (int) $request->input('applicant_barangay_id') : null,
            applicant_street: $request->input('applicant_street'),
            applicant_zip_code: $request->input('applicant_zip_code'),
            lot_no: $request->input('lot_no'),
            block_no: $request->input('block_no'),
            tct_no: $request->input('tct_no'),
            tax_dec_no: $request->input('tax_dec_no'),
            land_classification_id: $request->filled('land_classification_id') ? (int) $request->input('land_classification_id') : null,
            building_street: $request->input('building_street'),
            building_barangay_id: $request->filled('building_barangay_id') ? (int) $request->input('building_barangay_id') : null,
            no_of_storeys: $request->filled('no_of_storeys') ? (int) $request->input('no_of_storeys') : null,
            no_of_units: $request->filled('no_of_units') ? (int) $request->input('no_of_units') : null,
            total_floor_area: $request->filled('total_floor_area') ? (float) $request->input('total_floor_area') : null,
            lot_area: $request->filled('lot_area') ? (float) $request->input('lot_area') : null,
            owner_name: $request->input('owner_name'),
            owner_address: $request->input('owner_address'),
            owner_govt_id: $request->input('owner_govt_id'),
            owner_id_date_issued: $request->input('owner_id_date_issued'),
            owner_id_place_issued: $request->input('owner_id_place_issued'),
            owner_date_signed: $request->input('owner_date_signed'),
            bp_number: $request->input('bp_number'),
            bp_issued_date: $request->input('bp_issued_date'),
            fsec_no: $request->input('fsec_no'),
            fsec_issued_date: $request->input('fsec_issued_date'),
            completion_date: $request->input('completion_date'),
            applies_for: $request->input('applies_for'),
            remarks: $request->input('remarks'),
            source: $request->input('source', 'walk_in'),
            area_number: $request->input('area_number'),
        );
    }

    public function toArray(): array
    {
        return array_filter(
            get_object_vars($this),
            fn ($value) => $value !== null
        );
    }
}
