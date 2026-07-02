<?php

namespace Database\Seeders;

use App\Models\GcpCostBreakdown;
use Illuminate\Database\Seeder;

class GcpCostBreakdownSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent: seed the May 2026 table from "JPY Cost Table.xlsx" only once.
        if (GcpCostBreakdown::whereDate('period_start', '2026-05-01')->exists()) {
            $this->command?->info('GCP Cost Breakdown already seeded — skipping.');
            return;
        }

        $breakdown = GcpCostBreakdown::create([
            'period_start'         => '2026-05-01',
            'period_end'           => '2026-05-31',
            'billing_account_name' => 'My Billing Account',
            'reported_by'          => 'Thiha Zin Ko',
            'exchange_rate'        => 159.555,
            'created_by'           => 'Seeder',
            'modified_by'          => 'Seeder',
        ]);

        // From "JPY Cost Table.xlsx" (sheet "JPY"). cost_jpy null = terminated.
        $lines = [
            ['Bamawl New HR', 'All servers and storage of Bamawl SHIN HR', 'bamawl-new-hr', '2026-04-30', '2026-05-31', 164475.907358, null],
            ['Chemical', 'Servers of Chemical customer', 'chemical-392004', '2026-04-30', '2026-05-31', 28995.000000, null],
            ['BCM External Servers', 'BCMM, BAMAWL, Ultra (Hompage)', 'bcm-external-servers', '2026-04-30', '2026-05-31', 9031.000000, null],
            ['Core System', "Core System's all servers", 'core-system-396904', '2026-04-30', '2026-05-31', 104072.000000, null],
            ['BCM Internal Servers', "Gitlab server for all project's source code", 'bcm-internal-servers', '2026-04-30', '2026-05-31', 14641.000000, null],
            ['jisedai', "Jisedai's Hompage server", 'jisedai', null, null, null, 'Terminated'],
            ['Looker (Service)', 'Looker Studio Pro', '-', null, null, null, 'Terminated'],
            ['testing-env-bamawl', "Bamawl Shin HR's staging bucket storage", 'testing-env-bamawl', '2026-04-30', '2026-05-30', 4.000000, null],
            ['careerapp', 'Storage bucket for recruitment system', 'careerapp-371106', '2026-04-30', '2026-05-30', 1.000000, null],
            ['RK Yangon Steel', 'Storage bucket for RK Yangon Steel', 'RK Yangon Steel', '2026-04-30', '2026-05-31', 10081.000000, null],
        ];

        $rate = (float) $breakdown->exchange_rate; // JPY per USD

        foreach ($lines as $i => [$project, $usage, $projectId, $start, $end, $cost, $status]) {
            $breakdown->lines()->create([
                'sort_order'           => $i + 1,
                'project_name'         => $project,
                'usage'                => $usage,
                'billing_account_name' => 'My Billing Account',
                'project_id'           => $projectId,
                'usage_start_date'     => $start,
                'usage_end_date'       => $end,
                'billing_card'         => '***3907',
                'card_setting'         => 'Primary Card',
                'cost_jpy'             => $cost,
                'cost_usd'             => ($cost !== null && $rate > 0) ? round($cost / $rate, 6) : null,
                'status'               => $status,
            ]);
        }

        $this->command?->info('Seeded GCP Cost Breakdown (May 2026) with ' . count($lines) . ' project lines.');
    }
}
