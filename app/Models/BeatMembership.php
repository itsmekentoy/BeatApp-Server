<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeatMembership extends Model
{
    protected $table = 'beat_memberships';

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'status',
        'created_by',
        'updated_by',
    ];
}
