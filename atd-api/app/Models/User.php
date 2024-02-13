<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class User extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'forname',
        'email',
        'password',
        'phone_country',
        'phone_number',
        'gender',
        'birth_date',
        'address',
        'zipcode',
        'status',
        'ban',
        'notification',
        'archive'
    ];

    protected $guarded = [

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'ban' => 'boolean',
        'notification' => 'boolean',
        'archive' => 'boolean',
    ];
}
