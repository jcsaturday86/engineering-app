<?php

namespace App\Concerns;

use App\Models\ApplicationOccupancyGroup;
use App\Models\ApplicationRequirement;
use App\Models\ApplicationType;
use App\Models\Assessment;
use App\Models\Barangay;
use App\Models\Billing;
use App\Models\City;
use App\Models\Collection;
use App\Models\Document;
use App\Models\FormOfOwnership;
use App\Models\LandClassification;
use App\Models\Permit;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;

trait HasPermitApplicationBehavior
{
    public function getApplicationNumberFormattedAttribute(): string
    {
        return sprintf(
            '%04d-%02d-%05d',
            $this->app_year,
            $this->app_month,
            $this->app_counter,
        );
    }

    public function getApplicantFullNameAttribute(): string
    {
        return collect([
            $this->applicant_first_name,
            $this->applicant_middle_name,
            $this->applicant_last_name,
            $this->applicant_suffix,
        ])->filter()->implode(' ');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    // ---------------------------------------------------------------
    // Shared BelongsTo Relationships
    // ---------------------------------------------------------------

    public function applicationType(): BelongsTo
    {
        return $this->belongsTo(ApplicationType::class);
    }

    public function formOfOwnership(): BelongsTo
    {
        return $this->belongsTo(FormOfOwnership::class);
    }

    public function landClassification(): BelongsTo
    {
        return $this->belongsTo(LandClassification::class);
    }

    public function applicantProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'applicant_province_id');
    }

    public function applicantCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'applicant_city_id');
    }

    public function applicantBarangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'applicant_barangay_id');
    }

    public function buildingBarangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'building_barangay_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function assessedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function clientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    // ---------------------------------------------------------------
    // Polymorphic Relationships
    // ---------------------------------------------------------------

    public function assessments(): MorphMany
    {
        return $this->morphMany(Assessment::class, 'applicationable');
    }

    public function billings(): MorphMany
    {
        return $this->morphMany(Billing::class, 'applicationable');
    }

    public function collections(): MorphMany
    {
        return $this->morphMany(Collection::class, 'applicationable');
    }

    public function permits(): MorphMany
    {
        return $this->morphMany(Permit::class, 'applicationable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'applicationable');
    }

    public function applicationOccupancyGroups(): MorphMany
    {
        return $this->morphMany(ApplicationOccupancyGroup::class, 'applicationable');
    }

    public function applicationRequirements(): MorphMany
    {
        return $this->morphMany(ApplicationRequirement::class, 'applicationable');
    }
}
