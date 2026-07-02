<?php

use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Renewal Status is now derived from Status + Expire Date. Backfill every
        // existing row to the computed value (DB update so the model saving hook
        // and its reminder_date recompute don't fire here).
        Subscription::query()->chunkById(200, function ($subscriptions) {
            foreach ($subscriptions as $subscription) {
                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update(['renewal_status' => $subscription->computeRenewalStatus()]);
            }
        });
    }

    public function down(): void
    {
        // No-op: prior hand-set renewal_status values cannot be restored.
    }
};
