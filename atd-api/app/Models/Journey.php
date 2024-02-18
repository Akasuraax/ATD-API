<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'duration',
        'distance',
        'cost',
        'fuel_cost'
    ];

    public function vehicles(){
        return $this->belongsToMany(Vehicle::class, 'drives', 'id_journey', 'id_vehicle')->withPivot('archive');
    }
}
