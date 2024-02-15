<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HaveRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_user',
        'id_role',
        'archive',
        'created_at',
        'updated_at'
    ];
}
