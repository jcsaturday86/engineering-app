<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZoningAssessment extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'project_lifespan',
        'project_significance',
        'project_classification',
        'site_zoning_classification',
        'right_over_lands',
        'radius_covered',
        'land_use_radius',
        'findings_evaluation',
        'decision_recommended',
        'date_evaluation',
        'project_status',
        'boundary_north',
        'boundary_south',
        'boundary_east',
        'boundary_west',
        'building_coverage',
        'secure_ecc',
        'off_street_parking',
        'decision_no',
        'certificate_date',
        'assessed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_evaluation' => 'date',
            'secure_ecc' => 'boolean',
            'off_street_parking' => 'boolean',
            'decision_no' => 'integer',
            'certificate_date' => 'date',
        ];
    }

    /**
     * Application this zoning assessment belongs to.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * User who performed the zoning assessment.
     */
    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
