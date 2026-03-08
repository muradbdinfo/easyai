<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrap { max-width: 580px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
  .header { background: #16a34a; padding: 24px 30px; }
  .header h1 { color: #fff; margin: 0; font-size: 20px; }
  .header span { color: #bbf7d0; font-size: 13px; }
  .body { padding: 30px; color: #334155; }
  .body p { margin: 0 0 14px; line-height: 1.6; }
  .box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 18px 22px; margin: 18px 0; }
  .box h2 { margin: 0 0 6px; color: #15803d; font-size: 17px; }
  .box p { margin: 0; color: #166534; font-size: 14px; }
  .btn { display: inline-block; background: #16a34a; color: #fff; padding: 10px 22px; border-radius: 6px; text-decoration: none; font-size: 14px; margin-top: 6px; }
  .footer { background: #f8fafc; padding: 16px 30px; text-align: center; color: #94a3b8; font-size: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>✅ Workspace Activated!</h1>
    <span>{{ $appName }} — Good news for your team</span>
  </div>
  <div class="body">
    <p>Hello,</p>
    <p>Your <strong>{{ $appName }}</strong> workspace <strong>"{{ $tenant->name }}"</strong> has been activated by the admin. You can now use all features on your current plan.</p>

    <div class="box">
      <h2>{{ $tenant->plan?->name ?? 'Current' }} Plan — Active</h2>
      <p>{{ number_format($tenant->token_quota) }} tokens / month available</p>
    </div>

    <p>Log in to your workspace and start using EasyAI:</p>
    <a href="{{ $appUrl }}/dashboard" class="btn">Go to Dashboard</a>

    <p style="margin-top:20px; font-size:13px; color:#64748b;">
      If you have any questions, contact us at {{ config('mail.from.address') }}.
    </p>
  </div>
  <div class="footer">
    {{ $appName }} &mdash; {{ now()->format('Y') }} &mdash;
    <a href="{{ $appUrl }}" style="color:#16a34a;">{{ $appUrl }}</a>
  </div>
</div>
</body>
</html>