<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    protected $fillable = [
        'application_id',
        'applicationable_type',
        'applicationable_id',
        'document_type',
        'title',
        'file_path',
        'counter',
        'document_date',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'counter' => 'integer',
            'document_date' => 'date',
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

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
