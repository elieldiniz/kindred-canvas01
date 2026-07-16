<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->dateTime('attempted_at');
            $table->string('reason')->default('unknown');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'attempted_at']);
            $table->index('stripe_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_failures');
    }
};
