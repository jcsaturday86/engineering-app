<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApplicationRequirement extends Model
{
    protected $fillable = [
        'application_id',
        'applicationable_type',
        'applicationable_id',
        'document_requirement_id',
        'requirement_name',
        'file_path',
        'original_filename',
        'status',
        'reviewer_remarks',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function applicationable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getApplicationAttribute()
    {
        return $this->applicationable;
    }

    /**
     * The configured requirement this upload satisfies. Null for legacy or
     * free-form uploads made before the checklist existed.
     */
    public function documentRequirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
