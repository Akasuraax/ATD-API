<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Annexe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'zipcode',
        'archive'
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'id_annexe', 'id');
    }

    public function archive(){
        $this->archive = true;
        $this->save();

        $vehicleIds = $this->vehicles->pluck('id')->toArray();
        Vehicle::whereIn('id', $vehicleIds)->get()->each(function($vehicle){
            $vehicle->archive();
        });
    }
}
