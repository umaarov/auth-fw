<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionToken extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'device', 'ip', 'token', 'last_activity'];

    protected $casts = [
        'last_activity' => 'datetime',
    ];
    private mixed $token;

    public static function create(array $array)
    {
    }

    final function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    final function scopeActive($query)
    {
        return $query->where('last_activity', '>=', now()->subDays(30));
    }

    final function isCurrentSession(): bool
    {
        return $this->token === (string)request()->bearerToken();
    }
}
