<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Renewal confirmed</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.55;">

    <h2 style="color: #16a34a; margin-bottom: .25rem;">
        Renewal confirmed
    </h2>

    <p>
        The renewal for
        <strong>{{ $renewal->subscription->subscription_name }}</strong>
        ({{ $renewal->subscription->service_type }} &mdash; {{ $renewal->subscription->project_name }})
        has been finalised.
    </p>

    <table cellpadding="6" cellspacing="0"
           style="border-collapse: collapse; border: 1px solid #e5e7eb; font-size: 13px; margin: 16px 0;">
        <tr><td style="background:#f1f5f9;"><strong>P.O. Number</strong></td><td>{{ $renewal->po_number }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Approved by</strong></td>
            <td>{{ $renewal->approver_name }} ({{ $renewal->approver_email }})</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Approved at</strong></td>
            <td>{{ optional($renewal->approved_at)->format('Y-m-d H:i') }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Final confirmed by</strong></td>
            <td>{{ $renewal->final_confirmed_by }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Final confirmed at</strong></td>
            <td>{{ optional($renewal->final_confirmed_at)->format('Y-m-d H:i') }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>New expiry</strong></td>
            <td><strong>{{ optional($renewal->subscription->expire_date)->format('Y-m-d') }}</strong></td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Total Amount</strong></td>
            <td>{{ $renewal->currency }} {{ number_format((float) $renewal->total_amount, 2) }}</td></tr>
    </table>

    <p>The signed Purchase Order PDF is attached for your records.</p>

    <p style="margin-top: 24px; color: #6b7280; font-size: 12px;">
        &mdash; {{ $appName }}
    </p>
</body>
</html>
