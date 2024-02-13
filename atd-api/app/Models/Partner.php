<?php

namespace App\Models;

use App\Models\User;

class Partner extends User
{
    protected $fillable = [
        'siret_number',
        'compagny'
    ];
}
