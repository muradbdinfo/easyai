<?php
// FILE: app/Http/Controllers/Admin/SettingsController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'platform' => [
                'app_name'      => \App\Models\Setting::get('app_name', config('app.name')),
                'support_email' => \App\Models\Setting::get('support_email', env('MAIL_FROM_ADDRESS', '')),
                'logo_url'      => \App\Models\Setting::get('logo_url'),
            ],
            'ollama' => [
                'url'   => \App\Models\Setting::get('ollama_url',   config('ollama.url')),
                'model' => \App\Models\Setting::get('ollama_model', config('ollama.model')),
            ],
            'mail' => [
                'host'       => env('MAIL_HOST', ''),
                'port'       => env('MAIL_PORT', 587),
                'username'   => env('MAIL_USERNAME', ''),
                'from_name'  => env('MAIL_FROM_NAME', ''),
                'from_email' => env('MAIL_FROM_ADDRESS', ''),
            ],
            'theme' => [
                'brand'        => \App\Models\Setting::get('theme_brand',        '#6366f1'),
                'tenant_mode'  => \App\Models\Setting::get('theme_tenant_mode',  'dark'),
                'landing_mode' => \App\Models\Setting::get('theme_landing_mode', 'dark'),
            ],
            'landing' => [
                'hero_title'    => \App\Models\Setting::get('landing_hero_title',    'Your Private AI Workspace'),
                'hero_subtitle' => \App\Models\Setting::get('landing_hero_subtitle', 'Self-hosted, multi-tenant AI workspace.'),
                'hero_cta'      => \App\Models\Setting::get('landing_hero_cta',      'Start Free Trial'),
                'announcement'  => \App\Models\Setting::get('landing_announcement',  ''),
                'show_pricing'  => \App\Models\Setting::get('landing_show_pricing', '1') === '1',
                'show_contact'  => \App\Models\Setting::get('landing_show_contact', '1') === '1',
                'contact_email' => \App\Models\Setting::get('landing_contact_email', env('MAIL_FROM_ADDRESS', '')),
                'footer_text'   => \App\Models\Setting::get('landing_footer_text',  'EasyAI — Self-Hosted AI Workspace'),
                'features'      => json_decode(\App\Models\Setting::get('landing_features', '[]'), true) ?: [],
                'faq'           => json_decode(\App\Models\Setting::get('landing_faq',      '[]'), true) ?: [],
            ],
            // ── Superadmins ────────────────────────────────────────
            'superadmins' => User::where('role', 'superadmin')
                ->select('id', 'name', 'email', 'created_at')
                ->latest()
                ->get(),
        ]);
    }

    // ── Platform ───────────────────────────────────────────────────
    public function updatePlatform(Request $request)
    {
        $request->validate(['app_name' => ['required','string','max:100'], 'support_email' => ['required','email']]);
        \App\Models\Setting::set('app_name', $request->app_name);
        \App\Models\Setting::set('support_email', $request->support_email);
        return back()->with('success', 'Platform settings saved.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate(['logo' => ['required','image','mimes:png,jpg,jpeg,svg,webp','max:2048']]);
        $old = \App\Models\Setting::get('logo_path');
        if ($old) Storage::disk('public')->delete($old);
        $path = $request->file('logo')->store('logos', 'public');
        \App\Models\Setting::set('logo_path', $path);
        \App\Models\Setting::set('logo_url',  asset('storage/'.$path));
        return back()->with('success', 'Logo updated.');
    }

    public function deleteLogo()
    {
        $path = \App\Models\Setting::get('logo_path');
        if ($path) Storage::disk('public')->delete($path);
        \App\Models\Setting::set('logo_path', null);
        \App\Models\Setting::set('logo_url',  null);
        return back()->with('success', 'Logo removed.');
    }

    // ── Ollama ─────────────────────────────────────────────────────
    public function updateOllama(Request $request)
    {
        $request->validate(['url' => ['required','url'], 'model' => ['required','string']]);
        \App\Models\Setting::set('ollama_url',   $request->url);
        \App\Models\Setting::set('ollama_model', $request->model);
        return back()->with('success', 'Ollama settings saved.');
    }

    public function testOllama()
    {
        try {
            $url  = \App\Models\Setting::get('ollama_url') ?? config('ollama.url');
            $resp = Http::timeout(5)->get($url.'/api/tags');
            return response()->json(['success' => $resp->ok(), 'message' => $resp->ok() ? 'Connected.' : 'Failed.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Mail ───────────────────────────────────────────────────────
    public function updateMail(Request $request)
    {
        $request->validate([
            'host'       => ['required','string'],
            'port'       => ['required','integer'],
            'username'   => ['nullable','string'],
            'password'   => ['nullable','string'],
            'from_name'  => ['required','string'],
            'from_email' => ['required','email'],
        ]);
        foreach ($request->only('host','port','username','from_name','from_email') as $k => $v) {
            \App\Models\Setting::set('mail_'.$k, $v);
        }
        if ($request->filled('password')) \App\Models\Setting::set('mail_password', $request->password);
        return back()->with('success', 'Mail settings saved.');
    }

    public function testMail(Request $request)
    {
        $request->validate(['email' => ['required','email']]);
        try {
            Mail::raw('EasyAI mail test.', fn($m) => $m->to($request->email)->subject('EasyAI Mail Test'));
            return back()->with('success', 'Test email sent to '.$request->email);
        } catch (\Throwable $e) {
            return back()->withErrors(['email' => 'Mail failed: '.$e->getMessage()]);
        }
    }

    // ── Theme ──────────────────────────────────────────────────────
    public function updateTheme(Request $request)
    {
        $request->validate([
            'brand'        => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tenant_mode'  => ['required', 'in:dark,light'],
            'landing_mode' => ['required', 'in:dark,light'],
        ]);
        \App\Models\Setting::set('theme_brand',        $request->brand);
        \App\Models\Setting::set('theme_tenant_mode',  $request->tenant_mode);
        \App\Models\Setting::set('theme_landing_mode', $request->landing_mode);
        return back()->with('success', 'Theme saved.');
    }

    // ── Landing ────────────────────────────────────────────────────
    public function updateLanding(Request $request)
    {
        $request->validate([
            'hero_title'    => ['required','string','max:100'],
            'hero_subtitle' => ['required','string','max:300'],
            'hero_cta'      => ['required','string','max:50'],
            'announcement'  => ['nullable','string','max:200'],
            'show_pricing'  => ['boolean'],
            'show_contact'  => ['boolean'],
            'contact_email' => ['required','email'],
            'footer_text'   => ['required','string','max:100'],
            'features'      => ['nullable','array'],
            'faq'           => ['nullable','array'],
        ]);
        foreach (['hero_title','hero_subtitle','hero_cta','announcement','contact_email','footer_text'] as $k) {
            \App\Models\Setting::set('landing_'.$k, $request->$k);
        }
        \App\Models\Setting::set('landing_show_pricing', $request->show_pricing ? '1' : '0');
        \App\Models\Setting::set('landing_show_contact', $request->show_contact ? '1' : '0');
        \App\Models\Setting::set('landing_features',     json_encode($request->features ?? []));
        \App\Models\Setting::set('landing_faq',          json_encode($request->faq ?? []));
        return back()->with('success', 'Landing page saved.');
    }

    // ── Superadmins ────────────────────────────────────────────────
    public function storeSuperAdmin(Request $request)
    {
        $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'email', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => bcrypt($request->password),
            'role'      => 'superadmin',
            'tenant_id' => null,
            'is_active' => true,
        ]);

        return back()->with('success', "Superadmin {$request->name} created.");
    }

    public function deleteSuperAdmin(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'You cannot delete yourself.');
        abort_if($user->role !== 'superadmin', 403);

        if (User::where('role', 'superadmin')->count() <= 1) {
            return back()->withErrors(['delete' => 'Cannot delete the last superadmin.']);
        }

        $user->tokens()->delete();
        $user->delete();

        return back()->with('success', 'Superadmin removed.');
    }
}