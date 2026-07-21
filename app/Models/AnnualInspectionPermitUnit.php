<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnualInspectionPermitUnit extends Model
{
    use SoftDeletes;

    protected $table = 'annual_inspection_permit_units';

    protected $fillable = [
        'annual_inspection_application_id',
        'assessment_item_id',
        'group_code',
        'description',
        'quantity',
        'amount',
        'permit_id',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'amount' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function annualInspectionApplication(): BelongsTo
    {
        return $this->belongsTo(AnnualInspectionApplication::class);
    }

    public function permit(): BelongsTo
    {
        return $this->belongsTo(Permit::class);
    }

    public function assessmentItem(): BelongsTo
    {
        return $this->belongsTo(AssessmentItem::class);
    }
}
