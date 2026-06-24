<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApplicationOccupancyGroup extends Model
{
    protected $fillable = [
        'application_id',
        'applicationable_type',
        'applicationable_id',
        'occupancy_group_id',
        'occupancy_sub_group_id',
        'others_text',
    ];

    public function applicationable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getApplicationAttribute()
    {
        return $this->applicationable;
    }

    public function occupancyGroup(): BelongsTo
    {
        return $this->belongsTo(OccupancyGroup::class);
    }

    public function occupancySubGroup(): BelongsTo
    {
        return $this->belongsTo(OccupancySubGroup::class);
    }
}
