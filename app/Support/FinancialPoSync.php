<?php

namespace App\Support;

use App\Models\FinancialPo;
use App\Models\LicenseContract;
use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use Carbon\Carbon;

/**
 * Keeps the Financial Management register in sync with its source records.
 *
 * POs are never created by hand — they mirror approved Subscription renewals and
 * qualifying License & Contract records. Both syncs are insert-only and keyed by
 * the source id, so calling run() repeatedly is idempotent and never clobbers
 * receipts or other data already attached to a PO.
 */
class FinancialPoSync
{
    /** License & Contract statuses that should appear as POs. */
    public const LICENSE_STATUSES = ['Active', 'Pending'];

    public static function run(): void
    {
        self::syncSubscriptions();
        self::syncRenewedSubscriptions();
        self::syncPayAsYouGo();
        self::syncLicenses();
    }

    protected static function syncSubscriptions(): void
    {
        $approved = [SubscriptionRenewal::STATUS_APPROVED, SubscriptionRenewal::STATUS_FINAL];

        // withTrashed: a deleted (dismissed) PO still counts as "handled" so the
        // sync won't recreate it on the next view.
        $existing = FinancialPo::withTrashed()
            ->whereNotNull('subscription_renewal_id')
            ->pluck('subscription_renewal_id')->all();

        $renewals = SubscriptionRenewal::whereIn('status', $approved)
            ->when($existing, fn ($q) => $q->whereNotIn('id', $existing))
            ->get();

        foreach ($renewals as $renewal) {
            FinancialPo::create([
                'po_number'               => $renewal->po_number,
                'po_date'                 => $renewal->po_date,
                'subject'                 => $renewal->subject,
                'vendor_name'             => $renewal->vendor_company ?: $renewal->vendor_name,
                'category'                => 'Subscription',
                'total_amount'            => $renewal->total_amount,
                'currency'                => self::currency($renewal->currency),
                'source'                  => FinancialPo::SOURCE_SUBSCRIPTION,
                'subscription_renewal_id' => $renewal->id,
                'notes'                   => $renewal->notes,
                'created_by'              => 'System (subscription sync)',
            ]);
        }
    }

    /**
     * Subscriptions renewed by hand — by editing the Previous Renewal Date /
     * Expire Date on the Subscription instead of going through the formal PO
     * approval workflow — still represent a real renewal spend. Mirror that
     * renewal into a PO dated on the Previous Renewal Date so it shows up in
     * Financial Management for the month it was renewed.
     *
     * Keyed by (subscription_id, billing_month=previous_renewal_date), so this is
     * insert-only and re-runnable. Subscriptions renewed through the formal
     * workflow are skipped for the month they were finalised, since that renewal
     * already has its own approved PO (mirrored by syncSubscriptions()).
     */
    protected static function syncRenewedSubscriptions(): void
    {
        $subs = Subscription::where('renewal_type', '!=', 'Pay as you go')
            ->where('status', '!=', 'Terminated')
            ->whereNotNull('previous_renewal_date')
            ->get();

        if ($subs->isEmpty()) {
            return;
        }

        // Manual-renewal POs already created: $have[subscription_id]['Y-m-d'] = true.
        // withTrashed so a dismissed PO is not regenerated.
        $have = [];
        FinancialPo::withTrashed()
            ->where('source', FinancialPo::SOURCE_SUBSCRIPTION)
            ->whereNotNull('subscription_id')
            ->whereNotNull('billing_month')
            ->get(['subscription_id', 'billing_month'])
            ->each(function ($po) use (&$have) {
                $have[$po->subscription_id][$po->billing_month->format('Y-m-d')] = true;
            });

        // Months a subscription was finalised through the formal workflow — its
        // renewal already has an approved PO, so don't mirror it twice. finalise
        // stamps previous_renewal_date and final_confirmed_at in the same moment,
        // so their months always match.
        $formalMonths = [];
        SubscriptionRenewal::where('status', SubscriptionRenewal::STATUS_FINAL)
            ->whereNotNull('final_confirmed_at')
            ->get(['subscription_id', 'final_confirmed_at'])
            ->each(function ($r) use (&$formalMonths) {
                $formalMonths[$r->subscription_id][$r->final_confirmed_at->format('Y-m')] = true;
            });

        foreach ($subs as $sub) {
            $date = Carbon::parse($sub->previous_renewal_date)->startOfDay();

            if (isset($have[$sub->id][$date->format('Y-m-d')])) {
                continue;
            }
            if (isset($formalMonths[$sub->id][$date->format('Y-m')])) {
                continue;
            }

            FinancialPo::create([
                'po_number'       => 'SUB-' . str_pad((string) $sub->id, 5, '0', STR_PAD_LEFT) . '-' . $date->format('Ymd'),
                'po_date'         => $date->copy(),
                'subject'         => $sub->subscription_name ?: $sub->project_name ?: 'Subscription renewal',
                'vendor_name'     => $sub->vendor_name,
                'category'        => 'Subscription',
                'total_amount'    => $sub->renewal_cost ?: 0,
                'currency'        => self::currency($sub->currency),
                'source'          => FinancialPo::SOURCE_SUBSCRIPTION,
                'subscription_id' => $sub->id,
                'billing_month'   => $date->copy(),
                'notes'           => $sub->remarks,
                'created_by'      => 'System (subscription renewal sync)',
            ]);
        }
    }

