<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_USER = 'user';
    const ROLE_FIRMENADMIN = 'firmenadmin';
    const ROLE_SUPERADMIN = 'superadmin';

    const ROLES = [
        self::ROLE_USER => 'Benutzer',
        self::ROLE_FIRMENADMIN => 'Firmen-Admin',
        self::ROLE_SUPERADMIN => 'Superadmin',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'modules',
        'jtl_host',
        'jtl_port',
        'jtl_database',
        'jtl_username',
        'jtl_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'jtl_password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'modules' => 'array',
        ];
    }

    public function hasModule(string $key): bool
    {
        return in_array($key, $this->modules ?? []);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isFirmenadmin(): bool
    {
        return $this->role === self::ROLE_FIRMENADMIN;
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, [self::ROLE_FIRMENADMIN, self::ROLE_SUPERADMIN]);
    }

}
