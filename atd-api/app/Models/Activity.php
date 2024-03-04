<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'address',
        'zipcode',
        'start_date',
        'end_date',
        'donation',
        'id_type',
        'archive'
    ];

    public function files(){
        return $this->belongsToMany(File::class, 'activity_files', 'id_activity', 'id_file')->withPivot('archive');
    }
}
