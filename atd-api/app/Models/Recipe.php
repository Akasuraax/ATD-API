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
}
