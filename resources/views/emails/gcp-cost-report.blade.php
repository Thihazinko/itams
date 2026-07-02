<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>GCP Cost Report</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.55;">

    <h2 style="color: #0d6efd; margin-bottom: .25rem;">
        Google Cloud Platform &mdash; {{ $currency }} Cost Report
    </h2>

    <p>Hi,</p>

    <p>
        Please find attached the <strong>{{ $currency }}</strong> GCP cost breakdown report for
        <strong>{{ $periodLabel }}</strong>.
    </p>

    <table cellpadding="6" cellspacing="0"
           style="border-collapse: collapse; border: 1px solid #e5e7eb; font-size: 13px; margin: 16px 0;">
        <tr><td style="background:#f1f5f9;"><strong>Currency</strong></td><td>{{ $currency }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Period</strong></td><td>{{ $periodLabel }}</td></tr>
        <tr><td style="background:#f1f5f9;"><strong>Breakdowns</strong></td><td>{{ $count }}</td></tr>
    </table>

    <p>The full cost table is attached as a PDF.</p>

    <p style="margin-top: 24px; color: #6b7280; font-size: 12px;">
        &mdash; {{ $appName }}
    </p>
</body>
</html>
