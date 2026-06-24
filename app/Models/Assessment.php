<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Assessment extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'application_id',
        'applicationable_type',
        'applicationable_id',
        'assessment_type',
        'filing_fee',
        'processing_fee',
        'total_amount',
        'status',
        'assessed_by',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'filing_fee' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'finalized_at' => 'datetime',
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

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function assessmentItems(): HasMany
    {
        return $this->hasMany(AssessmentItem::class);
    }
}
