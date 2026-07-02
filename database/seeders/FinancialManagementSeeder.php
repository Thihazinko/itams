<?php

namespace Database\Seeders;

use App\Models\FinancialPo;
use App\Models\FinancialReceipt;
use App\Support\FinancialPoSync;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FinancialManagementSeeder extends Seeder
{
    // Marker used to make this seeder idempotent and easy to identify/remove.
    private const MARKER = 'Seeder';

    public function run(): void
    {
        if (FinancialReceipt::where('created_by', self::MARKER)->exists()) {
            $this->command?->info('Financial Management receipts already seeded — skipping.');
            return;
        }

        // POs are not created by hand anymore — mirror them from the linked
        // sources (approved Subscription renewals + Active/Pending License &
        // Contract records), then attach sample receipts so the budget views
        // have roughly a year of data across all three currencies.
        FinancialPoSync::run();

        $pos = FinancialPo::orderBy('id')->get();
        if ($pos->isEmpty()) {
            $this->command?->info('No linked POs found (seed Subscriptions/Licenses first) — nothing to attach receipts to.');
            return;
        }

        $methods = ['Bank transfer', 'Credit card', 'Cheque', 'Cash'];
        $months2026 = [1, 2, 3, 4, 5, 6]; // year-to-date spread for the default view
        $receiptNo = 0;
        $created = 0;

        foreach ($pos as $i => $po) {
            $cur = $po->currency;
            $amount = (float) $po->total_amount;
            $round = fn ($v) => $cur === 'USD' ? round($v, 2) : round($v);

            // Vary the payment state so the register shows fully-paid, partial,
            // and outstanding POs:  mode 3 = no receipts (outstanding).
            $mode = $i % 4;
            if ($mode === 3 || $amount <= 0) {
                continue;
            }

            // Most receipts land in 2026 (Jan–Jun); every 5th in late 2025 to
            // exercise the year selector.
            $base = ($i % 5 === 0)
                ? Carbon::create(2025, $i % 2 ? 11 : 12, 12)
                : Carbon::create(2026, $months2026[$i % count($months2026)], 12);

            if ($mode === 2) {
                // Partial: ~60% now, ~25% the following month (leaves a balance).
                $this->makeReceipt($po, $base, $round($amount * 0.60), $methods[$receiptNo++ % 4], ++$receiptNo);
                $this->makeReceipt($po, $base->copy()->addMonth(), $round($amount * 0.25), $methods[$receiptNo++ % 4], ++$receiptNo);
                $created += 2;
            } else {
                // Fully received.
                $this->makeReceipt($po, $base, $round($amount), $methods[$receiptNo++ % 4], ++$receiptNo);
                $created++;
            }
        }

        $this->command?->info("Seeded {$created} sample receipts across {$pos->count()} linked PO(s).");
    }

    private function makeReceipt(FinancialPo $po, Carbon $date, float $amount, string $method, int $n): void
    {
        $po->receipts()->create([
            'receipt_number' => 'RC-' . $po->id . '-' . $n,
            'receipt_date'   => $date,
            'paid_amount'    => $amount,
            'currency'       => $po->currency,
            'payment_method' => $method,
            'created_by'     => self::MARKER,
            'modified_by'    => self::MARKER,
        ]);
    }
}
