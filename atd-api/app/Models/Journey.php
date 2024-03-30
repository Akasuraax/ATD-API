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
        'archive',
        'id_activity'
    ];

    public function vehicles(){
        return $this->belongsToMany(Vehicle::class, 'drives', 'id_journey', 'id_vehicle')->withPivot('archive');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'id_activity');
    }

    public function steps(){
        return $this->hasMany(Step::class, 'id_journey');
    }

    public function archive(){
        $this->archive = true;
        $this->save();
        $this->steps()->update(['archive' => true]);
    }



}
