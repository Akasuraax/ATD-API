<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'access_to_warehouse',
        'access_to_journey',
        'image',
        'color',
        'display',
        'archive',
        'created_at',
        'updated_at'
    ];

    public function activities(){
        return $this->hasMany(Activity::class, 'id_type', 'id');
    }

    public function demands(){
        return $this->hasMany(Demand::class,  'id_type');
    }

    public function archive(){
        $this->archive = true;
        $this->save();

        $this->activities()->update(['archive' => true]);
        $this->demands()->update(['archive' => true]);
    }


}
