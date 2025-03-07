<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\LoginAttempt;
use App\Listeners\RecordLoginAttempt;
use App\Events\UserRegistered;
use App\Listeners\AssignDefaultRole;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        LoginAttempt::class => [
            RecordLoginAttempt::class,
        ],
        UserRegistered::class => [
            AssignDefaultRole::class,
        ],
    ];

    public function boot(): void
    {
        // parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
