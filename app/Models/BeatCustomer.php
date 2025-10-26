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
        'keypab',
        'membership_id',
        'membership_start',
        'membership_end',
        'status',
        'profile_picture',
        'created_by',
        'updated_by',
        'is_frozen',
        'is_terminated',
    ];

   
    public function membershipType()
    {
        return $this->belongsTo(BeatMembership::class, 'membership_id');
    }

    public function attendanceMonitorings()
    {
        return $this->hasMany(BeatAttendanceMonitoring::class, 'beat_customer_id')
            ->orderBy('created_at', 'desc')
            ->limit(5);
    }

    function CustomerPaymentTransactions()
    {
        return $this->hasMany(CustomerPaymentTransaction::class, 'customer_id')->orderBy('created_at', 'desc');
    }

    function getProfilePictureAttribute($value)
    {
        return $value ? env('MYLINK') . '/storage/profiles/' . $value : null;
    }
}
