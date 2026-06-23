<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Application extends Model
{
    use LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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
        'enterprise_name',
        'form_of_ownership_id',
        'applicant_province_id',
        'applicant_city_id',
        'applicant_barangay_id',
        'applicant_street',
        'applicant_zip_code',
        'project_title',
        'scope_of_work_id',
        'scope_of_work_details',
        'lot_no',
        'block_no',
        'tct_no',
        'tax_dec_no',
        'land_classification_id',
        'building_street',
        'building_barangay_id',
        'no_of_storeys',
        'no_of_units',
        'occupancy_classified',
        'total_floor_area',
        'lot_area',
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
        'proposed_construction_date',
        'expected_completion_date',
        'remarks',
        'bp_number',
        'bp_issued_date',
        'fsec_no',
        'fsec_issued_date',
        'applies_for',
        'completion_date',
        'engineer_name',
        'engineer_prc_no',
        'engineer_prc_validity',
        'engineer_ptr_no',
        'engineer_ptr_date_issued',
        'engineer_ptr_issued_at',
        'engineer_tin',
        'engineer_address',
        'engineer_date_signed',
        'owner_name',
        'owner_address',
        'owner_govt_id',
        'owner_id_date_issued',
        'owner_id_place_issued',
        'owner_date_signed',
        'applicant_date_signed',
        'include_electrical',
        'total_connected_load',
        'total_transformer_capacity',
        'total_generator_capacity',
        'pee_name', 'pee_prc_no', 'pee_prc_validity', 'pee_date_signed',
        'pee_ptr_no', 'pee_ptr_date_issued', 'pee_ptr_issued_at', 'pee_address', 'pee_tin',
        'sew_profession', 'sew_name', 'sew_prc_no', 'sew_prc_validity', 'sew_date_signed',
        'sew_ptr_no', 'sew_ptr_date_issued', 'sew_ptr_issued_at', 'sew_address', 'sew_tin',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'app_year' => 'integer',
            'app_month' => 'integer',
            'app_counter' => 'integer',
            'applicant_id_date_issued' => 'date',
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
            'proposed_construction_date' => 'date',
            'expected_completion_date' => 'date',
            'bp_issued_date' => 'date',
            'completion_date' => 'date',
            'engineer_prc_validity' => 'date',
            'engineer_ptr_date_issued' => 'date',
            'engineer_date_signed' => 'date',
            'owner_id_date_issued' => 'date',
            'owner_date_signed' => 'date',
            'include_electrical' => 'boolean',
            'total_connected_load' => 'decimal:4',
            'total_transformer_capacity' => 'decimal:4',
            'total_generator_capacity' => 'decimal:4',
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
     * Get activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    // ---------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------

    /**
     * Get formatted application number.
     */
    public function getApplicationNumberFormattedAttribute(): string
    {
        return sprintf(
            '%04d-%02d-%05d',
            $this->app_year,
            $this->app_month,
            $this->app_counter,
        );
    }

    /**
     * Get applicant's full name.
     */
    public function getApplicantFullNameAttribute(): string
    {
        return collect([
            $this->applicant_first_name,
            $this->applicant_middle_name,
            $this->applicant_last_name,
            $this->applicant_suffix,
        ])->filter()->implode(' ');
    }

    /**
     * Get total estimated cost (sum of all cost fields).
     */
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
    // Relationships
    // ---------------------------------------------------------------

    /**
     * Permit type for this application.
     */
    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class);
    }

    /**
     * Application type.
     */
    public function applicationType(): BelongsTo
    {
        return $this->belongsTo(ApplicationType::class);
    }

    /**
     * Scope of work.
     */
    public function scopeOfWork(): BelongsTo
    {
        return $this->belongsTo(ScopeOfWork::class);
    }

    /**
     * Form of ownership.
     */
    public function formOfOwnership(): BelongsTo
    {
        return $this->belongsTo(FormOfOwnership::class);
    }

    /**
     * Land classification.
     */
    public function landClassification(): BelongsTo
    {
        return $this->belongsTo(LandClassification::class);
    }

    /**
     * Applicant province.
     */
    public function applicantProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'applicant_province_id');
    }

    /**
     * Applicant city.
     */
    public function applicantCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'applicant_city_id');
    }

    /**
     * Applicant barangay.
     */
    public function applicantBarangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'applicant_barangay_id');
    }

    /**
     * Building location barangay.
     */
    public function buildingBarangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'building_barangay_id');
    }

    /**
     * User who entered the application.
     */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    /**
     * User who assessed the application.
     */
    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * User who approved the application.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Client user (for online applications).
     */
    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    /**
     * Assessments for this application.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Zoning assessment for this application.
     */
    public function zoningAssessment(): HasOne
    {
        return $this->hasOne(ZoningAssessment::class);
    }

    /**
     * Occupancy groups for this application.
     */
    public function applicationOccupancyGroups(): HasMany
    {
        return $this->hasMany(ApplicationOccupancyGroup::class);
    }

    /**
     * Requirements for this application.
     */
    public function applicationRequirements(): HasMany
    {
        return $this->hasMany(ApplicationRequirement::class);
    }

    /**
     * Billings for this application.
     */
    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class);
    }

    /**
     * Collections for this application.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    /**
     * Permits for this application.
     */
    public function permits(): HasMany
    {
        return $this->hasMany(Permit::class);
    }

    /**
     * Documents for this application.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
