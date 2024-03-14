<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demand extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'id_user',
        'id_type',
        'archive'
    ];

    public function type(){
        return $this->belongsTo(Type::class, 'id_type');
    }

    public function user(){
        return $this->belongsTo(User::class, 'id_user');
    }

    public function archive(){
        $this->archive = true;
        $this->save();
    }
}
