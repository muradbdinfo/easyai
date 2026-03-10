<?php
// FILE: app/Mail/InvitationAcceptedMail.php
namespace App\Mail;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $appUrl;
    public string $appName;

    public function __construct(public TeamInvitation $invitation, public User $newMember)
    {
        $this->appUrl  = 'http://' . config('domains.app');
        $this->appName = config('app.name');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . $this->appName . '] ' . $this->newMember->name . ' accepted your invitation',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitation-accepted');
    }
}