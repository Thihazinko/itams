<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $moduleLabel }} renewal reminder</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <h2 style="color: #0d6efd; margin-bottom: .25rem;">
        {{ $moduleLabel }} renewal reminder &mdash; {{ $daysAhead }} day(s) out
    </h2>
    <p>
        The following {{ $count }} {{ Str::lower($moduleLabel) }}{{ $count === 1 ? '' : 's' }}
        will expire in <strong>exactly {{ $daysAhead }} day(s)</strong>.
        Please take action to renew, replace, or terminate before the expiry date.
    </p>

    <table cellpadding="8" cellspacing="0" border="1"
           style="border-collapse: collapse; border-color: #ccc; width: 100%; font-size: 13px;">
        <thead style="background: #f1f5f9;">
            <tr>
                <th align="left">{{ $moduleKey === 'subscriptions' ? 'Subscription' : 'Software' }}</th>
                <th align="left">{{ $moduleKey === 'subscriptions' ? 'Service Type' : 'License / Info' }}</th>
                <th align="left">Vendor</th>
                <th align="left">Expires</th>
                <th align="right">Renewal Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td><strong>{{ $row['name'] ?? '—' }}</strong></td>
                    <td>{{ $row['detail'] ?? '—' }}</td>
                    <td>{{ $row['vendor'] ?? '—' }}</td>
                    <td>{{ $row['expires'] ?? '—' }}</td>
                    <td align="right">
                        @if(!empty($row['cost']))
                            {{ $row['currency'] ?? '' }} {{ number_format((float) $row['cost'], 2) }}
                        @else
                            &mdash;
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 18px; color: #6b7280; font-size: 12px;">
        Sent automatically by {{ $appName }} based on the
        <strong>{{ $daysAhead }}-day</strong> reminder you selected in Notification Settings.
        You will receive separate digests at each other selected day-mark before each item expires.
    </p>

    <p style="color: #6b7280; font-size: 12px;">— {{ $appName }}</p>
</body>
</html>
