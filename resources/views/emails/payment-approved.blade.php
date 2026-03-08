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
  .plan-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 18px 22px; margin: 18px 0; }
  .plan-box h2 { margin: 0 0 8px; color: #0369a1; font-size: 18px; }
  .plan-box p { margin: 0; color: #0c4a6e; font-size: 14px; }
  .table { width: 100%; border-collapse: collapse; margin: 18px 0; }
  .table th { text-align: left; background: #f1f5f9; padding: 8px 12px; font-size: 12px; color: #64748b; text-transform: uppercase; }
  .table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
  .btn { display: inline-block; background: #6366f1; color: #fff; padding: 10px 22px; border-radius: 6px; text-decoration: none; font-size: 14px; margin-top: 10px; }
  .check { color: #16a34a; font-size: 16px; }
  .footer { background: #f8fafc; padding: 16px 30px; text-align: center; color: #94a3b8; font-size: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>🎉 Payment Approved!</h1>
    <span>Your {{ $appName }} workspace has been upgraded</span>
  </div>
  <div class="body">
    <p>Great news! Your payment has been approved and your workspace is now on the <strong>{{ $payment->plan->name }}</strong> plan.</p>
    <div class="plan-box">
      <h2>{{ $payment->plan->name }} Plan Active</h2>
      <p>{{ number_format($payment->plan->monthly_token_limit) }} tokens / month</p>
    </div>
    <table class="table">
      <tr><th>Field</th><th>Details</th></tr>
      <tr><td>Invoice</td><td>{{ $payment->invoice_number ?? 'Generating...' }}</td></tr>
      <tr><td>Plan</td><td>{{ $payment->plan->name }}</td></tr>
      <tr><td>Amount Paid</td><td><strong>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</strong></td></tr>
      <tr><td>Payment Method</td><td>{{ strtoupper($payment->method) }}</td></tr>
      <tr><td>Approved On</td><td>{{ now()->format('d M Y, H:i') }}</td></tr>
    </table>
    @if($payment->plan->features)
    <p><strong>What's included:</strong></p>
    <ul style="margin:0 0 16px;padding-left:20px;">
      @foreach((array)$payment->plan->features as $feature)
      <li class="check" style="margin-bottom:6px;color:#334155;">✅ {{ $feature }}</li>
      @endforeach
    </ul>
    @endif
    <a href="{{ $appUrl }}/billing" class="btn">View Billing Dashboard</a>
    <p style="margin-top:20px;font-size:13px;color:#64748b;">
      If you have any questions, contact us at {{ config('mail.from.address') }}.
    </p>
  </div>
  <div class="footer">
    {{ $appName }} &mdash; {{ now()->format('Y') }} &mdash;
    <a href="{{ $appUrl }}" style="color:#6366f1;">{{ $appUrl }}</a>
  </div>
</div>
</body>
</html>