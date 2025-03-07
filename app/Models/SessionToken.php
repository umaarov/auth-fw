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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('last_activity', '>=', now()->subDays(30));
    }

    public function isCurrentSession(): bool
    {
        return $this->token === (string)request()->bearerToken();
    }
}
