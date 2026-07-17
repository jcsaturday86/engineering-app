<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MechanicalPermitUnit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mechanical_application_id',
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

    public function mechanicalApplication(): BelongsTo
    {
        return $this->belongsTo(MechanicalApplication::class);
    }

    public function permit(): BelongsTo
    {
        return $this->belongsTo(Permit::class);
    }
}
