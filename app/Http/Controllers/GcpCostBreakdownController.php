<?php

namespace App\Http\Controllers;

use App\Mail\GcpCostReport;
use App\Models\GcpCostBreakdown;
use App\Support\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GcpCostBreakdownController extends Controller
{
    public function index(Request $request)
    {
        $year  = (int) ($request->get('year') ?: now()->year);
        $month = $request->get('month'); // '' / null = whole year
        $month = ($month === null || $month === '') ? null : (int) $month;

        // USD / JPY tabs — a breakdown belongs to a tab based on which cost column
        // its lines fill: JPY when a yen cost is recorded, USD when only a dollar
        // cost is (no yen amount on that line).
        $tab = strtolower((string) $request->get('tab'));
        if (! in_array($tab, ['usd', 'jpy'], true)) {
            $tab = 'usd';
        }
        $currency = strtoupper($tab); // 'USD' | 'JPY'

        // Period filter (year + optional month) reused by the counts and listing.
        $period = function ($q) use ($year, $month) {
            $q->whereYear('period_start', $year);
            if ($month) {
                $q->whereMonth('period_start', $month);
            }
        };

        // Restrict to breakdowns having a line billed in the given currency.
        $forCurrency = fn ($q, $cur) => $q->whereHas('lines', function ($l) use ($cur) {
            $cur === 'JPY'
                ? $l->whereNotNull('cost_jpy')
                : $l->whereNotNull('cost_usd')->whereNull('cost_jpy');
        });

        $counts = [
            'usd' => $forCurrency(GcpCostBreakdown::query()->where($period), 'USD')->count(),
            'jpy' => $forCurrency(GcpCostBreakdown::query()->where($period), 'JPY')->count(),
        ];

        $query = GcpCostBreakdown::query()->withCount('lines')
            ->withSum('lines as total_cost_jpy', 'cost_jpy')
            ->withSum('lines as total_cost_usd', 'cost_usd')
            ->with('lines:id,gcp_cost_breakdown_id,sort_order,account_type')
            ->where($period);

        $forCurrency($query, $currency);

        $breakdowns = $query->orderByDesc('period_start')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        $gcpCount = GcpCostBreakdown::count();

        // Years that have any data, for the year selector.
        $years = GcpCostBreakdown::selectRaw('DISTINCT YEAR(period_start) as y')
            ->whereNotNull('period_start')
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->all();
        if (! in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        return view('gcp_cost_breakdowns.index', compact('breakdowns', 'gcpCount', 'years', 'year', 'month', 'tab', 'currency', 'counts'));
    }

    /**
     * Monthly cost comparison for a currency tab: projects (rows) × months of the
     * selected year (columns), each cell the project's cost for that month.
     */
    public function compare(Request $request)
    {
        $tab      = strtolower((string) $request->get('tab')) === 'usd' ? 'usd' : 'jpy';
        $currency = strtoupper($tab); // USD | JPY
        $isJpy    = $currency === 'JPY';
        $year     = (int) ($request->get('year') ?: now()->year);

        // Breakdowns for the year in this currency, chronological (months left → right).
        $breakdowns = GcpCostBreakdown::query()
            ->with('lines')
            ->whereYear('period_start', $year)
            ->whereHas('lines', function ($l) use ($isJpy) {
                $isJpy
                    ? $l->whereNotNull('cost_jpy')
                    : $l->whereNotNull('cost_usd')->whereNull('cost_jpy');
            })
            ->orderBy('period_start')->orderBy('id')
            ->get();

        // Columns = each breakdown (one month).
        $months = $breakdowns->map(fn ($b) => ['id' => $b->id, 'label' => $b->periodLabel()])->values();

        // Rows = projects; matrix[project][breakdownId] = summed cost in this currency.
        $matrix = [];      // project => [breakdownId => cost]
        $rowTotals = [];   // project => total across months
        $colTotals = [];   // breakdownId => month total
        $grand = 0.0;

        foreach ($breakdowns as $b) {
            $colTotals[$b->id] = 0.0;
            foreach ($b->lines as $line) {
                if ($isJpy) {
                    if ($line->cost_jpy === null) {
                        continue;
                    }
                    $cost = (float) $line->cost_jpy;
                } else {
                    // Mirror the USD tab: only dollar-billed lines (no yen amount).
                    if ($line->cost_usd === null || $line->cost_jpy !== null) {
                        continue;
                    }
                    $cost = (float) $line->cost_usd;
                }
                $project = trim((string) $line->project_name) ?: '(Unnamed)';
                $matrix[$project][$b->id] = ($matrix[$project][$b->id] ?? 0) + $cost;
                $rowTotals[$project] = ($rowTotals[$project] ?? 0) + $cost;
                $colTotals[$b->id] += $cost;
                $grand += $cost;
            }
        }

        // Biggest spenders first.
        uksort($matrix, fn ($a, $c) => $rowTotals[$c] <=> $rowTotals[$a]);

        // Years that have data, for the selector.
        $years = GcpCostBreakdown::selectRaw('DISTINCT YEAR(period_start) as y')
            ->whereNotNull('period_start')
            ->orderByDesc('y')->pluck('y')->map(fn ($y) => (int) $y)->all();
        if (! in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        return view('gcp_cost_breakdowns.compare', compact(
            'months', 'matrix', 'rowTotals', 'colTotals', 'grand', 'year', 'years', 'tab', 'currency', 'isJpy'
        ));
    }

    /**
     * Email a single month's cost breakdown as a PDF attachment to free-typed
     * To/Cc recipients. Triggered from the mail action on each index row.
     */
    public function mail(Request $request, GcpCostBreakdown $gcpCost)
    {
        $data = $request->validate([
            'tab'     => 'nullable|string',
            'subject' => 'required|string|max:255',
            'to'      => 'required|string',
            'cc'      => 'nullable|string',
        ]);

        $tab  = strtolower((string) ($data['tab'] ?? '')) === 'usd' ? 'usd' : 'jpy';
        $back = ['tab' => $tab];

        $to = $this->parseEmails($data['to']);
        $cc = $this->parseEmails($data['cc'] ?? null);

        if (empty($to)) {
            return redirect()->route('gcp-costs.index', $back)
                ->with('error', 'Enter at least one valid recipient (To) email address.');
        }

        $gcpCost->load('lines');
        $isJpy       = $gcpCost->lines->contains(fn ($l) => $l->cost_jpy !== null);
        $currency    = $isJpy ? 'JPY' : 'USD';
        $periodLabel = $gcpCost->periodLabel();

        $pdf = Pdf::loadView('gcp_cost_breakdowns.pdf', [
            'breakdown' => $gcpCost,
            'isJpy'     => $isJpy,
            'currency'  => $currency,
            'appName'   => config('app.name', 'ITAMS'),
        ])->setPaper('a4', 'landscape');

        $fileName = 'GCP-Cost-' . $currency . '-' . str_replace(' ', '-', $periodLabel) . '.pdf';

        try {
            Mail::to($to)->cc($cc)->send(new GcpCostReport(
                mailSubject: $data['subject'],
                pdfData: $pdf->output(),
                fileName: $fileName,
                currency: $currency,
                periodLabel: $periodLabel,
                count: $gcpCost->lines->count(),
            ));
        } catch (\Throwable $e) {
            ActivityLogger::log(
                action: 'mail_failed',
                description: "GCP {$currency} cost mail failed ({$periodLabel}): " . $e->getMessage(),
                subject: $gcpCost,
            );

            return redirect()->route('gcp-costs.index', $back)
                ->with('error', 'Could not send the email: ' . $e->getMessage());
        }

        ActivityLogger::log(
            action: 'mailed',
            description: "Emailed {$currency} GCP cost breakdown ({$periodLabel}) to " . implode(', ', $to),
            subject: $gcpCost,
        );

        return redirect()->route('gcp-costs.index', $back)
            ->with('success', "GCP cost breakdown for {$periodLabel} emailed to " . implode(', ', $to) . '.');
    }

    /** Split a free-typed recipient string into a de-duplicated list of valid emails. */
    protected function parseEmails(?string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $raw))
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()->values()->all();
    }

    public function create(Request $request)
    {
        $currency = strtoupper((string) $request->get('tab')) === 'USD' ? 'USD' : 'JPY';

        return view('gcp_cost_breakdowns.create', compact('currency'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $breakdown = DB::transaction(function () use ($data, $request) {
            $breakdown = GcpCostBreakdown::create([
                'period_start'         => $data['period_start'] ?? null,
                'period_end'           => $data['period_end'] ?? null,
                'billing_account_name' => $data['billing_account_name'] ?? null,
                'reported_by'          => $data['reported_by'] ?? null,
                'exchange_rate'        => $data['exchange_rate'] ?? null,
                'notes'                => $data['notes'] ?? null,
                'created_by'           => $request->user()->name,
                'modified_by'          => $request->user()->name,
            ]);

            $this->syncLines($breakdown, $data['lines'] ?? []);

            return $breakdown;
        });

        ActivityLogger::log(
            action: 'created',
            description: "Added GCP Cost Breakdown for {$breakdown->periodLabel()}",
            subject: $breakdown,
        );

        return redirect()->route('gcp-costs.show', $breakdown)->with('success', 'GCP cost breakdown added.');
    }

    /**
     * Copy a breakdown (header + all project lines) into a new one for the next
     * month, then open it for editing. Lets the user roll last month's project
     * list forward and just update the period and costs.
     */
    public function duplicate(Request $request, GcpCostBreakdown $gcpCost)
    {
        $gcpCost->load('lines');

        $copy = DB::transaction(function () use ($gcpCost, $request) {
            // Advance the billing period by one month for the "next" breakdown.
            $start = $gcpCost->period_start ? $gcpCost->period_start->copy()->addMonthNoOverflow() : null;
            $end   = $gcpCost->period_end ? $gcpCost->period_end->copy()->addMonthNoOverflow() : null;

            $copy = GcpCostBreakdown::create([
                'period_start'         => $start,
                'period_end'           => $end,
                'billing_account_name' => $gcpCost->billing_account_name,
                'reported_by'          => $gcpCost->reported_by,
                'exchange_rate'        => $gcpCost->exchange_rate,
                'notes'                => $gcpCost->notes,
                'created_by'           => $request->user()->name,
                'modified_by'          => $request->user()->name,
            ]);

            foreach ($gcpCost->lines as $line) {
                $copy->lines()->create([
                    'sort_order'           => $line->sort_order,
                    'account_type'         => $line->account_type,
                    'project_name'         => $line->project_name,
                    'usage'                => $line->usage,
                    'billing_account_name' => $line->billing_account_name,
                    'project_id'           => $line->project_id,
                    'usage_start_date'     => $line->usage_start_date,
                    'usage_end_date'       => $line->usage_end_date,
                    'billing_card'         => $line->billing_card,
                    'card_setting'         => $line->card_setting,
                    'cost_jpy'             => $line->cost_jpy,
                    'cost_usd'             => $line->cost_usd,
                    'status'               => $line->status,
                ]);
            }

            return $copy;
        });

        ActivityLogger::log(
            action: 'created',
            description: "Duplicated GCP Cost Breakdown to {$copy->periodLabel()}",
            subject: $copy,
        );

        return redirect()->route('gcp-costs.edit', $copy)
            ->with('success', 'Breakdown duplicated for ' . $copy->periodLabel() . ' — update the period and costs for the new month.');
    }

    public function show(GcpCostBreakdown $gcpCost)
    {
        $gcpCost->load('lines');

        return view('gcp_cost_breakdowns.show', ['breakdown' => $gcpCost]);
    }

    public function edit(GcpCostBreakdown $gcpCost)
    {
        $gcpCost->load('lines');

        return view('gcp_cost_breakdowns.edit', ['breakdown' => $gcpCost]);
    }

    public function update(Request $request, GcpCostBreakdown $gcpCost)
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($gcpCost, $data, $request) {
            $gcpCost->update([
                'period_start'         => $data['period_start'] ?? null,
                'period_end'           => $data['period_end'] ?? null,
                'billing_account_name' => $data['billing_account_name'] ?? null,
                'reported_by'          => $data['reported_by'] ?? null,
                'exchange_rate'        => $data['exchange_rate'] ?? null,
                'notes'                => $data['notes'] ?? null,
                'modified_by'          => $request->user()->name,
            ]);

            // Replace the lines wholesale — simplest way to honour add/remove/reorder
            // from the repeater form.
            $gcpCost->lines()->delete();
            $this->syncLines($gcpCost, $data['lines'] ?? []);
        });

        ActivityLogger::log(
            action: 'updated',
            description: "Updated GCP Cost Breakdown for {$gcpCost->periodLabel()}",
            subject: $gcpCost,
        );

        return redirect()->route('gcp-costs.show', $gcpCost)->with('success', 'GCP cost breakdown updated.');
    }

    public function destroy(GcpCostBreakdown $gcpCost)
    {
        ActivityLogger::log(
            action: 'deleted',
            description: "Deleted GCP Cost Breakdown for {$gcpCost->periodLabel()}",
            subject: $gcpCost,
        );

        $gcpCost->delete(); // lines cascade

        return redirect()->route('gcp-costs.index')->with('success', 'GCP cost breakdown deleted.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'period_start'             => 'nullable|date',
            'period_end'               => 'nullable|date|after_or_equal:period_start',
            'billing_account_name'     => 'nullable|string|max:255',
            'reported_by'              => 'nullable|string|max:255',
            'exchange_rate'            => 'nullable|numeric|min:0',
            'notes'                    => 'nullable|string|max:2000',
            'lines'                    => 'array',
            'lines.*.account_type'     => 'nullable|string|max:255',
            'lines.*.project_name'     => 'nullable|string|max:255',
            'lines.*.usage'            => 'nullable|string|max:1000',
            'lines.*.billing_account_name' => 'nullable|string|max:255',
            'lines.*.project_id'       => 'nullable|string|max:255',
            'lines.*.usage_start_date' => 'nullable|date',
            'lines.*.usage_end_date'   => 'nullable|date',
            'lines.*.billing_card'     => 'nullable|string|max:255',
            'lines.*.card_setting'     => 'nullable|string|max:255',
            'lines.*.cost_jpy'         => 'nullable|numeric',
            'lines.*.cost_usd'         => 'nullable|numeric',
            'lines.*.status'           => 'nullable|string|max:255',
        ]);
    }

    /**
     * Persist the line rows for a breakdown, skipping fully-empty rows and
     * numbering them by their submitted order.
     */
    protected function syncLines(GcpCostBreakdown $breakdown, array $lines): void
    {
        $order = 0;
        foreach ($lines as $line) {
            $hasContent = collect($line)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
            if (! $hasContent) {
                continue;
            }

            $breakdown->lines()->create([
                'sort_order'           => ++$order,
                'account_type'         => $line['account_type'] ?? null,
                'project_name'         => $line['project_name'] ?? null,
                'usage'                => $line['usage'] ?? null,
                'billing_account_name' => $line['billing_account_name'] ?? null,
                'project_id'           => $line['project_id'] ?? null,
                'usage_start_date'     => $line['usage_start_date'] ?? null,
                'usage_end_date'       => $line['usage_end_date'] ?? null,
                'billing_card'         => $line['billing_card'] ?? null,
                'card_setting'         => $line['card_setting'] ?? null,
                'cost_jpy'             => ($line['cost_jpy'] ?? '') === '' ? null : $line['cost_jpy'],
                'cost_usd'             => ($line['cost_usd'] ?? '') === '' ? null : $line['cost_usd'],
                'status'               => $line['status'] ?? null,
            ]);
        }
    }
}
