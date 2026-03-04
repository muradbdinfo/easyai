<?php

namespace App\Services;

use App\Models\Payment;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generate(Payment $payment): string
    {
        $payment->load(['tenant', 'plan', 'user']);

        $invoiceNumber = $payment->invoice_number
            ?? $payment->generateInvoiceNumber();

        $html = $this->buildHtml($payment, $invoiceNumber);

        $mpdf = new Mpdf([
            'margin_top'    => 10,
            'margin_bottom' => 20,
            'margin_left'   => 15,
            'margin_right'  => 15,
            'format'        => 'A4',
            'tempDir'       => storage_path('app/mpdf-tmp'),
        ]);

        $mpdf->SetTitle('Invoice ' . $invoiceNumber);
        $mpdf->SetAuthor('EasyAI');
        $mpdf->WriteHTML($html);

        // Ensure directory exists
        Storage::makeDirectory('invoices');
        $path = storage_path('app/invoices/' . $invoiceNumber . '.pdf');

        $mpdf->Output($path, 'F');

        return 'invoices/' . $invoiceNumber . '.pdf';
    }

    private function buildHtml(Payment $payment, string $invoiceNumber): string
    {
        $date        = now()->format('M d, Y');
        $method      = strtoupper($payment->method);
        $status      = ucfirst($payment->status);
        $tenantName  = $payment->tenant->name ?? 'N/A';
        $planName    = $payment->plan->name ?? 'N/A';
        $planTokens  = number_format($payment->plan->monthly_token_limit ?? 0);
        $amount      = number_format($payment->amount, 2);
        $currency    = $payment->currency;
        $txId        = $payment->transaction_id ?? 'N/A';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:11px; color:#1e293b; }

  .header { background:#4f46e5; color:#fff; padding:24px; }
  .header .brand { font-size:22px; font-weight:700; letter-spacing:1px; }
  .header .tagline { font-size:10px; color:#c7d2fe; margin-top:4px; }

  .invoice-meta { background:#f8fafc; border-bottom:2px solid #e2e8f0; padding:16px 24px; }
  .invoice-meta table { width:100%; }
  .invoice-meta td { vertical-align:top; }
  .invoice-number { font-size:18px; font-weight:700; color:#4f46e5; }
  .invoice-date { font-size:10px; color:#64748b; margin-top:2px; }
  .invoice-status { display:inline-block; background:#dcfce7; color:#166534;
                    padding:3px 10px; border-radius:20px; font-size:9px;
                    font-weight:700; text-transform:uppercase; letter-spacing:0.5px; }

  .section { padding:20px 24px; }
  .section-title { font-size:10px; font-weight:700; text-transform:uppercase;
                   letter-spacing:0.5px; color:#64748b; margin-bottom:10px;
                   padding-bottom:6px; border-bottom:1px solid #e2e8f0; }

  .info-grid table { width:100%; }
  .info-grid td { padding:3px 0; font-size:11px; }
  .info-grid .label { color:#64748b; width:120px; font-weight:600; }
  .info-grid .value { color:#1e293b; }

  .plan-table { width:100%; border-collapse:collapse; margin-top:4px; }
  .plan-table th { background:#f1f5f9; padding:8px 12px; text-align:left;
                   font-size:10px; font-weight:700; color:#475569;
                   text-transform:uppercase; letter-spacing:0.4px; }
  .plan-table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; font-size:11px; }
  .plan-table .amount-cell { font-size:16px; font-weight:700; color:#4f46e5; text-align:right; }

  .total-box { background:#4f46e5; color:#fff; padding:14px 24px; text-align:right; }
  .total-label { font-size:10px; color:#c7d2fe; text-transform:uppercase; letter-spacing:0.5px; }
  .total-amount { font-size:24px; font-weight:700; margin-top:2px; }

  .footer { padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0;
            font-size:9px; color:#94a3b8; text-align:center; }
  .footer-fixed { position:fixed; bottom:0; left:0; right:0;
                  border-top:1px solid #e2e8f0; padding:5px 24px;
                  background:#f8fafc; font-size:8px; color:#94a3b8; }
  .footer-fixed table { width:100%; }
  .footer-fixed .left { text-align:left; }
  .footer-fixed .right { text-align:right; }
</style>
</head>
<body>

<div class="footer-fixed">
  <table>
    <tr>
      <td class="left">EasyAI — Confidential Invoice</td>
      <td class="right">Page {PAGENO} of {nbpg}</td>
    </tr>
  </table>
</div>

<div class="header">
  <div class="brand">EasyAI</div>
  <div class="tagline">Self-Hosted AI Workspace Platform</div>
</div>

<div class="invoice-meta">
  <table>
    <tr>
      <td>
        <div class="invoice-number">{$invoiceNumber}</div>
        <div class="invoice-date">Issue Date: {$date}</div>
      </td>
      <td style="text-align:right; vertical-align:middle;">
        <span class="invoice-status">{$status}</span>
      </td>
    </tr>
  </table>
</div>

<div class="section">
  <div class="section-title">Billed To</div>
  <div class="info-grid">
    <table>
      <tr>
        <td class="label">Workspace</td>
        <td class="value">{$tenantName}</td>
      </tr>
      <tr>
        <td class="label">Payment Method</td>
        <td class="value">{$method}</td>
      </tr>
      <tr>
        <td class="label">Transaction ID</td>
        <td class="value">{$txId}</td>
      </tr>
    </table>
  </div>
</div>

<div class="section">
  <div class="section-title">Plan Details</div>
  <table class="plan-table">
    <thead>
      <tr>
        <th>Plan</th>
        <th>Tokens / Month</th>
        <th>Billing Period</th>
        <th style="text-align:right;">Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>{$planName}</strong></td>
        <td>{$planTokens} tokens</td>
        <td>Monthly</td>
        <td class="amount-cell">{$currency} {$amount}</td>
      </tr>
    </tbody>
  </table>
</div>

<div class="total-box">
  <div class="total-label">Total Due</div>
  <div class="total-amount">{$currency} {$amount}</div>
</div>

<div class="footer">
  Thank you for choosing EasyAI. This is a computer-generated invoice.
</div>

</body>
</html>
HTML;
    }
}