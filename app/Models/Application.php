<?php

namespace App\Models;

use App\Concerns\HasPermitApplicationBehavior;
use App\Contracts\PermitApplicationContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Application extends Model implements PermitApplicationContract
{
    use HasPermitApplicationBehavior, LogsActivity, SoftDeletes;

    protected $fillable = [
        'permit_type_id',
        'application_type_id',
        'complexity',
        'applies_to',
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
        'scope_of_work_id',
        'scope_of_work_details',
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
        // Cost estimates
        'building_cost',
        'electrical_cost',
        'mechanical_cost',
        'electronics_cost',
        'plumbing_cost',
        'other_equipment_cost',
        'equipment_cost_1',
        'equipment_cost_2',
        'equipment_cost_3',
        'equipment_cost_4',
        'total_estimated_cost',
        // Timeline
        'proposed_construction_date',
        'expected_completion_date',
        'remarks',
        // Engineer/Architect
        'engineer_name',
        'engineer_prc_no',
        'engineer_prc_validity',
        'engineer_ptr_no',
        'engineer_ptr_date_issued',
        'engineer_ptr_issued_at',
        'engineer_tin',
        'engineer_address',
        'engineer_date_signed',
        // Owner
        'owner_name',
        'owner_address',
        'owner_govt_id',
        'owner_id_date_issued',
        'owner_id_place_issued',
        'owner_date_signed',
        // Electrical
        'include_electrical',
        'total_connected_load',
        'total_transformer_capacity',
        'total_generator_capacity',
        // PEE
        'pee_name', 'pee_prc_no', 'pee_prc_validity', 'pee_date_signed',
        'pee_ptr_no', 'pee_ptr_date_issued', 'pee_ptr_issued_at', 'pee_address', 'pee_tin',
        // SEW
        'sew_profession', 'sew_name', 'sew_prc_no', 'sew_prc_validity', 'sew_date_signed',
        'sew_ptr_no', 'sew_ptr_date_issued', 'sew_ptr_issued_at', 'sew_address', 'sew_tin',
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
            'building_cost' => 'decimal:2',
            'electrical_cost' => 'decimal:2',
            'mechanical_cost' => 'decimal:2',
            'electronics_cost' => 'decimal:2',
            'plumbing_cost' => 'decimal:2',
            'other_equipment_cost' => 'decimal:2',
            'total_estimated_cost' => 'decimal:2',
            'equipment_cost_1' => 'decimal:2',
            'equipment_cost_2' => 'decimal:2',
            'equipment_cost_3' => 'decimal:2',
            'equipment_cost_4' => 'decimal:2',
            'proposed_construction_date' => 'date',
            'expected_completion_date' => 'date',
            'engineer_prc_validity' => 'date',
            'engineer_ptr_date_issued' => 'date',
            'engineer_date_signed' => 'date',
            'owner_id_date_issued' => 'date',
            'owner_date_signed' => 'date',
            'include_electrical' => 'boolean',
            'total_connected_load' => 'decimal:4',
            'total_transformer_capacity' => 'decimal:4',
            'total_generator_capacity' => 'decimal:4',
            'pee_prc_validity' => 'date',
            'pee_date_signed' => 'date',
            'pee_ptr_date_issued' => 'date',
            'sew_prc_validity' => 'date',
            'sew_date_signed' => 'date',
            'sew_ptr_date_issued' => 'date',
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
        return 'BP';
    }

    public function getTotalEstimatedCostAttribute(): float
    {
        return (float) $this->building_cost
            + (float) $this->electrical_cost
            + (float) $this->mechanical_cost
            + (float) $this->electronics_cost
            + (float) $this->plumbing_cost
            + (float) $this->other_equipment_cost;
    }

    // ---------------------------------------------------------------
    // BP-specific Relationships
    // ---------------------------------------------------------------

    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class);
    }

    public function scopeOfWork(): BelongsTo
    {
        return $this->belongsTo(ScopeOfWork::class);
    }

    public function zoningAssessment(): HasOne
    {
        return $this->hasOne(ZoningAssessment::class);
    }
}
