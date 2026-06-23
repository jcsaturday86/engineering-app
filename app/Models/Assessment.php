<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Assessment extends Model
{
    use LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'assessment_type',
        'filing_fee',
        'processing_fee',
        'total_amount',
        'status',
        'assessed_by',
        'finalized_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filing_fee' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'finalized_at' => 'datetime',
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

    /**
     * Application this assessment belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * User who performed the assessment.
     */
    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * Line items in this assessment.
     */
    public function assessmentItems(): HasMany
    {
        return $this->hasMany(AssessmentItem::class);
    }
}
