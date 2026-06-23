<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormOfOwnership extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'form_of_ownerships';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
