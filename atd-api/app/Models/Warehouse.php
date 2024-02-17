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
        'capacity'
    ];
}
