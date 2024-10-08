<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'archive',
        'created_at',
        'updated_at'
    ];

    protected $guarded = [
        'id'
    ];

    public function ticket()
    {
        return $this->hasMany(Ticket::class, 'problem_id');
    }

}
