<?php

namespace App\Services;

use App\Mail\RegisteredSuccessfully;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class MailtrapSender
{
    public function sendTestEmail(string $to): void
    {
        Mail::raw(
            'Congrats for sending test email with Mailtrap!',
            function ($message) use ($to): void {
                $message->to($to)->subject('You are awesome!');
            },
        );
    }

    public function sendRegistrationEmail(User $user): void
    {
        Mail::to($user->email)->send(new RegisteredSuccessfully($user));
    }
}
