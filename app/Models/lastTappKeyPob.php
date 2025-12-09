<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class lastTappKeyPob extends Model
{
    protected $table = 'last_tapp_key_pobs';

    protected $fillable = [
        'keyfob_number',
    ];
}
