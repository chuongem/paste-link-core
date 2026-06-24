<?php

use App\Services\MailtrapSender;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('send-mail {to?}', function (?string $to = null) {
    $recipient = $to ?? 'test@example.com';

    app(MailtrapSender::class)->sendTestEmail($recipient);

    $this->info("Test email sent to {$recipient}. Check your Mailtrap inbox.");

    return 0;
})->purpose('Send a test email via configured SMTP mailer');
