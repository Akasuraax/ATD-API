<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Journey;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'license_plate',
        'average_consumption',
        'fuel_type',
        'id_annexe',
        'vehicle',
        'archive'
    ];

    public function journeys(){
        return $this->belongsToMany(Journey::class, 'drives', 'id_vehicle', 'id_journey')->withPivot('archive');
    }

    public function annexe()
    {
        return $this->belongsTo(Annexe::class, 'id_annexe');
    }

    public function archive()
    {
        $this->archive = true;
        $this->save();

        $journeyIds = $this->journeys->pluck('id')->toArray();
        Journey::whereIn('id', $journeyIds)->get()->each(function($journey) {
            $journey->archive();
        });

        $this->journeys()->updateExistingPivot($journeyIds, ['archive' => true]);
    }

}

