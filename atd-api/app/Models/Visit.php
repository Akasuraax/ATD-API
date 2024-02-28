<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'checking',
        'id_volunteer',
        'id_beneficiary',
        'archive'
    ];

    protected $rules = [
        'checking' => 'required|string|max:255',
        'attribute2' => 'numeric',
        'attribute3' => 'email|unique:visits,email',
    ];
}
