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
      'access_to_journey'
    ];

    protected $guarded = [
        'archive'
    ];
}
