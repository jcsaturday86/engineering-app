<?php

namespace App\Http\Controllers;

use App\Models\City;

class GeoController extends Controller
{
    public function barangaysForCity(City $city)
    {
        return $city->barangays()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
