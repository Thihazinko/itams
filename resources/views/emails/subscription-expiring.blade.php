<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Renewal Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2 style="color: #d9534f;">Subscription Renewal Reminder</h2>
    <p>Hello Admin,</p>
    <p>The following service is expiring in <strong>{{ $daysRemaining }} day(s)</strong>:</p>
    <table cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse; border-color: #ccc;">
        <tr><td><strong>Service Type</strong></td><td>{{ $subscription->service_type }}</td></tr>
        <tr><td><strong>Project</strong></td><td>{{ $subscription->project_name }}</td></tr>
        <tr><td><strong>Subscription</strong></td><td>{{ $subscription->subscription_name }}</td></tr>
        <tr><td><strong>Expire Date</strong></td><td>{{ $subscription->expire_date->format('Y-m-d') }}</td></tr>
        <tr><td><strong>Renewal Type</strong></td><td>{{ $subscription->renewal_type }}</td></tr>
        <tr><td><strong>Renewal Cost</strong></td><td>{{ $subscription->renewal_cost }}</td></tr>
        <tr><td><strong>Status</strong></td><td>{{ $subscription->renewal_status }}</td></tr>
    </table>
    <p>Please take action to renew or terminate this subscription before it expires.</p>
    <p>— ITAMS Notification System</p>
</body>
</html>
