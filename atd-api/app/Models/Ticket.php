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
        'status',
        'severity',
        'archive',
        'problem_id'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'sends', 'id_ticket', 'id_user')->withTimestamps();
    }

    public function support()
    {
        return $this->belongsToMany(User::class, 'sends', 'id_ticket', 'id_user')
            ->withPivot('created_at');
    }

    public function messages(){
        return $this->hasMany(Message::class, 'id_ticket');
    }

    public function problem()
    {
        return $this->belongsTo(Problem::class, 'problem_id');
    }
    public function archive(){
        $this->archive = true;
        $this->save();

        $this->messages()->update(['archive' => true]);
        $userIds = $this->users->pluck('id')->toArray();
        $this->users()->updateExistingPivot($userIds, ['archive' => true]);
    }
}

