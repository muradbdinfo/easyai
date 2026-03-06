<?php

// FILE: app/Http/Controllers/LandingController.php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class LandingController extends Controller
{
    private function settings(): array
    {
        return [
            'primary_color'    => Setting::get('landing_primary_color', '#6366f1'),
            'app_name'         => Setting::get('app_name', config('app.name', 'EasyAI')),
            'logo_url'         => Setting::get('logo_url'),
            'hero_title'       => Setting::get('landing_hero_title', 'Your Private AI Workspace'),
            'hero_subtitle'    => Setting::get('landing_hero_subtitle', 'Self-hosted, multi-tenant AI workspace for your team. Your data never leaves your server.'),
            'hero_cta'         => Setting::get('landing_hero_cta', 'Start Free Trial'),
            'announcement'     => Setting::get('landing_announcement'),
            'show_pricing'     => Setting::get('landing_show_pricing', '1') === '1',
            'show_contact'     => Setting::get('landing_show_contact', '1') === '1',
            'contact_email'    => Setting::get('landing_contact_email', env('MAIL_FROM_ADDRESS', '')),
            'footer_text'      => Setting::get('landing_footer_text', 'EasyAI — Self-Hosted AI Workspace'),
            'features'         => json_decode(Setting::get('landing_features', '[]'), true) ?: $this->defaultFeatures(),
            'faq'              => json_decode(Setting::get('landing_faq', '[]'), true) ?: $this->defaultFaq(),
        ];
    }

    public function home()
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();
        return Inertia::render('Landing/Home', [
            'settings' => $this->settings(),
            'plans'    => $plans,
        ]);
    }

    public function pricing()
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();
        return Inertia::render('Landing/Pricing', [
            'settings' => $this->settings(),
            'plans'    => $plans,
        ]);
    }

    public function contact()
    {
        return Inertia::render('Landing/Contact', [
            'settings' => $this->settings(),
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email'],
            'subject' => ['required', 'string'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        try {
            $to = Setting::get('landing_contact_email', env('MAIL_FROM_ADDRESS', 'admin@easyai.local'));
            Mail::raw(
                "From: {$request->name} <{$request->email}>\nSubject: {$request->subject}\n\n{$request->message}",
                fn ($m) => $m->to($to)
                    ->subject("Contact: {$request->subject}")
                    ->replyTo($request->email, $request->name)
            );
        } catch (\Throwable) {}

        return back()->with('success', "Message sent! We'll get back to you within 24 hours.");
    }

    private function defaultFeatures(): array
    {
        return [
            ['icon' => '🧠', 'title' => 'AI Memory',           'desc' => 'Projects remember context across conversations.'],
            ['icon' => '📁', 'title' => 'Projects & Chats',    'desc' => 'Organize work into projects with full history.'],
            ['icon' => '📚', 'title' => 'Knowledge Base',      'desc' => 'Upload documents. AI answers from your own data.'],
            ['icon' => '👥', 'title' => 'Team Collaboration',  'desc' => 'Invite teammates, assign roles, restrict projects.'],
            ['icon' => '🔒', 'title' => '100% Private',        'desc' => 'Self-hosted Ollama. Data never leaves your server.'],
            ['icon' => '💳', 'title' => 'Flexible Billing',    'desc' => 'COD, bKash/Nagad via SSLCommerz, or Stripe.'],
            ['icon' => '📄', 'title' => 'Chat Export',         'desc' => 'Export conversations as PDF or Markdown.'],
            ['icon' => '⚡', 'title' => 'Token Quotas',        'desc' => 'Per-tenant token limits with monthly auto-reset.'],
            ['icon' => '🔑', 'title' => 'REST API',            'desc' => 'Full API with Sanctum token auth for mobile apps.'],
        ];
    }

    private function defaultFaq(): array
    {
        return [
            ['q' => 'Is my data private?',                    'a' => 'Yes. EasyAI is fully self-hosted. All AI processing happens via your own Ollama instance.'],
            ['q' => 'Which AI models are supported?',         'a' => 'Any model supported by Ollama — llama3, mistral, codellama, gemma, and more.'],
            ['q' => 'Can I use it for my whole team?',        'a' => 'Yes. Invite team members, assign roles, and restrict projects per member.'],
            ['q' => 'What payment methods are accepted?',     'a' => 'Cash on Delivery, bKash/Nagad via SSLCommerz, and international cards via Stripe.'],
            ['q' => 'Do I need technical knowledge to deploy?', 'a' => 'Basic server knowledge is enough. Works on cPanel and VPS (Ubuntu 22).'],
        ];
    }
}