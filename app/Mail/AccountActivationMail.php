<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $activationUrl,
        public string $activationCode
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'আপনার অ্যাকাউন্ট ভেরিফাই ও অ্যাক্টিভেশন লিঙ্ক - PropertyKoi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-activation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
