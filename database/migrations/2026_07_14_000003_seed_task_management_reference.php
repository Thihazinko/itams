<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Category => task list, transcribed from "Task Management.xlsx".
     * (Two source typos corrected: "Comparision" and "Traininig".)
     */
    private array $structure = [
        'Monitoring' => [
            'Production Server Monitoring', 'Testing Server Monitoring', 'Internal Server Monitoring',
            'Network Monitoring', 'IT Asset Monitoring', 'PC Monitoring', 'Billing Monitoring',
        ],
        'Maintenance' => [
            'Production Server Maintenance', 'Testing Server Maintenance', 'Internal Server Maintenance',
            'Network Maintenance', 'IT Asset Maintenance', 'PC Maintenance',
        ],
        'Operation' => ['Network Operation', 'Internal Server Operation', 'Others'],
        'Project Support' => ['Release Support', 'Issue Support', 'Technical Support', 'Customer Support'],
        'Purchasing' => [
            'Quotation Inquiry', 'Vendor Comparison', 'Quotation Confirmation',
            'Order Confirmation', 'Order Follow up', 'Received Item Confirmation',
        ],
        'Meeting' => ['Management Meeting', 'Leader Meeting', 'Team Meeting', 'Project Meeting', 'Others'],
        'Management' => [
            'Task Management', 'Man Hour Management', 'Incident Management',
            'Change Management', 'Configuration Management', 'Financial Management',
        ],
        'Review' => ['Task Review', 'Configuration Review', 'Operation Review', 'Project Support Review', 'Management Review'],
        'Reporting' => ['Daily Reporting', 'Man Hour Reporting', 'Issue Reporting', 'Incident Reporting', 'Information Reporting'],
        'End User Support' => ['PC Hardware Issue', 'PC Software Issue', 'Network Issue', 'IT Asset Issue'],
        'QIS' => ['Presentation', 'Technical Training', 'Exam', 'Reporting'],
        'ISO Compliance' => ['9001', '27001'],
        'Improvement' => ['Technical Improvement', 'Others'],
    ];

    public function up(): void
    {
        // Guard so re-running (or a fresh seed on an already-populated DB) is a no-op.
        if (DB::table('task_categories')->exists()) {
            return;
        }

        $catOrder = 0;
        foreach ($this->structure as $category => $tasks) {
            $categoryId = DB::table('task_categories')->insertGetId([
                'name'       => $category,
                'sort_order' => ++$catOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $taskOrder = 0;
            foreach ($tasks as $task) {
                DB::table('task_items')->insert([
                    'task_category_id' => $categoryId,
                    'name'             => $task,
                    'sort_order'       => ++$taskOrder,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Tables are dropped by the schema migration; nothing to unwind here.
    }
};
