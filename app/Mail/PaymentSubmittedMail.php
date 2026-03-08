<?php
namespace App\Mail;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $adminUrl;
    public string $appName;

    public function __construct(public Payment $payment)
    {
        $this->adminUrl = 'http://' . config('domains.admin');
        $this->appName  = config('app.name');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . config('app.name') . '] New Payment Submitted — ' . $this->payment->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-submitted');
    }
}