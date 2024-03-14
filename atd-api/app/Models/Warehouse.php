<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'address',
        'zipcode',
        'capacity',
        'archive'
    ];

    public function pieces(){
        return $this->hasMany(Piece::class, 'id_warehouse');
    }

    public function archive(){
        $this->archive = true;
        $this->save();

        $this->pieces()->update(['archive' => true]);
    }
}
