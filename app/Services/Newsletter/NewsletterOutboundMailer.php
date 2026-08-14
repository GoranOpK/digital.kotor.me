<?php

namespace App\Services\Newsletter;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class NewsletterOutboundMailer
{
    public function send(string $recipientEmail, Mailable $mailable): void
    {
        Mail::to($recipientEmail)->send($mailable);
    }
}
