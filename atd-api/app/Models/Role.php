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
}
