<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }

    .header { display: table; width: 100%; margin-bottom: 30px; border-bottom: 3px solid #2980b9; padding-bottom: 20px; }
    .header-left { display: table-cell; width: 50%; vertical-align: top; }
    .header-right { display: table-cell; width: 50%; vertical-align: top; text-align: right; }
    .company-name { font-size: 22px; font-weight: bold; color: #e67e22; letter-spacing: 1px; }
    .company-tagline { font-size: 10px; color: #666; margin-top: 3px; }
    .bill-title { font-size: 28px; font-weight: bold; color: #2c3e50; letter-spacing: 2px; }
    .bill-ref { font-size: 14px; color: #2980b9; font-weight: bold; margin-top: 5px; }
    .bill-date { font-size: 10px; color: #666; margin-top: 4px; }

    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-top: 6px; }
    .status-draft   { background: #95a5a6; color: #fff; }
    .status-sent    { background: #3498db; color: #fff; }
    .status-paid    { background: #27ae60; color: #fff; }
    .status-overdue { background: #e74c3c; color: #fff; }

    .bill-section { display: table; width: 100%; margin-bottom: 25px; }
    .bill-to { display: table-cell; width: 55%; vertical-align: top; }
    .bill-from { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
    .section-label { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #2980b9; margin-bottom: 6px; }
    .bill-name { font-size: 14px; font-weight: bold; color: #2c3e50; }
    .bill-detail { font-size: 10px; color: #555; margin-top: 2px; line-height: 1.5; }

    .period-box { background: #eaf2f8; border: 1px solid #aed6f1; border-radius: 6px; padding: 10px 14px; display: inline-block; text-align: left; margin-top: 8px; }
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
    table.items tbody td.ref  { font-weight: bold; color: #2980b9; }
    table.items tfoot tr { background: #f8f9fa; }
    table.items tfoot td { padding: 8px 10px; font-size: 10px; }

    .totals { width: 260px; margin-left: auto; margin-bottom: 25px; }
    .totals table { width: 100%; border-collapse: collapse; }
    .totals table tr td { padding: 6px 10px; font-size: 11px; }
    .totals table tr td:last-child { text-align: right; }
    .totals-subtotal { border-top: 1px solid #ecf0f1; }
    .totals-total { background: #2c3e50; color: #fff; font-weight: bold; font-size: 13px; }
    .totals-total td { padding: 10px !important; border-radius: 4px; }

    .notes { background: #fafafa; border-left: 3px solid #2980b9; padding: 10px 14px; margin-bottom: 20px; font-size: 10px; color: #555; }
    .notes-label { font-weight: bold; color: #2c3e50; margin-bottom: 4px; }

    .bank-info { background: #f8f9fa; border: 1px solid #ecf0f1; border-radius: 6px; padding: 12px 14px; margin-bottom: 20px; }
    .bank-label { font-size: 9px; font-weight: bold; color: #2980b9; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .bank-detail { font-size: 10px; color: #333; line-height: 1.6; }

    .footer { border-top: 2px solid #ecf0f1; padding-top: 14px; display: table; width: 100%; }
    .footer-left { display: table-cell; width: 60%; vertical-align: top; }
    .footer-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
    .footer-label { font-size: 9px; font-weight: bold; color: #2980b9; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
    .footer-text { font-size: 9px; color: #777; line-height: 1.6; }
    .page-number { font-size: 9px; color: #aaa; margin-top: 6px; }
</style>
</head>
<body>

<!-- ═══ HEADER ═══════════════════════════════════════════════════════════ -->
<div class="header">
    <div class="header-left">
        <div class="company-name">🎈 {{ $settings->company_name ?? 'Booklix' }}</div>
        <div class="company-tagline">Hot Air Balloon Experiences · Morocco</div>
    </div>
    <div class="header-right">
        <div class="bill-title">TRANSPORT BILL</div>
        <div class="bill-ref">{{ $bill->bill_ref }}</div>
        <div class="bill-date">
            Date: {{ $bill->created_at->format('d/m/Y') }}<br>
            @if($bill->period_from && $bill->period_to)
            Period: {{ $bill->period_from->format('d/m/Y') }} — {{ $bill->period_to->format('d/m/Y') }}
            @endif
        </div>
        <div>
            <span class="status-badge status-{{ $bill->status }}">{{ ucfirst($bill->status) }}</span>
        </div>
    </div>
</div>

<!-- ═══ PAY TO / PERIOD ════════════════════════════════════════════════ -->
<div class="bill-section">
    <div class="bill-to">
        <div class="section-label">Pay To — Transport Company</div>
        <div class="bill-name">{{ $company->company_name }}</div>
        @if($company->contact_name)
        <div class="bill-detail">Contact: {{ $company->contact_name }}</div>
        @endif
        @if($company->address)
        <div class="bill-detail">{{ $company->address }}</div>
        @endif
        @if($company->email)
        <div class="bill-detail">{{ $company->email }}</div>
        @endif
        @if($company->phone)
        <div class="bill-detail">Phone: {{ $company->phone }}</div>
        @endif
    </div>
    <div class="bill-from">
        @if($bill->period_from && $bill->period_to)
        <div class="period-box">
            <div class="period-label">Billing Period</div>
            <div class="period-value">{{ $bill->period_from->format('M d, Y') }}</div>
            <div class="period-label" style="margin-top:4px;">To</div>
            <div class="period-value">{{ $bill->period_to->format('M d, Y') }}</div>
        </div>
        @endif
        @if($bill->paid_at)
        <div class="period-box" style="margin-top:8px; background:#eafaf1; border-color:#a9dfbf;">
            <div class="period-label" style="color:#27ae60;">Paid On</div>
            <div class="period-value" style="color:#27ae60;">{{ $bill->paid_at->format('d/m/Y') }}</div>
            @if($bill->payment_reference)
            <div class="bill-detail">Ref: {{ $bill->payment_reference }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<!-- ═══ LINE ITEMS ════════════════════════════════════════════════════════ -->
<table class="items">
    <thead>
        <tr>
            <th style="width:18%">Dispatch Ref</th>
            <th style="width:12%">Date</th>
            <th>Description</th>
            <th class="right" style="width:10%">PAX</th>
            <th class="right" style="width:10%">Vehicles</th>
            <th class="right" style="width:15%">Cost (MAD)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($bill->items as $item)
        <tr>
            <td class="ref">{{ $item->dispatch->dispatch_ref ?? '—' }}</td>
            <td>{{ $item->dispatch?->flight_date?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $item->description }}</td>
            <td class="right">{{ $item->dispatch?->total_pax ?? '—' }}</td>
            <td class="right">{{ $item->vehicles_used }}</td>
            <td class="right"><strong>MAD {{ number_format((float) $item->line_total, 2) }}</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- ═══ TOTALS ════════════════════════════════════════════════════════════ -->
<div class="totals">
    <table>
        <tr class="totals-subtotal">
            <td>Subtotal</td>
            <td>MAD {{ number_format((float) $bill->subtotal, 2) }}</td>
        </tr>
        @if($bill->tax_rate > 0)
        <tr>
            <td>Tax ({{ number_format($bill->tax_rate, 0) }}%)</td>
            <td>MAD {{ number_format((float) $bill->tax_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="totals-total">
            <td>TOTAL TO PAY</td>
            <td>MAD {{ number_format((float) $bill->total_amount, 2) }}</td>
        </tr>
    </table>
</div>

<!-- ═══ BANK DETAILS ══════════════════════════════════════════════════════ -->
@if($company->bank_name || $company->bank_account || $company->bank_iban)
<div class="bank-info">
    <div class="bank-label">Bank Payment Details</div>
    @if($company->bank_name)
    <div class="bank-detail">Bank: {{ $company->bank_name }}</div>
    @endif
    @if($company->bank_account)
    <div class="bank-detail">Account: {{ $company->bank_account }}</div>
    @endif
    @if($company->bank_iban)
    <div class="bank-detail">IBAN: {{ $company->bank_iban }}</div>
    @endif
</div>
@endif

<!-- ═══ NOTES ══════════════════════════════════════════════════════════════ -->
@if($bill->notes)
<div class="notes">
    <div class="notes-label">Notes</div>
    {{ $bill->notes }}
</div>
@endif

<!-- ═══ FOOTER ═════════════════════════════════════════════════════════════ -->
<div class="footer">
    <div class="footer-left">
        <div class="footer-label">Payment Information</div>
        <div class="footer-text">
            Please reference bill number <strong>{{ $bill->bill_ref }}</strong> in your payment records.
        </div>
    </div>
    <div class="footer-right">
        <div class="footer-label">{{ $settings->company_name ?? 'Booklix' }}</div>
        <div class="footer-text">
            🎈 Hot Air Balloon Experiences<br>
            Morocco
        </div>
        <div class="page-number">Generated {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

</body>
</html>
