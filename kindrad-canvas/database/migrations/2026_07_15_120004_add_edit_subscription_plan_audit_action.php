<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('audit_log_actions')
            ->where('slug', 'edit_subscription_plan')
            ->exists();

        if (! $exists) {
            DB::table('audit_log_actions')->insert([
                'name' => 'Edit Subscription Plan',
                'slug' => 'edit_subscription_plan',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('audit_log_actions')
            ->where('slug', 'edit_subscription_plan')
            ->delete();
    }
};
