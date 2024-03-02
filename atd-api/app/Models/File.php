<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'link',
        'id_user'
    ];

    public function activities(){
        return $this->belongsToMany(Activity::class, 'activity_files', 'id_file', 'id_activity')->withPivot('archive');
    }

}
