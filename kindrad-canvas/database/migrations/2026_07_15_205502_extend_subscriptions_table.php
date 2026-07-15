<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('subscription_plan_id')
                ->nullable()
                ->after('user_id')
                ->constrained('subscription_plans')
                ->nullOnDelete();

            $table->foreignId('pending_plan_id')
                ->nullable()
                ->after('subscription_plan_id')
                ->constrained('subscription_plans')
                ->nullOnDelete();

            $table->foreignId('status_id')
                ->nullable()
                ->after('stripe_status')
                ->constrained('subscription_statuses')
                ->nullOnDelete();

            $table->timestamp('current_period_start')->nullable()->after('trial_ends_at');
            $table->timestamp('current_period_end')->nullable()->after('current_period_start');
            $table->boolean('cancel_at_period_end')->default(false)->after('ends_at');

            $table->index('subscription_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropForeign(['pending_plan_id']);
            $table->dropForeign(['status_id']);
            $table->dropIndex(['subscription_plan_id']);
            $table->dropColumn([
                'subscription_plan_id',
                'pending_plan_id',
                'status_id',
                'current_period_start',
                'current_period_end',
                'cancel_at_period_end',
            ]);
        });
    }
};
