<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantSuspendedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $appUrl;
    public string $appName;
    public string $supportEmail;

    public function __construct(public Tenant $tenant)
    {
        $this->appUrl       = 'http://' . config('domains.app');
        $this->appName      = config('app.name');
        $this->supportEmail = config('mail.from.address');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . config('app.name') . '] Your workspace has been suspended',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant-suspended');
    }
}