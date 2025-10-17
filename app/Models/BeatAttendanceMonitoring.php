<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeatAttendanceMonitoring extends Model
{
    protected $fillable = [
        'beat_customer_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
    ];

    public function beatCustomer()
    {
        return $this->belongsTo(BeatCustomer::class);
    }
}
