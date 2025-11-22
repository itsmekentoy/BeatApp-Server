<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionSoldProduct extends Model
{
    protected $table = 'transaction_sold_products';

    protected $fillable = [
        'customer_name',
        'email',
        'total_amount',
    ];

    public $timestamps = true;

    public function soldProducts()
    {
        return $this->hasMany(SoldProduct::class, 'transaction_id', 'id');
    }
}
