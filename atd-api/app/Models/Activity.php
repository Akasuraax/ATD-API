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

    public function users(){
        return $this->belongsToMany(User::class, 'participates', 'id_activity', 'id_user')->withPivot('count', 'archive');
    }

    public function roles(){
        return $this->belongsToMany(Role::class, 'limits', 'id_activity', 'id_role')->withPivot('min', 'max', 'count', 'archive');
    }

    public function products(){
        return $this->belongsToMany(Product::class, 'gives', 'id_activity', 'id_product')->withPivot('count', 'archive');
    }

    public function recipes(){
        return $this->belongsToMany(Recipe::class, 'contains', 'id_activity', 'id_recipe')->withPivot('count', 'archive');
    }

    public function type(){
        return $this->belongsTo(Type::class, 'id_type', 'id');
    }

    public function journeys()
    {
        return $this->hasMany(Journey::class, 'id_activity');
    }

    public function archive()
    {
        $this->archive = true;
        $this->save();

        $this->journeys()->update(['archive' => true]);
    }

}
