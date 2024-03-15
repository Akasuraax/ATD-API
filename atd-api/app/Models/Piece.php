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
        'location',
        'id_warehouse',
        'id_product',
        'archive'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }

    public function archive(){
        $this->archive = true;
        $this->save();
    }
}
