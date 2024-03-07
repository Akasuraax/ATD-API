<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Piece extends Model
{
    use HasFactory;

    protected $fillable = [
        'expired_date',
        'count',
        'measure',
        'id_warehouse',
        'id_product',
        'archive'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_products');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }
}
