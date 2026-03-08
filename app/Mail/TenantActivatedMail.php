<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $appUrl;
    public string $appName;

    public function __construct(public Tenant $tenant)
    {
        $this->appUrl  = 'http://' . config('domains.app');
        $this->appName = config('app.name');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . config('app.name') . '] Your workspace has been activated!',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant-activated');
    }
}