<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeatCustomer extends Model
{
    protected $table = 'beat_customers';

    protected $fillable = [
        'firstname',
        'lastname',
        'middlename',
        'gender',
        'birthdate',
        'age',
        'address',
        'email',
        'phone',
        'phone2',
        'keypab',
        'membership_id',
        'membership_start',
        'membership_end',
        'status',
        'created_by',
        'updated_by',
    ];

   
    public function membershipType()
    {
        return $this->belongsTo(BeatMembership::class, 'membership_id');
    }

    public function attendanceMonitorings()
    {
        return $this->hasMany(BeatAttendanceMonitoring::class, 'beat_customer_id')->orderBy('created_at');
    }
}
