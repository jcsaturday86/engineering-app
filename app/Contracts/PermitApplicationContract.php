<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface PermitApplicationContract
{
    public function getPermitTypeCode(): string;

    public function getApplicationNumberFormattedAttribute(): string;

    public function getApplicantFullNameAttribute(): string;

    public function applicationType(): BelongsTo;

    public function formOfOwnership(): BelongsTo;

    public function landClassification(): BelongsTo;

    public function applicantProvince(): BelongsTo;

    public function applicantCity(): BelongsTo;

    public function applicantBarangay(): BelongsTo;

    public function buildingBarangay(): BelongsTo;

    public function enteredBy(): BelongsTo;

    public function assessedByUser(): BelongsTo;

    public function approvedBy(): BelongsTo;

    public function clientUser(): BelongsTo;

    public function assessments(): MorphMany;

    public function billings(): MorphMany;

    public function collections(): MorphMany;

    public function permits(): MorphMany;

    public function documents(): MorphMany;

    public function applicationOccupancyGroups(): MorphMany;

    public function applicationRequirements(): MorphMany;
}
