<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeatCustomerMedical extends Model
{
    protected $table = 'beat_customer_medicals';

    protected $fillable = [
        'beat_customer_id',
        'medical_condition',
    ];

    
}
