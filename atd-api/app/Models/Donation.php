<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'user_id',
        'archive',
        'created_at',
        'updated_at'
    ];

    protected $guarded = [
        'id'
    ];
}
