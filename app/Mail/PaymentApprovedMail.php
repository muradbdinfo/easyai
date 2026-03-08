<?php
namespace App\Mail;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $appUrl;
    public string $appName;

    public function __construct(public Payment $payment)
    {
        $this->appUrl  = 'http://' . config('domains.app');
        $this->appName = config('app.name');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . config('app.name') . '] Payment Approved — ' . $this->payment->plan->name . ' Plan Activated',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-approved');
    }
}