    /**
     * Pay-as-you-go subscriptions are billed by usage, so they accrue one PO for
     * every month they stay active — from the month they started up to the
     * current month. Terminated subscriptions stop accruing (existing POs stay).
     *
     * Keyed by (subscription_id, billing_month), so this is insert-only and never
     * overwrites a Renewal Cost the user has since edited on a generated PO.
     */
    protected static function syncPayAsYouGo(): void
    {
        $subs = Subscription::where('renewal_type', 'Pay as you go')
            ->where('status', '!=', 'Terminated')
            ->get();

        if ($subs->isEmpty()) {
            return;
        }

        // Existing months per subscription: $have[subscription_id]['Y-m'] = true.
        // withTrashed so a dismissed monthly PO is not regenerated.
        $have = [];
        FinancialPo::withTrashed()
            ->where('source', FinancialPo::SOURCE_SUBSCRIPTION_PAYG)
            ->whereNotNull('billing_month')
            ->get(['subscription_id', 'billing_month'])
            ->each(function ($po) use (&$have) {
                $have[$po->subscription_id][$po->billing_month->format('Y-m')] = true;
            });

        $currentMonth = Carbon::today()->startOfMonth();

        foreach ($subs as $sub) {
            $anchor = $sub->start_using_date ?: $sub->created_at;
            if (! $anchor) {
                continue;
            }

            $month = Carbon::parse($anchor)->startOfMonth();

            while ($month->lte($currentMonth)) {
                $key = $month->format('Y-m');

                if (! isset($have[$sub->id][$key])) {
                    FinancialPo::create([
                        'po_number'       => 'PAYG-' . str_pad((string) $sub->id, 5, '0', STR_PAD_LEFT) . '-' . $month->format('Ym'),
                        'po_date'         => $month->copy(),
                        'subject'         => $sub->subscription_name ?: $sub->project_name ?: 'Pay-as-you-go subscription',
                        'vendor_name'     => $sub->vendor_name,
                        'category'        => 'Subscription',
                        'total_amount'    => $sub->renewal_cost ?: 0,
                        'currency'        => self::currency($sub->currency),
                        'source'          => FinancialPo::SOURCE_SUBSCRIPTION_PAYG,
                        'subscription_id' => $sub->id,
                        'billing_month'   => $month->copy(),
                        'notes'           => $sub->remarks,
                        'created_by'      => 'System (pay-as-you-go sync)',
                    ]);
                }

                $month->addMonthNoOverflow();
            }
        }
    }

    protected static function syncLicenses(): void
    {
        $existing = FinancialPo::withTrashed()
            ->whereNotNull('license_contract_id')
            ->pluck('license_contract_id')->all();

        $licenses = LicenseContract::whereIn('status', self::LICENSE_STATUSES)
            ->when($existing, fn ($q) => $q->whereNotIn('id', $existing))
            ->get();

        foreach ($licenses as $license) {
            FinancialPo::create([
                'po_number'           => 'LC-' . str_pad((string) $license->id, 5, '0', STR_PAD_LEFT),
                'po_date'             => $license->last_renewal_date ?: $license->created_at,
                'subject'             => $license->software_name,
                'vendor_name'         => $license->vendor_name,
                'category'            => 'License & Contract',
                'total_amount'        => $license->renewal_cost ?: $license->previous_cost ?: 0,
                'currency'            => self::currency($license->currency),
                'source'              => FinancialPo::SOURCE_LICENSE,
                'license_contract_id' => $license->id,
                'notes'               => $license->remarks ?: $license->license_info,
                'created_by'          => 'System (license sync)',
            ]);
        }
    }

    protected static function currency(?string $code): string
    {
        return in_array($code, array_keys(FinancialPo::CURRENCIES), true) ? $code : 'MMK';
    }
}
