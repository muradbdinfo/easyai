<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrap { max-width: 580px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
  .header { background: #dc2626; padding: 24px 30px; }
  .header h1 { color: #fff; margin: 0; font-size: 20px; }
  .header span { color: #fecaca; font-size: 13px; }
  .body { padding: 30px; color: #334155; }
  .body p { margin: 0 0 14px; line-height: 1.6; }
  .box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 18px 22px; margin: 18px 0; }
  .box h2 { margin: 0 0 6px; color: #dc2626; font-size: 17px; }
  .box p { margin: 0; color: #991b1b; font-size: 14px; }
  .btn { display: inline-block; background: #6366f1; color: #fff; padding: 10px 22px; border-radius: 6px; text-decoration: none; font-size: 14px; margin-top: 6px; }
  .footer { background: #f8fafc; padding: 16px 30px; text-align: center; color: #94a3b8; font-size: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>⚠️ Workspace Suspended</h1>
    <span>{{ $appName }} — Important account notice</span>
  </div>
  <div class="body">
    <p>Hello,</p>
    <p>Your <strong>{{ $appName }}</strong> workspace <strong>"{{ $tenant->name }}"</strong> has been suspended by the admin. Access to your workspace is temporarily disabled.</p>

    <div class="box">
      <h2>What does this mean?</h2>
      <p>You and your team cannot log in or use EasyAI until the workspace is reactivated. Your data is safe and will not be deleted.</p>
    </div>

    <p>If you believe this is a mistake or need assistance, please contact our support team:</p>
    <a href="mailto:{{ $supportEmail }}" class="btn">Contact Support</a>

    <p style="margin-top:20px; font-size:13px; color:#64748b;">
      You can also reply directly to this email — we're happy to help.
    </p>
  </div>
  <div class="footer">
    {{ $appName }} &mdash; {{ now()->format('Y') }} &mdash;
    <a href="{{ $appUrl }}" style="color:#6366f1;">{{ $appUrl }}</a>
  </div>
</div>
</body>
</html>