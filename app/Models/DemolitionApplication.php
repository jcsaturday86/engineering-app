<?php

namespace App\Models;

use App\Concerns\HasPermitApplicationBehavior;
use App\Contracts\PermitApplicationContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class DemolitionApplication extends Model implements PermitApplicationContract
{
    use HasPermitApplicationBehavior, LogsActivity, SoftDeletes;

    protected $table = 'demolition_applications';

    protected $fillable = [
        'application_type_id',
        'app_year',
        'app_month',
        'app_counter',
        'application_number',
        'status',
        'source',
        // Applicant
        'applicant_first_name',
        'applicant_middle_name',
        'applicant_last_name',
        'applicant_tin',
        'applicant_telephone',
        // Enterprise
        'owned_by_enterprise',
        'enterprise_name',
        'form_of_ownership_id',
        // Address
        'applicant_province_id',
        'applicant_city_id',
        'applicant_barangay_id',
        'applicant_street',
        'applicant_zip_code',
        'applicant_ctc_no',
        'applicant_ctc_date_issued',
        'applicant_ctc_place_issued',
        // Location of Demolition Works
        'lot_no',
        'block_no',
        'tct_no',
        'tax_dec_no',
        'demolition_street',
        'demolition_barangay_id',
        // Scope of Work
        'scope_of_work',
        'scope_of_work_detail',
        // Full-time Inspector / Supervisor of Demolition Works
        'inspector_name',
        'inspector_address',
        'inspector_telephone',
        'inspector_prc_no',
        'inspector_prc_validity',
        'inspector_ptr_no',
        'inspector_ptr_date_issued',
        'inspector_ptr_issued_at',
        'inspector_tin',
        // Lot Owner Consent
        'owner_name',
        'owner_ctc_no',
        'owner_ctc_date_issued',
        'owner_ctc_place_issued',
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
            'owned_by_enterprise' => 'boolean',
            'applicant_ctc_date_issued' => 'date',
            'inspector_prc_validity' => 'date',
            'inspector_ptr_date_issued' => 'date',
            'owner_ctc_date_issued' => 'date',
            'submitted_at' => 'datetime',
            'assessed_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'issued_date' => 'date',
        ];
    }

    /**
     * Overrides HasPermitApplicationBehavior::buildingBarangay(), which targets
     * building_barangay_id — a column that doesn't exist on this table. Demolition
     * applications use demolition_barangay_id instead, but generic code (e.g.
     * PermitController::print()) eager-loads the relation via this same method name.
     */
    public function buildingBarangay(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'demolition_barangay_id');
    }

    public function demolitionBarangay(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->buildingBarangay();
    }

    public function getPermitTypeCode(): string
    {
        return 'DP';
    }
}
