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
}
