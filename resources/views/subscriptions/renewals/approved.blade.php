<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation approved · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fb;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
    </style>
</head>
<body>
@php
    $isSecond = ($approverStep ?? null) === \App\Models\SubscriptionRenewal::APPROVER_SECOND;
    $recipientEmail = $isSecond ? $renewal->second_approver_email : $renewal->approver_email;
@endphp
<div class="container" style="max-width: 640px; margin: 64px auto;">
    <div class="card shadow-sm text-center">
        <div class="card-body py-5">
            <div class="mb-3" style="font-size: 64px; color: #16a34a;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h2 class="mb-2">{{ $isSecond ? 'Second approval recorded' : 'First approval recorded' }}</h2>
            <p class="text-muted">
                Thank you. The renewal for
                <strong>{{ $renewal->subscription->subscription_name }}</strong>
                (P.O. {{ $renewal->po_number }}) has been approved.
            </p>
            <p class="text-muted small">
                @if($isSecond)
                    Both approvers have signed off. An administrator will now finalise the renewal and
                    you will receive a confirmation email at <strong>{{ $recipientEmail }}</strong>.
                @else
                    The issuer will now forward this quotation to the second approver.
                    You will be copied on the final confirmation email at <strong>{{ $recipientEmail }}</strong>.
                @endif
            </p>

            @if(! $isSigned && auth()->check())
                <a href="{{ route('subscriptions.index') }}" class="btn btn-primary mt-2">
                    <i class="bi bi-arrow-left"></i> Back to Subscriptions
                </a>
            @endif
        </div>
    </div>
</div>
</body>
</html>
