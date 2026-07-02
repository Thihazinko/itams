<?php

namespace Database\Seeders;

use App\Models\FinancialPo;
use App\Models\FinancialReceipt;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class FinancialManagementSeeder extends Seeder
{
    // Marker used to make this seeder idempotent and easy to identify/remove.
    private const MARKER = 'Seeder';

    public function run(): void
    {
        if (FinancialPo::where('created_by', self::MARKER)->exists()) {
            $this->command?->info('Financial Management already seeded — skipping.');
            return;
        }

        // Financial Management is a standalone, manual-entry register (POs are no
        // longer mirrored from Subscriptions or Licenses). Seed a spread of one-time
        // purchase orders across all three currencies, then attach sample receipts
        // so the budget views have roughly a year of data.
        $pos = $this->seedManualPos();

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

        $this->command?->info("Seeded {$pos->count()} manual PO(s) and {$created} sample receipt(s).");
    }

    /**
     * Create a spread of one-time (manual) purchase orders across MMK / JPY / USD
     * and across late-2025 → mid-2026 dates, for the budget charts and year picker.
     *
     * @return Collection<int, FinancialPo>
     */
    private function seedManualPos(): Collection
    {
        // [subject, vendor, category, amount, currency]
        $samples = [
            ['Dell OptiPlex desktop batch', 'Dell Myanmar', 'Hardware', 1_200_000, 'MMK'],
            ['APC UPS units', 'Power Solutions', 'Hardware', 850_000, 'MMK'],
            ['Cisco switch replacement', 'NetGear Trading', 'Networking', 2_400_000, 'MMK'],
            ['Backup tape media', 'Storage Plus', 'Hardware', 95_000, 'MMK'],
            ['Office suite (offline invoice)', 'Global IT', 'Software', 3_600, 'USD'],
            ['MacBook Pro for design team', 'Apple Reseller', 'Hardware', 2_900, 'USD'],
            ['External SSD drives', 'Tech Depot', 'Hardware', 480, 'USD'],
            ['Server rack maintenance', 'JP Datacenter', 'Services', 180_000, 'JPY'],
            ['Firewall appliance', 'Tokyo Networks', 'Hardware', 320_000, 'JPY'],
            ['Conference room displays', 'Display World', 'Hardware', 240_000, 'JPY'],
        ];

        $pos = collect();

        foreach ($samples as $i => [$subject, $vendor, $category, $amount, $currency]) {
            $poDate = ($i % 5 === 0)
                ? Carbon::create(2025, $i % 2 ? 11 : 12, 10)
                : Carbon::create(2026, ($i % 6) + 1, 10);

            $pos->push(FinancialPo::create([
                'po_number'    => 'PO-SEED-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'po_date'      => $poDate,
                'subject'      => $subject,
                'vendor_name'  => $vendor,
                'category'     => $category,
                'total_amount' => $amount,
                'currency'     => $currency,
                'source'       => FinancialPo::SOURCE_MANUAL,
                'created_by'   => self::MARKER,
                'modified_by'  => self::MARKER,
            ]));
        }

        return $pos;
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
