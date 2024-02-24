<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Piece extends Model
{
    use HasFactory;

    protected $fillable = [
        'expired_date',
        'count',
        'measure',
        'id_warehouse',
        'id_product'
    ];
}
