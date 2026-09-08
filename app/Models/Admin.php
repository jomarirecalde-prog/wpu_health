<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $table = 'admins';

    /** @var list<string> */
    protected $fillable = [
        'username',
        'password',
        'role',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'two_factor_enabled',
        'two_factor_secret',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /** @var list<string> */
    protected $hidden = [
        'password',
        'two_factor_secret',
    ];

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'locked_until' => 'datetime',
            'password_changed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'failed_login_attempts' => 'integer',
        ];
    }
}
