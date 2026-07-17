<?php

namespace App\Models;

use App\Concerns\HasPermitApplicationBehavior;
use App\Contracts\PermitApplicationContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class MechanicalApplication extends Model implements PermitApplicationContract
{
    use HasPermitApplicationBehavior, LogsActivity, SoftDeletes;

    protected $table = 'mechanical_applications';

    protected $fillable = [
        'app_year',
        'app_month',
        'app_counter',
        'application_number',
        'status',
        'source',
        'application_kind',
        // Owner / Lessee
        'owner_name',
        // Location Address
        'location_street',
        'location_barangay_id',
        // Processing
        'entered_by',
        'assessed_by',
        'approved_by',
        'submitted_at',
        'assessed_at',
        'approved_at',
        'paid_at',
        'released_at',
        'cancelled_at',
        'cancellation_reason',
        'client_user_id',
        'issued_date',
    ];

    protected function casts(): array
    {
        return [
            'app_year' => 'integer',
            'app_month' => 'integer',
            'app_counter' => 'integer',
            'submitted_at' => 'datetime',
            'assessed_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'issued_date' => 'date',
        ];
    }

    /**
     * Overrides HasPermitApplicationBehavior::buildingBarangay() and ::applicantBarangay(),
     * which target columns that don't exist on this table. Mechanical applications only have
     * one address concept — Location Address — so both generic relation names alias to it,
     * since generic code (e.g. PermitController::print()) eager-loads both by name.
     */
    public function buildingBarangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'location_barangay_id');
    }

    public function applicantBarangay(): BelongsTo
    {
        return $this->buildingBarangay();
    }

    public function locationBarangay(): BelongsTo
    {
        return $this->buildingBarangay();
    }

    public function mechanicalPermitUnits(): HasMany
    {
        return $this->hasMany(MechanicalPermitUnit::class);
    }

    public function getPermitTypeCode(): string
    {
        return 'MP';
    }
}
