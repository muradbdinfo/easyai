<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrap { max-width: 580px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
  .header { background: #6366f1; padding: 24px 30px; }
  .header h1 { color: #fff; margin: 0; font-size: 20px; }
  .header span { color: #c7d2fe; font-size: 13px; }
  .body { padding: 30px; color: #334155; }
  .body p { margin: 0 0 14px; line-height: 1.6; }
  .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin: 18px 0; font-size: 14px; }
  .info-box p { margin: 4px 0; }
  .btn { display: inline-block; background: #6366f1; color: #fff !important; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-size: 15px; font-weight: 600; margin: 8px 0 16px; }
  .link-box { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; word-break: break-all; font-size: 12px; color: #64748b; margin-top: 8px; }
  .footer { background: #f8fafc; padding: 16px 30px; text-align: center; color: #94a3b8; font-size: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>You're Invited!</h1>
    <span>{{ $appName }} Workspace Invitation</span>
  </div>
  <div class="body">
    <p>Hi there,</p>
    <p>
      <strong>{{ $invitation->inviter->name ?? 'An admin' }}</strong> has invited you to join
      <strong>{{ $invitation->tenant->name }}</strong> as a <strong>{{ ucfirst($invitation->role) }}</strong>.
    </p>
    <div class="info-box">
      <p><strong>Workspace:</strong> {{ $invitation->tenant->name }}</p>
      <p><strong>Role:</strong> {{ ucfirst($invitation->role) }}</p>
      <p><strong>Invited by:</strong> {{ $invitation->inviter->name ?? '—' }}</p>
      <p><strong>Expires:</strong> {{ $invitation->expires_at->format('d M Y, H:i') }}</p>
    </div>
    <p>Click the button below to accept:</p>
    <a href="{{ $invitation->invite_url }}" class="btn">Accept Invitation</a>
    <p style="font-size:13px;color:#94a3b8;">Or copy this link:</p>
    <div class="link-box">{{ $invitation->invite_url }}</div>
  </div>
  <div class="footer">
    <p>If you weren't expecting this invitation, you can ignore this email.</p>
    <p style="margin-top:4px;">© {{ date('Y') }} {{ $appName }}</p>
  </div>
</div>
</body>
</html>