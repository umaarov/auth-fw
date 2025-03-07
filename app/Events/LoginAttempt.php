<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginAttempt
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public string $ipAddress;
    public string $userAgent;

    public function __construct(User $user, string $ipAddress, string $userAgent)
    {
        $this->user = $user;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }
}
