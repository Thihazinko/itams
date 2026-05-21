<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation rejected · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; min-height: 100vh;
               font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    </style>
</head>
<body>
<div class="container" style="max-width: 640px; margin: 64px auto;">
    <div class="card shadow-sm text-center">
        <div class="card-body py-5">
            <div class="mb-3" style="font-size: 64px; color: #dc2626;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <h2 class="mb-2">Quotation rejected</h2>
            <p class="text-muted">
                You rejected P.O. <strong>{{ $renewal->po_number }}</strong>
                for {{ $renewal->subscription->subscription_name }}.
            </p>
            @if($renewal->rejected_reason)
                <p class="text-start mt-3 mb-0">
                    <strong class="small text-muted">Your reason:</strong>
                </p>
                <div class="alert alert-secondary text-start">{{ $renewal->rejected_reason }}</div>
            @endif

            @if(! $isSigned && auth()->check())
                <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary mt-2">
                    <i class="bi bi-arrow-left"></i> Back to Subscriptions
                </a>
            @endif
        </div>
    </div>
</div>
</body>
</html>
