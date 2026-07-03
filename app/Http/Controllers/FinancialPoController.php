<?php

namespace App\Http\Controllers;

use App\Exports\FinancialPosExport;
use App\Models\FinancialPo;
use App\Models\FinancialReceipt;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class FinancialPoController extends Controller
{
    public function index(Request $request)
    {
        // Financial Management is a standalone register: POs are entered by hand
        // (see create/store) and no longer mirrored from Subscriptions or Licenses.
        $tab = $request->get('tab') === 'receipts' ? 'receipts' : 'pos';
        $currencies = array_keys(FinancialPo::CURRENCIES);
        $sources = array_keys(FinancialPo::SOURCES);

        // ---- Budget usage (driven by receipt dates) ----
        $year  = (int) ($request->get('year') ?: now()->year);
        $month = $request->get('month'); // '' / null = whole year
        $month = ($month === null || $month === '') ? null : (int) $month;

        // Per-currency total of renewal costs for the selected period. The figure
        // is the source Renewal Cost (PO total_amount), grouped by renewal date
        // (po_date) — so it always equals the Subscription values.
        $periodTotals = [];
        foreach ($currencies as $cur) {
            $q = FinancialPo::where('currency', $cur)->whereYear('po_date', $year);
            if ($month) {
                $q->whereMonth('po_date', $month);
            }
            $periodTotals[$cur] = (float) $q->sum('total_amount');
        }

        // Monthly breakdown for the selected year: [month => [currency => total]].
        $monthlyRows = FinancialPo::whereYear('po_date', $year)
            ->selectRaw('MONTH(po_date) as m, currency, SUM(total_amount) as total')
            ->groupBy('m', 'currency')
            ->get();

        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthly[$m] = array_fill_keys($currencies, 0.0);
        }
        foreach ($monthlyRows as $row) {
            $monthly[(int) $row->m][$row->currency] = (float) $row->total;
        }

        $yearTotals = array_fill_keys($currencies, 0.0);
        foreach ($monthly as $byCur) {
            foreach ($currencies as $cur) {
                $yearTotals[$cur] += $byCur[$cur];
            }
        }

        // Years that have any data, for the year selector.
        $years = FinancialPo::selectRaw('DISTINCT YEAR(po_date) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->all();
        if (! in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        // ---- PO register (the list itself) ----
        // The register lists POs by renewal date (po_date) in the selected
        // period and shows each one's Renewal Cost, so its amounts tie back to
        // the Monthly Breakdown / budget cards above.
        $query = FinancialPo::query()->withCount('receipts')->whereYear('po_date', $year);
        if ($month) {
            $query->whereMonth('po_date', $month);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }
        if (($cur = $request->get('currency')) && in_array($cur, $currencies, true)) {
            $query->where('currency', $cur);
        }
        if (($source = $request->get('source')) && in_array($source, $sources, true)) {
            $query->where('source', $source);
        }

        $pos = $query->orderByDesc('po_date')->orderByDesc('id')->paginate(20)->withQueryString();

        // Count of POs dated in the period (ignores search/filters).
        $poCount = FinancialPo::whereYear('po_date', $year)
            ->when($month, fn ($q) => $q->whereMonth('po_date', $month))
            ->count();

        // ---- Receipts tab: history, linked to the same POs as the Approved PO
        // table. Filtered by each receipt's linked PO date (po_date) and the same
        // period, so both tabs always show matching POs (the Receipts tab lists
        // exactly the receipts belonging to the POs in the Approved PO list). ----
        $poPeriod = function ($q) use ($year, $month) {
            $q->whereYear('po_date', $year);
            if ($month) {
                $q->whereMonth('po_date', $month);
            }
        };

        // When arriving from a PO's receipt link (?po=ID), narrow the Receipts tab
        // to just that PO's receipts (across all periods, since a PO's receipts may
        // fall in any month). Otherwise list receipts of POs dated in the period.
        $poFilter = null;
        if (($poFilterId = $request->get('po')) && ctype_digit((string) $poFilterId)) {
            $poFilter = FinancialPo::find($poFilterId);
        }

        $receiptsQuery = FinancialReceipt::with('financialPo');
        $receiptCountQuery = FinancialReceipt::query();

        if ($poFilter) {
            $receiptsQuery->where('financial_po_id', $poFilter->id);
            $receiptCountQuery->where('financial_po_id', $poFilter->id);
        } else {
            $receiptsQuery->whereHas('financialPo', $poPeriod);
            $receiptCountQuery->whereHas('financialPo', $poPeriod);
        }

        if ($rsearch = $request->get('rsearch')) {
            $receiptsQuery->where(function ($q) use ($rsearch) {
                $q->where('receipt_number', 'like', "%{$rsearch}%")
                  ->orWhereHas('financialPo', function ($p) use ($rsearch) {
                      $p->where('po_number', 'like', "%{$rsearch}%")
                        ->orWhere('subject', 'like', "%{$rsearch}%")
                        ->orWhere('vendor_name', 'like', "%{$rsearch}%");
                  });
            });
        }

        $receipts = $receiptsQuery->orderByDesc('receipt_date')->orderByDesc('id')
            ->paginate(20, ['*'], 'rpage')->withQueryString();

        $receiptCount = $receiptCountQuery->count();

        // Approved POs for the upload dropdown.
        $approvedPos = FinancialPo::orderBy('po_number')
            ->get(['id', 'po_number', 'subject', 'vendor_name', 'currency']);

        return view('financial_pos.index', compact(
            'tab', 'pos', 'poCount', 'currencies', 'sources',
            'year', 'month', 'years',
            'periodTotals', 'monthly', 'yearTotals',
            'receipts', 'receiptCount', 'approvedPos', 'poFilter'
        ));
    }

    /**
     * Form to add a one-time purchase order (e.g. a PC, UPS, hardware). Unlike
     * subscription / license POs, these are entered by hand and not synced.
     */
    public function create()
    {
        return view('financial_pos.create', [
            'currencies'    => array_keys(FinancialPo::CURRENCIES),
            'subscriptions' => \App\Models\Subscription::orderBy('subscription_name')
                ->get(['id', 'subscription_name', 'service_type', 'vendor_name', 'currency', 'renewal_cost']),
            // License & Contract records the PO can link to — permanent (never
            // expiring) licenses are excluded, as they have no renewal to bill.
            'licenses'      => \App\Models\LicenseContract::where(function ($q) {
                    $q->where('expire_permanent', false)->orWhereNull('expire_permanent');
                })
                ->orderBy('software_name')
                ->get(['id', 'software_name', 'vendor_name', 'currency', 'renewal_cost']),
        ]);
    }

    public function store(Request $request)
    {
        $source = $request->input('source', FinancialPo::SOURCE_MANUAL);
        if (! array_key_exists($source, FinancialPo::SOURCES)) {
            $source = FinancialPo::SOURCE_MANUAL;
        }

        $data = $this->validateManual($request);
        $data['source'] = $source;

        // A subscription / license PO links back to the chosen source record.
        if ($source === FinancialPo::SOURCE_SUBSCRIPTION) {
            $request->validate(
                ['subscription_id' => ['required', Rule::exists('subscriptions', 'id')]],
                ['subscription_id.required' => 'Please choose a subscription to link this purchase order to.',
                 'subscription_id.exists'   => 'The selected subscription no longer exists.']
            );
            $data['subscription_id'] = (int) $request->input('subscription_id');
        } elseif ($source === FinancialPo::SOURCE_LICENSE) {
            $request->validate(
                ['license_contract_id' => ['required', Rule::exists('licenses_contracts', 'id')]],
                ['license_contract_id.required' => 'Please choose a license & contract to link this purchase order to.',
                 'license_contract_id.exists'   => 'The selected license & contract no longer exists.']
            );
            $data['license_contract_id'] = (int) $request->input('license_contract_id');
        }

        $data['po_number']   = $data['po_number'] ?: FinancialPo::generatePoNumber();
        $data['created_by']  = $request->user()->name;
        $data['modified_by'] = $request->user()->name;

        $po = FinancialPo::create($data);

        ActivityLogger::log(
            action: 'created',
            description: "Added {$po->sourceMeta()['label']} PO {$po->po_number} ({$po->subject})",
            subject: $po,
        );

        return redirect()->route('financial-pos.show', $po)->with('success', 'Purchase order added.');
    }

    public function edit(FinancialPo $financialPo)
    {
        abort_unless($financialPo->isManual(), 403);

        return view('financial_pos.edit', [
            'po'         => $financialPo,
            'currencies' => array_keys(FinancialPo::CURRENCIES),
        ]);
    }

    /**
     * One-time (manual) POs are fully editable; pay-as-you-go POs allow only the
     * Renewal Cost to be adjusted. Every other source mirrors its origin record
     * and stays read-only.
     */
    public function update(Request $request, FinancialPo $financialPo)
    {
        if ($financialPo->isManual()) {
            $data = $this->validateManual($request, $financialPo);
            $data['po_number']   = $data['po_number'] ?: $financialPo->po_number;
            $data['modified_by'] = $request->user()->name;

            $financialPo->update($data);

            ActivityLogger::log(
                action: 'updated',
                description: "Updated PO {$financialPo->po_number} ({$financialPo->subject})",
                subject: $financialPo,
            );

            return redirect()->route('financial-pos.show', $financialPo)->with('success', 'Purchase order updated.');
        }

        abort_unless($financialPo->isPayAsYouGo(), 403);

        $data = $request->validate([
            'total_amount' => 'required|numeric|min:0',
        ]);

        $financialPo->update([
            'total_amount' => $data['total_amount'],
            'modified_by'  => $request->user()->name,
        ]);

        ActivityLogger::log(
            action: 'updated',
            description: "Updated renewal cost for PO {$financialPo->po_number} ({$financialPo->currency} " . number_format((float) $financialPo->total_amount, 2) . ')',
            subject: $financialPo,
        );

        return back()->with('success', 'Renewal cost updated.');
    }

    /**
     * Shared validation for one-time PO create/update. po_number is optional
     * (auto-generated when blank) and unique across all POs, including
     * soft-deleted ones, since the DB unique index spans them too.
     */
    protected function validateManual(Request $request, ?FinancialPo $ignore = null): array
    {
        return $request->validate([
            'po_number'    => ['nullable', 'string', 'max:255', Rule::unique('financial_pos', 'po_number')->ignore($ignore?->id)],
            'po_date'      => 'required|date',
            'subject'      => 'required|string|max:255',
            'vendor_name'  => 'nullable|string|max:255',
            'category'     => 'nullable|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'currency'     => ['required', Rule::in(array_keys(FinancialPo::CURRENCIES))],
            'notes'        => 'nullable|string|max:2000',
        ]);
    }

    /**
     * Dismiss a PO from the register. POs are mirrored from their sources on
     * every view, so this is a soft delete — the sync recognises the dismissed
     * PO (via withTrashed checks) and won't regenerate it.
     */
    public function destroy(Request $request, FinancialPo $financialPo)
    {
        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted PO {$financialPo->po_number} ({$financialPo->subject})",
            subject: $financialPo,
        );

        $financialPo->delete();

        return redirect()->route('financial-pos.index')->with('success', 'Purchase order deleted.');
    }

    public function show(FinancialPo $financialPo)
    {
        $financialPo->load(['receipts' => fn ($q) => $q->orderByDesc('receipt_date')->orderByDesc('id')]);

        return view('financial_pos.show', ['po' => $financialPo]);
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx') === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $ext = $format === \Maatwebsite\Excel\Excel::CSV ? 'csv' : 'xlsx';

        return Excel::download(new FinancialPosExport(), 'financial-pos-' . now()->format('Ymd-His') . '.' . $ext, $format);
    }
}
