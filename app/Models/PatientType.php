<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientType extends Model
{
    public $timestamps = false;

    protected $fillable = ['type_name', 'color_code'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
