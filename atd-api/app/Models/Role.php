<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'archive'
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'have_roles', 'id_role', 'id_user')->withTimestamps();
    }

    public function activities(){
        return $this->belongsToMany(Activity::class, 'limits', 'id_role', 'id_activity')->withPivot('min', 'max', 'count', 'archive');
    }

    public function archive(){
        $this->archive = true;
        $this->save();

        $userIds = $this->users->pluck('id')->toArray();
        $activityIds = $this->activities->pluck('id')->toArray();
        $this->users()->updateExistingPivot($userIds, ['archive' => true]);
        $this->activities()->updateExistingPivot($activityIds, ['archive' => true]);
    }
}
