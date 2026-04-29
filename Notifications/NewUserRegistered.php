<?php

// FILE: app/Notifications/NewUserRegistered.php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewUserRegistered extends Notification
{
    public function __construct(public User $newUser) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New User Registered — EasyAI')
            ->greeting('Hello Admin,')
            ->line("**{$this->newUser->name}** ({$this->newUser->email}) just registered.")
            ->line('Workspace: ' . ($this->newUser->tenant?->name ?? 'N/A'))
            ->line('Plan: Starter (14-day trial)')
            ->action('View in Admin Panel', 'http://' . config('domains.admin') . '/tenants')
            ->line('EasyAI Platform');
    }
}