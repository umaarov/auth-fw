<?php

namespace App\Listeners;

use App\Events\LoginAttempt;
use App\Models\LoginHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Stevebauman\Location\Facades\Location;

class RecordLoginAttempt implements ShouldQueue
{
    public function handle(LoginAttempt $event): void
    {
        $location = Location::get($event->ipAddress);
        $locationString = $location ? "{$location->cityName}, {$location->regionName}, {$location->countryName}" : null;

        LoginHistory::create([
            'user_id' => $event->user->id,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'location' => $locationString,
            'successful' => true,
            'login_at' => now(),
        ]);
    }
}
