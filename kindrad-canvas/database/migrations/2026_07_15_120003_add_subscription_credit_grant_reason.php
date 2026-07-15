<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('credit_transaction_reasons')
            ->where('slug', 'subscription_credit_grant')
            ->exists();

        if (! $exists) {
            DB::table('credit_transaction_reasons')->insert([
                'name' => 'Subscription Credit Grant',
                'slug' => 'subscription_credit_grant',
                'expected_sign' => '+',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('credit_transaction_reasons')
            ->where('slug', 'subscription_credit_grant')
            ->delete();
    }
};
