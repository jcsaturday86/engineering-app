<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationOccupancyGroup extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'occupancy_group_id',
        'occupancy_sub_group_id',
        'others_text',
    ];

    /**
     * Application this record belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Occupancy group.
     */
    public function occupancyGroup(): BelongsTo
    {
        return $this->belongsTo(OccupancyGroup::class);
    }

    /**
     * Occupancy sub-group.
     */
    public function occupancySubGroup(): BelongsTo
    {
        return $this->belongsTo(OccupancySubGroup::class);
    }
}
