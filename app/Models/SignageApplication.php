<?php

namespace App\Models;

use App\Concerns\HasPermitApplicationBehavior;
use App\Contracts\PermitApplicationContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class SignageApplication extends Model implements PermitApplicationContract
{
    use HasPermitApplicationBehavior, LogsActivity, SoftDeletes;

    protected $table = 'signage_applications';

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
        // Address
        'applicant_province_id',
        'applicant_city_id',
        'applicant_barangay_id',
        'applicant_street',
        'applicant_zip_code',
        // Scope of Work
        'install',
        'install_detail',
        'attach',
        'attach_detail',
        'paint',
        'paint_detail',
        'wordings',
        'premises_of',
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
            'install' => 'boolean',
            'attach' => 'boolean',
            'paint' => 'boolean',
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
     * building_barangay_id — a column that doesn't exist on this table. Signage
     * applications have no separate site-location address beyond the applicant's
     * own, but generic code (e.g. PermitController::print()) eager-loads the
     * relation via this same method name.
     */
    public function buildingBarangay(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->applicantBarangay();
    }

    public function getPermitTypeCode(): string
    {
        return 'SGP';
    }
}
