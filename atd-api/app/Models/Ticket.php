<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'severity',
        'archive'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'sends', 'id_ticket', 'id_user')->withTimestamps();
    }

    public function messages(){
        return $this->hasMany(Message::class, 'id_ticket');
    }

    public function archive(){
        $this->archive = true;
        $this->save();

        $this->messages()->update(['archive' => true]);
        $userIds = $this->users->pluck('id')->toArray();
        $this->users()->updateExistingPivot($userIds, ['archive' => true]);
    }
}

