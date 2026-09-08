<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffSignature extends Model
{
    public const UPDATED_AT = 'updated_at';

    public const CREATED_AT = null;

    protected $table = 'staff_signatures';

    protected $fillable = ['name', 'position', 'license_no'];
}
