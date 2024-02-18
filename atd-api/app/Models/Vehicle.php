<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'license_plate',
        'average_consumption',
        'fuel_type',
        'id_annexe'
    ];

    public function journeys(){
        return $this->belongsToMany(Journey::class, 'drives', 'id_vehicle', 'id_journey')->withPivot('archive');
    }
}

