<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPaymentTransaction extends Model
{
    protected $table = 'customer_payment_transactions';

    protected $fillable = [
        'customer_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference',
        'notes',
        'transaction_id',
        'added_by',
        'status',
    ];
}
