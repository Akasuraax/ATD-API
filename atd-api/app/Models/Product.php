<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function recipes(){
        return $this->belongsToMany(Recipe::class, 'makes', 'id_product', 'id_recipe')->withPivot('archive', 'count', 'measure');
    }

}
