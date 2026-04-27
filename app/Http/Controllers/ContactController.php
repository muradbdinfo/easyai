<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email|max:100',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ]);

        $webhookUrl = config('services.contact.webhook_url');

        if ($webhookUrl) {
            try {
                Http::timeout(10)->post($webhookUrl, [
                    'name'       => $data['name'],
                    'email'      => $data['email'],
                    'subject'    => $data['subject'],
                    'message'    => $data['message'],
                    'submitted_at' => now()->format('Y-m-d H:i:s'),
                    'site_url'   => config('app.url'),
                ]);
            } catch (\Throwable $e) {
                Log::error('Contact webhook error: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Your message has been sent. We will get back to you shortly.');
    }
}