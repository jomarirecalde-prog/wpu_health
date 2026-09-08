<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentSetting extends Model
{
    protected $fillable = ['setting_key', 'setting_value'];
}
