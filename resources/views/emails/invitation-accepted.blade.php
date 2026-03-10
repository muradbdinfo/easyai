<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrap { max-width: 580px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
  .header { background: #059669; padding: 24px 30px; }
  .header h1 { color: #fff; margin: 0; font-size: 20px; }
  .header span { color: #a7f3d0; font-size: 13px; }
  .body { padding: 30px; color: #334155; }
  .body p { margin: 0 0 14px; line-height: 1.6; }
  .info-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px 20px; margin: 18px 0; font-size: 14px; }
  .info-box p { margin: 4px 0; color: #166534; }
  .btn { display: inline-block; background: #059669; color: #fff !important; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-size: 15px; font-weight: 600; margin: 8px 0; }
  .footer { background: #f8fafc; padding: 16px 30px; text-align: center; color: #94a3b8; font-size: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>✓ Invitation Accepted</h1>
    <span>{{ $appName }} — Team Update</span>
  </div>
  <div class="body">
    <p>Hi {{ $invitation->inviter->name ?? 'there' }},</p>
    <p>
      <strong>{{ $newMember->name }}</strong> has accepted your invitation and joined
      <strong>{{ $invitation->tenant->name }}</strong>.
    </p>
    <div class="info-box">
      <p><strong>Name:</strong> {{ $newMember->name }}</p>
      <p><strong>Email:</strong> {{ $newMember->email }}</p>
      <p><strong>Role:</strong> {{ ucfirst($invitation->role) }}</p>
      <p><strong>Joined:</strong> {{ now()->format('d M Y, H:i') }}</p>
    </div>
    <a href="{{ $appUrl }}/team" class="btn">View Team</a>
  </div>
  <div class="footer">
    <p>© {{ date('Y') }} {{ $appName }}</p>
  </div>
</div>
</body>
</html>