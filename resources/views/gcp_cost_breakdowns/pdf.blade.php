<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 10px; }
        h1 { font-size: 15px; margin: 0 0 2px; color: #0d6efd; }
        .sub { color: #6b7280; font-size: 10px; margin: 0 0 10px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { padding: 3px 6px; font-size: 10px; }
        .meta .k { background: #f1f5f9; font-weight: bold; width: 120px; }
        table.lines { width: 100%; border-collapse: collapse; }
        table.lines th, table.lines td { border: 1px solid #d1d5db; padding: 4px 6px; }
        table.lines thead th { background: #f1f5f9; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        tfoot td { background: #f8fafc; font-weight: bold; }
        .muted { color: #6b7280; }
    </style>
</head>
@php
    $yen = function ($v) {
        if ($v === null || $v === '') return '—';
        $s = number_format((float) $v, 6, '.', ',');
        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $usd = function ($v) {
        if ($v === null || $v === '') return '—';
        $s = number_format((float) $v, 6, '.', ',');
        return '$ ' . (str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s);
    };
    $totalJpy = (float) $breakdown->lines->sum('cost_jpy');
    $totalUsd = (float) $breakdown->lines->sum('cost_usd');
    $costCols = 1;

    $money = fn ($v) => $isJpy ? '¥ ' . $yen($v) : $usd($v);
    $subtotal = $isJpy ? $totalJpy : $totalUsd;
    $discountAmt = (float) ($breakdown->discount_amount ?? 0);
    $taxAmt = (float) ($breakdown->tax_amount ?? 0);
    $grandTotal = $breakdown->grandTotal($subtotal);
@endphp
<body>
    <h1>GCP Cost Breakdown &mdash; {{ $currency }}</h1>
    <p class="sub">{{ $breakdown->periodRange() }} &middot; {{ $appName }}</p>

    <table class="meta">
        <tr>
            <td class="k">Period</td><td>{{ $breakdown->periodRange() }}</td>
            <td class="k">Exchange Rate</td><td>{{ $breakdown->exchange_rate ? $yen($breakdown->exchange_rate) : '—' }}</td>
        </tr>
        <tr>
            <td class="k">Billing Account</td><td>{{ $breakdown->billing_account_name ?: '—' }}</td>
            <td class="k">Reported By</td><td>{{ $breakdown->reported_by ?: '—' }}</td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th style="width:26px;">No</th>
                <th>Project Name</th>
                <th>Usage</th>
                <th>Billing Account</th>
                <th>Project ID</th>
                <th>Usage Start</th>
                <th>Usage End</th>
                <th>Billing Card</th>
                <th>Card Setting</th>
                <th class="num">Cost ({{ $isJpy ? '¥' : '$' }})</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($breakdown->lines as $line)
                <tr>
                    <td class="muted">{{ $loop->iteration }}</td>
                    <td>{{ $line->project_name ?: '—' }}</td>
                    <td>{{ $line->usage ?: '—' }}</td>
                    <td>{{ $line->billing_account_name ?: '—' }}</td>
                    <td>{{ $line->project_id ?: '—' }}</td>
                    <td>{{ optional($line->usage_start_date)->format('Y-m-d') ?: '—' }}</td>
                    <td>{{ optional($line->usage_end_date)->format('Y-m-d') ?: '—' }}</td>
                    <td>{{ $line->billing_card ?: '—' }}</td>
                    <td>{{ $line->card_setting ?: '—' }}</td>
                    <td class="num">
                        @if($isJpy) {{ $line->cost_jpy !== null ? '¥ ' . $yen($line->cost_jpy) : '—' }}
                        @else {{ $usd($line->cost_usd) }} @endif
                    </td>
                    <td>{{ $line->status ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="center muted">No project lines recorded.</td></tr>
            @endforelse
        </tbody>
        @if($breakdown->lines->isNotEmpty())
        <tfoot>
            @if($breakdown->hasAdjustments())
            <tr>
                <td colspan="9" class="num">Subtotal</td>
                <td class="num">{{ $money($subtotal) }}</td>
                <td></td>
            </tr>
            @if($discountAmt != 0.0)
            <tr>
                <td colspan="9" class="num">Discount</td>
                <td class="num">&minus; {{ $money($discountAmt) }}</td>
                <td></td>
            </tr>
            @endif
            @if($taxAmt != 0.0)
            <tr>
                <td colspan="9" class="num">Tax</td>
                <td class="num">+ {{ $money($taxAmt) }}</td>
                <td></td>
            </tr>
            @endif
            <tr>
                <td colspan="9" class="num">Grand Total</td>
                <td class="num">{{ $money($grandTotal) }}</td>
                <td></td>
            </tr>
            @else
            <tr>
                <td colspan="9" class="num">Total Amount</td>
                <td class="num">{{ $money($subtotal) }}</td>
                <td></td>
            </tr>
            @endif
        </tfoot>
        @endif
    </table>
</body>
</html>
