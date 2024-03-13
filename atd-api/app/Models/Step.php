<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'zipcode',
        'time',
        'id_journey',
        'archive'
    ];

    public function journey(){
        return $this->belongsTo(Journey::class, 'id_journey');
    }

    public function archive(){
        $this->archive = true;
        $this->save();
    }
}
