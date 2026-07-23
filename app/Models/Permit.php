<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Permit extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'application_id',
        'applicationable_type',
        'applicationable_id',
        'permit_type_id',
        'permit_year',
        'permit_month',
        'permit_counter',
        'permit_number',
        'verification_token',
        'issued_date',
        'processed_by',
        'approved_by',
        'status',
        'revoke_reason',
        'building_official_name',
        'building_official_title',
        'building_official_designation',
        'building_official_license_no',
        'signatories_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'permit_year' => 'integer',
            'permit_month' => 'integer',
            'permit_counter' => 'integer',
            'issued_date' => 'date',
            'signatories_snapshot' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function applicationable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getApplicationAttribute()
    {
        return $this->applicationable;
    }

    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
