<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailManagement extends Model
{
    protected $table = 'email_management';

    protected $fillable = [
        'email',
        'password',
        'filepath',
    ];

    public function getFilepathAttribute($value)
    {
        return $value ? url( $value) : null;
    }

}
