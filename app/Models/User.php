<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'verification_token',
        'email_verified_at',
        'is_active',
        'last_login_at',
        'failed_login_attempts',
        'is_locked',
        'locked_until',
        'two_factor_enabled',
        'two_factor_secret',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'verification_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'two_factor_enabled' => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'roles' => $this->getRoleNames(),
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SessionToken::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->sessions()->active();
    }

    public function loginHistory(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }
}
