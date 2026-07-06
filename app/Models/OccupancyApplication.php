<?php

namespace App\Models;

use App\Concerns\HasPermitApplicationBehavior;
use App\Contracts\PermitApplicationContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class OccupancyApplication extends Model implements PermitApplicationContract
{
    use HasPermitApplicationBehavior, LogsActivity, SoftDeletes;

    protected $table = 'occupancy_applications';

    protected $fillable = [
        'application_type_id',
        'app_year',
        'app_month',
        'app_counter',
        'application_number',
        'area_number',
        'status',
        'source',
        // Applicant
        'applicant_first_name',
        'applicant_middle_name',
        'applicant_last_name',
        'applicant_suffix',
        'applicant_tin',
        'applicant_contact_no',
        'applicant_email',
        'applicant_govt_id',
        'applicant_id_date_issued',
        'applicant_id_place_issued',
        'applicant_date_signed',
        // Enterprise
        'enterprise_name',
        'form_of_ownership_id',
        // Address
        'applicant_province_id',
        'applicant_city_id',
        'applicant_barangay_id',
        'applicant_street',
        'applicant_zip_code',
        // Project
        'project_title',
        // Building location
        'lot_no',
        'block_no',
        'tct_no',
        'tax_dec_no',
        'land_classification_id',
        'building_street',
        'building_barangay_id',
        // Building specs
        'no_of_storeys',
        'no_of_units',
        'occupancy_classified',
        'total_floor_area',
        'lot_area',
        // Owner
        'owner_name',
        'owner_address',
        'owner_govt_id',
        'owner_id_date_issued',
        'owner_id_place_issued',
        'owner_date_signed',
        // OP-specific
        'bp_number',
        'bp_issued_date',
        'fsec_no',
        'fsec_issued_date',
        'fsic_no',
        'applies_for',
        'completion_date',
        // Misc
        'remarks',
        // Processing
        'entered_by',
        'assessed_by',
        'approved_by',
        'submitted_at',
        'assessed_at',
        'approved_at',
        'paid_at',
        'released_at',
        'cancelled_at',
        'cancellation_reason',
        'client_user_id',
        'issued_date',
    ];

    protected function casts(): array
    {
        return [
            'app_year' => 'integer',
            'app_month' => 'integer',
            'app_counter' => 'integer',
            'applicant_id_date_issued' => 'date',
            'applicant_date_signed' => 'date',
            'no_of_storeys' => 'integer',
            'no_of_units' => 'integer',
            'total_floor_area' => 'decimal:2',
            'lot_area' => 'decimal:2',
            'bp_issued_date' => 'date',
            'fsec_issued_date' => 'date',
            'completion_date' => 'date',
            'owner_id_date_issued' => 'date',
            'owner_date_signed' => 'date',
            'submitted_at' => 'datetime',
            'assessed_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'issued_date' => 'date',
        ];
    }

    public function getPermitTypeCode(): string
    {
        return 'OP';
    }
}
