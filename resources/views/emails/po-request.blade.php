<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order approval</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.55;">

    @php
        $isSecond = ($approverStep ?? 'first') === \App\Models\SubscriptionRenewal::APPROVER_SECOND;
        $stageLabel = $isSecond ? 'second-level approval' : 'first-level approval';
    @endphp

    <h2 style="color: #0d6efd; margin-bottom: .25rem;">
        Purchase Order &mdash; {{ $stageLabel }} needed
    </h2>

    <p>Hi {{ $recipientName ?? $renewal->approver_name }},</p>

    <p>
        A renewal quotation has been prepared for
        <strong>{{ $renewal->subscription->subscription_name }}</strong>
        ({{ $renewal->subscription->service_type }} &mdash; {{ $renewal->subscription->project_name }})
        and is awaiting your {{ $stageLabel }}.
    </p>

    @if($isSecond && $renewal->approved_at)
        <p style="background:#ecfdf5;border-left:4px solid #10b981;padding:10px 12px;font-size:13px;">
            <strong>First approver signed off:</strong>
            {{ $renewal->approver_name }} on {{ $renewal->approved_at->format('Y-m-d H:i') }}.
        </p>
    @endif

    <table cellpadding="6" cellspacing="0"
           style="border-collapse: collapse; border: 1px solid #e5e7eb; font-size: 13px; margin: 16px 0;">
        <tr><td style="background:#f1f5f9;"><strong>P.O. Number</strong></td><td>{{ $renewal->po_number }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Subject</strong></td><td>{{ $renewal->subject }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Current Expiry</strong></td>
            <td>{{ optional($renewal->subscription->expire_date)->format('Y-m-d') }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Quantity</strong></td><td>{{ $renewal->quantity }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Unit Price</strong></td>
            <td>{{ $renewal->currency }} {{ number_format((float) $renewal->unit_price, 2) }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Total Amount</strong></td>
            <td><strong>{{ $renewal->currency }} {{ number_format((float) $renewal->total_amount, 2) }}</strong></td></tr>
    </table>

    <p>The full Purchase Order is attached as a PDF. To review and respond, click the button below.</p>

    <p style="margin: 24px 0;">
        <a href="{{ $quotationUrl }}"
           style="background:#0d6efd;color:#fff;text-decoration:none;
                  padding:12px 20px;border-radius:6px;font-weight:600;display:inline-block;">
            Review &amp; Approve Quotation
        </a>
    </p>

    <p style="font-size: 12px; color: #6b7280;">
        Or copy this link into your browser:<br>
        <span style="word-break: break-all;">{{ $quotationUrl }}</span>
    </p>

    @if($renewal->notes)
        <p style="background:#fef3c7;border-left:4px solid #f59e0b;padding:10px 12px;font-size:13px;">
            <strong>Note from issuer:</strong> {{ $renewal->notes }}
        </p>
    @endif

    <p style="margin-top: 24px; color: #6b7280; font-size: 12px;">
        &mdash; {{ $appName }}
    </p>
</body>
</html>
