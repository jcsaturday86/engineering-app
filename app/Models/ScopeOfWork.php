<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScopeOfWork extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scope_of_works';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category',
        'is_active',
        'sort_order',
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
            'sort_order' => 'integer',
        ];
    }
}
