<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class SuspiciousLogin extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $ipAddress;
    protected array $reasons;
    protected Carbon $time;

    public function __construct(string $ipAddress, array $reasons, Carbon $time)
    {
        $this->ipAddress = $ipAddress;
        $this->reasons = $reasons;
        $this->time = $time;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Suspicious Login Detected')
            ->line('We detected a suspicious login to your account.')
            ->line('Time: ' . $this->time->format('Y-m-d H:i:s'))
            ->line('IP Address: ' . $this->ipAddress)
            ->line('Suspicious activity: ' . implode(', ', $this->reasons))
            ->line('If this was you, you can ignore this message.')
            ->line('If you did not log in at this time, please change your password immediately.');
    }
}
