<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'measure',
        'archive'
    ];

    public function recipes(){
        return $this->belongsToMany(Recipe::class, 'makes', 'id_product', 'id_recipe')->withPivot('archive', 'count', 'measure');
    }

    public function activities(){
        return $this->belongsToMany(Activity::class, 'gives', 'id_product', 'id_activity')->withPivot('count', 'archive');
    }

    public function pieces()
    {
        return $this->hasMany(Piece::class, 'id_product');
    }

    public function archive(){
        $this->archive = true;
        $this->save();
        $recipesIds = $this->recipes->pluck('id')->toArray();
        $activityIds = $this->activities->pluck('id')->toArray();

        $this->pieces()->update(['archive' => true]);
        $this->activities()->updateExistingPivot($activityIds, ['archive' => true]);
        $this->recipes()->updateExistingPivot($recipesIds, ['archive' => true]);
    }


}
