<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'day',
        'start_hour',
        'end_hour',
        'checking',
        'archive',
        'created_at',
        'updated_at',
        'user_id'
    ];
}
