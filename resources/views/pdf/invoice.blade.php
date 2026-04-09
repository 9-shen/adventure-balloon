<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }

    .header { display: table; width: 100%; margin-bottom: 30px; border-bottom: 3px solid #e67e22; padding-bottom: 20px; }
    .header-left { display: table-cell; width: 50%; vertical-align: top; }
    .header-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
    .company-name { font-size: 22px; font-weight: bold; color: #e67e22; letter-spacing: 1px; }
    .company-tagline { font-size: 10px; color: #666; margin-top: 3px; }
    .invoice-title { font-size: 28px; font-weight: bold; color: #2c3e50; letter-spacing: 2px; }
    .invoice-ref { font-size: 14px; color: #e67e22; font-weight: bold; margin-top: 5px; }
    .invoice-date { font-size: 10px; color: #666; margin-top: 4px; }

    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-top: 6px; }
    .status-draft   { background: #95a5a6; color: #fff; }
    .status-sent    { background: #3498db; color: #fff; }
    .status-paid    { background: #27ae60; color: #fff; }
    .status-overdue { background: #e74c3c; color: #fff; }

    .bill-section { display: table; width: 100%; margin-bottom: 25px; }
    .bill-to { display: table-cell; width: 55%; vertical-align: top; }
    .bill-from { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
    .section-label { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #e67e22; margin-bottom: 6px; }
    .bill-name { font-size: 14px; font-weight: bold; color: #2c3e50; }
    .bill-detail { font-size: 10px; color: #555; margin-top: 2px; line-height: 1.5; }

    .period-box { background: #fef9f0; border: 1px solid #f0d9a0; border-radius: 6px; padding: 10px 14px; display: inline-block; text-align: left; margin-top: 8px; }
    .period-label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: 1px; }
    .period-value { font-size: 11px; font-weight: bold; color: #2c3e50; margin-top: 2px; }

    table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.items thead tr { background: #2c3e50; color: #fff; }
    table.items thead th { padding: 9px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; }
    table.items thead th.right { text-align: right; }
    table.items tbody tr:nth-child(even) { background: #f8f9fa; }
    table.items tbody tr:nth-child(odd)  { background: #ffffff; }
    table.items tbody td { padding: 8px 10px; font-size: 10px; color: #333; border-bottom: 1px solid #ecf0f1; vertical-align: top; }
    table.items tbody td.right { text-align: right; }
    table.items tbody td.ref  { font-weight: bold; color: #e67e22; }
    table.items tfoot tr { background: #f8f9fa; }
    table.items tfoot td { padding: 8px 10px; font-size: 10px; }

    .totals { width: 260px; margin-left: auto; margin-bottom: 25px; }
    .totals table { width: 100%; border-collapse: collapse; }
    .totals table tr td { padding: 6px 10px; font-size: 11px; }
    .totals table tr td:last-child { text-align: right; }
    .totals-subtotal { border-top: 1px solid #ecf0f1; }
    .totals-total { background: #2c3e50; color: #fff; font-weight: bold; font-size: 13px; }
    .totals-total td { padding: 10px !important; border-radius: 4px; }

    .notes { background: #fafafa; border-left: 3px solid #e67e22; padding: 10px 14px; margin-bottom: 20px; font-size: 10px; color: #555; }
    .notes-label { font-weight: bold; color: #2c3e50; margin-bottom: 4px; }

    .footer { border-top: 2px solid #ecf0f1; padding-top: 14px; display: table; width: 100%; }
    .footer-left { display: table-cell; width: 60%; vertical-align: top; }
    .footer-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
    .footer-label { font-size: 9px; font-weight: bold; color: #e67e22; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .footer-text { font-size: 9px; color: #777; line-height: 1.6; }
    .page-number { font-size: 9px; color: #aaa; margin-top: 6px; }
</style>
</head>
<body>

<!-- ═══ HEADER ═══════════════════════════════════════════════════════════ -->
<div class="header">
    <div class="header-left">
        <div class="company-name">🎈 Booklix</div>
        <div class="company-tagline">Hot Air Balloon Experiences · Morocco</div>
    </div>
    <div class="header-right">
        <div class="invoice-title">INVOICE</div>
        <div class="invoice-ref">{{ $invoice->invoice_ref }}</div>
        <div class="invoice-date">
            Date: {{ $invoice->created_at->format('d/m/Y') }}<br>
            Period: {{ $invoice->period_from->format('d/m/Y') }} — {{ $invoice->period_to->format('d/m/Y') }}
        </div>
        <div>
            <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
        </div>
    </div>
</div>

<!-- ═══ BILL TO / FROM ════════════════════════════════════════════════════ -->
<div class="bill-section">
    <div class="bill-to">
        <div class="section-label">Bill To</div>
        <div class="bill-name">{{ $partner->company_name }}</div>
        @if($partner->trade_name)
        <div class="bill-detail">{{ $partner->trade_name }}</div>
        @endif
        @if($partner->address)
        <div class="bill-detail">{{ $partner->address }}@if($partner->city), {{ $partner->city }}@endif</div>
        @endif
        @if($partner->tax_number)
        <div class="bill-detail">Tax #: {{ $partner->tax_number }}</div>
        @endif
        @if($partner->email)
        <div class="bill-detail">{{ $partner->email }}</div>
        @endif
    </div>
    <div class="bill-from">
        <div class="period-box">
            <div class="period-label">Invoice Period</div>
            <div class="period-value">{{ $invoice->period_from->format('M d, Y') }}</div>
            <div class="period-label" style="margin-top:4px;">To</div>
            <div class="period-value">{{ $invoice->period_to->format('M d, Y') }}</div>
        </div>
        @if($invoice->paid_at)
        <div class="period-box" style="margin-top:8px; background:#eafaf1; border-color:#a9dfbf;">
            <div class="period-label" style="color:#27ae60;">Paid On</div>
            <div class="period-value" style="color:#27ae60;">{{ $invoice->paid_at->format('d/m/Y') }}</div>
            @if($invoice->payment_reference)
            <div class="bill-detail">Ref: {{ $invoice->payment_reference }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<!-- ═══ LINE ITEMS ════════════════════════════════════════════════════════ -->
<table class="items">
    <thead>
        <tr>
            <th style="width:12%">Date</th>
            <th style="width:14%">Booking Ref</th>
            <th>Description</th>
            <th class="right" style="width:8%">Adults</th>
            <th class="right" style="width:8%">Children</th>
            <th class="right" style="width:13%">Unit Price</th>
            <th class="right" style="width:13%">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->flight_date->format('d/m/Y') }}</td>
            <td class="ref">{{ $item->booking->booking_ref ?? '—' }}</td>
            <td>{{ $item->description }}</td>
            <td class="right">{{ $item->adult_pax }}</td>
            <td class="right">{{ $item->child_pax }}</td>
            <td class="right">MAD {{ number_format($item->unit_price, 2) }}</td>
            <td class="right"><strong>MAD {{ number_format($item->line_total, 2) }}</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- ═══ TOTALS ════════════════════════════════════════════════════════════ -->
<div class="totals">
    <table>
        <tr class="totals-subtotal">
            <td>Subtotal</td>
            <td>MAD {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->tax_rate > 0)
        <tr>
            <td>Tax ({{ number_format($invoice->tax_rate, 0) }}%)</td>
            <td>MAD {{ number_format($invoice->tax_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="totals-total">
            <td>TOTAL DUE</td>
            <td>MAD {{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
    </table>
</div>

<!-- ═══ NOTES ════════════════════════════════════════════════════════════ -->
@if($invoice->notes)
<div class="notes">
    <div class="notes-label">Notes</div>
    {{ $invoice->notes }}
</div>
@endif

<!-- ═══ FOOTER ═══════════════════════════════════════════════════════════ -->
<div class="footer">
    <div class="footer-left">
        <div class="footer-label">Payment Terms</div>
        <div class="footer-text">
            Payment due within {{ $partner->payment_terms_days ?? 30 }} days of invoice date.<br>
            Please reference invoice number <strong>{{ $invoice->invoice_ref }}</strong> in your payment.
        </div>
    </div>
    <div class="footer-right">
        <div class="footer-label">Thank you for your business</div>
        <div class="footer-text">
            🎈 Booklix · Hot Air Balloon Experiences<br>
            Morocco
        </div>
        <div class="page-number">Generated {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

</body>
</html>
