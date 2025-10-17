<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeatAppSetting extends Model
{
    protected $table = 'beat_app_settings';

    protected $fillable = [
        'app_name',
        'app_version',
        'support_email',
        'support_phone',
        'default_language',
        'timezone',
        'maintenance_mode',
        'logo_path',
        'favicon_path',
    ];
}
