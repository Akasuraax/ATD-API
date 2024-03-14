<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'archive'
    ];

    public function products(){
        return $this->belongsToMany(Product::class, 'makes', 'id_recipe', 'id_product')->withPivot('archive', 'count', 'measure');
    }

    public function activities(){
        return $this->belongsToMany(Activity::class, 'contains', 'id_recipe', 'id_activity')->withPivot('count', 'archive');
    }

    public function makes(){
        return $this->hasMany(Make::class, 'id_recipe');
    }

    public function archive(){
        $this->archive = true;
        $this->save();

        $productIds = $this->products->pluck('id')->toArray();
        $activityIds = $this->activities->pluck('id')->toArray();
        $this->products()->updateExistingPivot($productIds, ['archive' => true]);
        $this->activities()->updateExistingPivot($activityIds, ['archive' => true]);
    }





}
