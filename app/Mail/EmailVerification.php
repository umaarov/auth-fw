<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Verify Your Email Address')
            ->markdown('emails.verification')
            ->with([
                'verificationUrl' => url("/api/verify-email/{$this->user->verification_token}"),
                'name' => $this->user->name
            ]);
    }
}
