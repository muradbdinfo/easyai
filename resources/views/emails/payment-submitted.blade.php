<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrap { max-width: 580px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
  .header { background: #1e293b; padding: 24px 30px; }
  .header h1 { color: #fff; margin: 0; font-size: 20px; }
  .header span { color: #94a3b8; font-size: 13px; }
  .body { padding: 30px; color: #334155; }
  .body p { margin: 0 0 14px; line-height: 1.6; }
  .table { width: 100%; border-collapse: collapse; margin: 18px 0; }
  .table th { text-align: left; background: #f1f5f9; padding: 8px 12px; font-size: 12px; color: #64748b; text-transform: uppercase; }
  .table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
  .badge-cod { background: #fef3c7; color: #92400e; }
  .badge-sslcommerz { background: #dcfce7; color: #166534; }
  .badge-stripe { background: #ede9fe; color: #5b21b6; }
  .btn { display: inline-block; background: #6366f1; color: #fff; padding: 10px 22px; border-radius: 6px; text-decoration: none; font-size: 14px; margin-top: 10px; }
  .footer { background: #f8fafc; padding: 16px 30px; text-align: center; color: #94a3b8; font-size: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>New Payment Submitted</h1>
    <span>{{ $appName }} Admin Notification</span>
  </div>
  <div class="body">
    <p>A new payment has been submitted and requires your attention.</p>
    <table class="table">
      <tr><th>Field</th><th>Details</th></tr>
      <tr><td>Tenant</td><td><strong>{{ $payment->tenant->name }}</strong></td></tr>
      <tr><td>Plan</td><td>{{ $payment->plan->name }}</td></tr>
      <tr><td>Amount</td><td>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td></tr>
      <tr>
        <td>Method</td>
        <td>
          <span class="badge badge-{{ $payment->method }}">
            {{ strtoupper($payment->method) }}
          </span>
        </td>
      </tr>
      <tr><td>Status</td><td>{{ ucfirst($payment->status) }}</td></tr>
      <tr><td>Date</td><td>{{ $payment->created_at->format('d M Y, H:i') }}</td></tr>
      @if($payment->transaction_id)
      <tr><td>Transaction ID</td><td>{{ $payment->transaction_id }}</td></tr>
      @endif
    </table>
    @if($payment->method === 'cod')
    <p style="background:#fef3c7;padding:12px;border-radius:6px;font-size:13px;">
      ⚠️ This is a <strong>Cash on Delivery</strong> payment. Please verify and approve it in the admin panel.
    </p>
    <a href="{{ $adminUrl }}/payments" class="btn">Go to Admin Panel → Approve</a>
    @else
    <p style="background:#dcfce7;padding:12px;border-radius:6px;font-size:13px;">
      ✅ This payment was processed automatically via <strong>{{ strtoupper($payment->method) }}</strong> and the plan has been activated.
    </p>
    <a href="{{ $adminUrl }}/payments" class="btn">View in Admin Panel</a>
    @endif
  </div>
  <div class="footer">
    {{ $appName }} &mdash; Admin Notification &mdash; {{ now()->format('Y') }}
  </div>
</div>
</body>
</html>