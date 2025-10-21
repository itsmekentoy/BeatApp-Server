<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeatExpesense extends Model
{
    protected $table = 'beat_expesenses';

    protected $fillable = [
        'expense_type',
        'description',
        'expense_date',
        'amount',
        'added_by',
        'updated_by',
    ];
}
