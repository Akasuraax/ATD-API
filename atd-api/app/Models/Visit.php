<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_volunteer',
        'id_beneficiary',
        'archive',
        'created_at',
        'updated_at'
    ];
}
