<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order {{ $renewal->po_number }}</title>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 22px; letter-spacing: 4px; margin: 0 0 4px; color: #0d6efd; }
        .muted { color: #6b7280; }
        table.meta { width: 100%; margin-top: 12px; border-collapse: collapse; }
        table.meta td { padding: 4px 8px; vertical-align: top; }
        table.meta .label { color: #6b7280; width: 90px; }
        table.meta .right .label { text-align: right; }
        table.items { width: 100%; margin-top: 18px; border-collapse: collapse; }
        table.items th, table.items td { border: 1px solid #cbd5e1; padding: 8px; }
        table.items th { background: #f1f5f9; text-align: left; font-size: 11px; }
        .num { text-align: right; }
        .totals { width: 40%; margin-left: auto; margin-top: 8px; border-collapse: collapse; }
        .totals td { padding: 6px 8px; }
        .totals .grand { background: #0d6efd; color: #fff; font-weight: bold; font-size: 13px; }
        .note { margin-top: 16px; padding: 10px 12px; background: #fef3c7; border-left: 4px solid #f59e0b; font-size: 11px; }
        .sig { margin-top: 48px; width: 100%; }
        .sig td { width: 50%; border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 10px; color: #6b7280; text-align: center; }
        .footer { margin-top: 28px; text-align: center; color: #94a3b8; font-size: 10px; }
    </style>
</head>
<body>

<table style="width:100%">
    <tr>
        <td>
            <h1>PURCHASE ORDER</h1>
            <div class="muted">{{ $appName }}</div>
        </td>
        <td style="text-align:right; vertical-align: top;">
            <div><strong>P.O. No:</strong> {{ $renewal->po_number }}</div>
            <div><strong>Date:</strong> {{ $renewal->po_date->format('d M Y') }}</div>
            @if($renewal->reference)
                <div><strong>Reference:</strong> {{ $renewal->reference }}</div>
            @endif
        </td>
    </tr>
</table>

<hr style="border: none; border-top: 2px solid #0d6efd; margin: 12px 0;">

<table class="meta">
    <tr>
        <td class="label">Subject:</td>
        <td colspan="3"><strong>{{ $renewal->subject }}</strong></td>
    </tr>
    <tr>
        <td class="label">Vendor:</td>
        <td>{{ $renewal->vendor_company ?: '—' }}</td>
        <td class="label">Contact:</td>
        <td>{{ $renewal->vendor_name ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Phone/Email:</td>
        <td>{{ $renewal->vendor_phone_email ?: '—' }}</td>
        <td class="label">Issued by:</td>
        <td>{{ $renewal->created_by ?: '—' }}</td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width:32px;">No.</th>
            <th>Item</th>
            <th>Description</th>
            <th style="width:50px;" class="num">Qty</th>
            <th style="width:80px;" class="num">Unit Price</th>
            <th style="width:90px;" class="num">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td><strong>{{ $renewal->subscription->subscription_name }}</strong></td>
            <td>
                {{ $renewal->subscription->service_type }} &middot;
                {{ $renewal->subscription->project_name }}<br>
                <span class="muted">Renewal period: {{ $renewal->subscription->renewal_type }}</span><br>
                <span class="muted">Current expiry: {{ optional($renewal->subscription->expire_date)->format('Y-m-d') }}</span>
            </td>
            <td class="num">{{ $renewal->quantity }}</td>
            <td class="num">{{ number_format((float) $renewal->unit_price, 2) }}</td>
            <td class="num">{{ number_format((float) $renewal->total_amount, 2) }}</td>
        </tr>
    </tbody>
</table>

<table class="totals">
    <tr>
        <td class="muted">Subtotal</td>
        <td class="num">{{ $renewal->currency }} {{ number_format((float) $renewal->total_amount, 2) }}</td>
    </tr>
    <tr class="grand">
        <td>Grand Total</td>
        <td class="num">{{ $renewal->currency }} {{ number_format((float) $renewal->total_amount, 2) }}</td>
    </tr>
</table>

@if($renewal->notes)
    <div class="note">
        <strong>Note:</strong> {{ $renewal->notes }}
    </div>
@endif

<table class="sig">
    <tr>
        <td>Prepared by &mdash; {{ $renewal->created_by ?: '—' }}</td>
        <td>
            1st approver &mdash; {{ $renewal->approver_name }}
            @if($renewal->approved_at)
                <br><span class="muted">signed {{ $renewal->approved_at->format('Y-m-d') }}</span>
            @endif
        </td>
    </tr>
    @if($renewal->second_approver_name)
        <tr>
            <td></td>
            <td>
                2nd approver &mdash; {{ $renewal->second_approver_name }}
                @if($renewal->second_approved_at)
                    <br><span class="muted">signed {{ $renewal->second_approved_at->format('Y-m-d') }}</span>
                @endif
            </td>
        </tr>
    @endif
</table>

<div class="footer">
    Generated by {{ $appName }} on {{ now()->format('Y-m-d H:i') }} &middot; P.O. {{ $renewal->po_number }}
</div>

</body>
</html>
