<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'id_user',
        'id_ticket',
        'archive'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    public function userWhoSendTheMessage()
    {
        return $this->belongsTo(User::class, 'id_user')->select('id', 'name', 'forname');
    }
}
