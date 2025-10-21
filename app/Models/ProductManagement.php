<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductManagement extends Model
{
    protected $table = 'product_management';

    protected $fillable = [
        'product_name',
        'category',
        'description',
        'price',
        'stock_quantity',
    ];
}
