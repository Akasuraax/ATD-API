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

    public function users(){
        return  $this->belongsTo(User::class, 'id_user');
    }

    public function archiveActivity($name){
        $this->archive = true;
        $this->save();
        $activityIds = $this->activities->pluck('id')->toArray();
        $this->activities()->updateExistingPivot($activityIds, ['archive'=>true]);
        unlink(public_path() . $name);
    }

    public function archiveUser($name){
        $this->archive = true;
        $this->save();
        unlink(public_path() . $name);
    }

}
