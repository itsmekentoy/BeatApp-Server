<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoldProduct extends Model
{
    protected $table = 'sold_products';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'sub_total',
    ];

    public $timestamps = true;

    public function product()
    {
        return $this->belongsTo(ProductManagement::class, 'product_id');
    }
}
