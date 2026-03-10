<?php
// FILE: app/Mail/InvitationMail.php
namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $appName;

    public function __construct(public TeamInvitation $invitation)
    {
        $this->appName = config('app.name');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . $this->appName . '] You\'ve been invited to join ' . $this->invitation->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitation');
    }
}