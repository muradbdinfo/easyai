<?php

// FILE: app/Http/Controllers/Admin/SettingsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'platform' => [
                'app_name'      => config('app.name'),
                'support_email' => env('MAIL_FROM_ADDRESS', ''),
                'logo_url'      => \App\Models\Setting::get('logo_url'),
            ],
            'ollama' => [
                'url'   => config('ollama.url'),
                'model' => config('ollama.model'),
            ],
            'mail' => [
                'host'       => env('MAIL_HOST', ''),
                'port'       => env('MAIL_PORT', ''),
                'username'   => env('MAIL_USERNAME', ''),
                'from_name'  => env('MAIL_FROM_NAME', ''),
                'from_email' => env('MAIL_FROM_ADDRESS', ''),
            ],
        ]);
    }

    public function updatePlatform(Request $request)
    {
        $request->validate([
            'app_name'      => ['required', 'string', 'max:100'],
            'support_email' => ['required', 'email'],
        ]);

        \App\Models\Setting::set('app_name', $request->app_name);
        \App\Models\Setting::set('support_email', $request->support_email);

        return back()->with('success', 'Platform settings saved.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        $old = \App\Models\Setting::get('logo_path');
        if ($old) Storage::disk('public')->delete($old);

        $path = $request->file('logo')->store('logos', 'public');
        \App\Models\Setting::set('logo_path', $path);
        \App\Models\Setting::set('logo_url', asset('storage/' . $path));

        return back()->with('success', 'Logo updated.');
    }

    public function deleteLogo()
    {
        $path = \App\Models\Setting::get('logo_path');
        if ($path) Storage::disk('public')->delete($path);
        \App\Models\Setting::set('logo_path', null);
        \App\Models\Setting::set('logo_url', null);

        return back()->with('success', 'Logo removed.');
    }

    public function updateOllama(Request $request)
    {
        $request->validate([
            'url'   => ['required', 'url'],
            'model' => ['required', 'string'],
        ]);

        \App\Models\Setting::set('ollama_url', $request->url);
        \App\Models\Setting::set('ollama_model', $request->model);

        return back()->with('success', 'Ollama settings saved.');
    }

    public function testOllama()
    {
        try {
            $url  = \App\Models\Setting::get('ollama_url') ?? config('ollama.url');
            $resp = \Illuminate\Support\Facades\Http::timeout(5)->get($url . '/api/tags');
            return response()->json([
                'success' => $resp->ok(),
                'message' => $resp->ok() ? 'Ollama connected.' : 'Connection failed.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateMail(Request $request)
    {
        $request->validate([
            'host'       => ['required', 'string'],
            'port'       => ['required', 'integer'],
            'username'   => ['nullable', 'string'],
            'password'   => ['nullable', 'string'],
            'from_name'  => ['required', 'string'],
            'from_email' => ['required', 'email'],
        ]);

        foreach ($request->only('host','port','username','from_name','from_email') as $k => $v) {
            \App\Models\Setting::set('mail_'.$k, $v);
        }
        if ($request->filled('password')) {
            \App\Models\Setting::set('mail_password', $request->password);
        }

        return back()->with('success', 'Mail settings saved.');
    }

    public function testMail(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            \Illuminate\Support\Facades\Mail::raw('EasyAI mail test.', function ($m) use ($request) {
                $m->to($request->email)->subject('EasyAI Mail Test');
            });
            return back()->with('success', 'Test email sent to ' . $request->email);
        } catch (\Throwable $e) {
            return back()->withErrors(['email' => 'Mail failed: ' . $e->getMessage()]);
        }
    }
}