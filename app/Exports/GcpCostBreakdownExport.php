<?php

namespace App\Exports;

use App\Models\GcpCostBreakdown;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One month's GCP cost breakdown as a single worksheet: the project lines
 * followed by the Subtotal / Discount / Tax / Grand Total footer, mirroring the
 * PDF and the on-screen table. Costs are written in the breakdown's own currency
 * (JPY when any line carries a yen amount, otherwise USD).
 */
class GcpCostBreakdownExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected bool $isJpy;

    public function __construct(protected GcpCostBreakdown $breakdown)
    {
        $this->breakdown->loadMissing('lines');
        $this->isJpy = $this->breakdown->lines->contains(fn ($l) => $l->cost_jpy !== null);
    }

    public function title(): string
    {
        return $this->breakdown->periodLabel();
    }

    public function headings(): array
    {
        return [
            'No', 'Project Name', 'Usage', 'Billing Account', 'Project ID',
            'Usage Start', 'Usage End', 'Billing Card', 'Card Setting',
            'Cost (' . ($this->isJpy ? '¥' : '$') . ')', 'Status',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->breakdown->lines as $i => $line) {
            $cost = $this->isJpy ? $line->cost_jpy : $line->cost_usd;

            $rows[] = [
                $i + 1,
                $line->project_name ?: '',
                $line->usage ?: '',
                $line->billing_account_name ?: '',
                $line->project_id ?: '',
                optional($line->usage_start_date)->format('Y-m-d') ?: '',
                optional($line->usage_end_date)->format('Y-m-d') ?: '',
                $line->billing_card ?: '',
                $line->card_setting ?: '',
                $cost === null ? '' : (float) $cost,
                $line->status ?: '',
            ];
        }

        $subtotal    = $this->isJpy ? $this->breakdown->totalCostJpy() : $this->breakdown->totalCostUsd();
        $discountAmt = (float) ($this->breakdown->discount_amount ?? 0);
        $taxAmt      = (float) ($this->breakdown->tax_amount ?? 0);

        // Footer: mirror the PDF — full Subtotal / Discount / Tax / Grand Total
        // when adjustments exist, otherwise a single Total Amount line.
        if ($this->breakdown->hasAdjustments()) {
            $rows[] = $this->footerRow('Subtotal', $subtotal);
            if ($discountAmt != 0.0) {
                $rows[] = $this->footerRow('Discount', -$discountAmt);
            }
            if ($taxAmt != 0.0) {
                $rows[] = $this->footerRow('Tax', $taxAmt);
            }
            $rows[] = $this->footerRow('Grand Total', $this->breakdown->grandTotal($subtotal));
        } else {
            $rows[] = $this->footerRow('Total Amount', $subtotal);
        }

        return $rows;
    }

    /** A footer line: label in the Card Setting column, amount in the Cost column. */
    protected function footerRow(string $label, float $amount): array
    {
        return ['', '', '', '', '', '', '', '', $label, $amount, ''];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
