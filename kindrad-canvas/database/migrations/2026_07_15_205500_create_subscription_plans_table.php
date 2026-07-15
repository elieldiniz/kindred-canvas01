<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('credits_per_period');
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('BRL');
            $table->foreignId('interval_id')->constrained('subscription_intervals');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'id'], 'subscription_plans_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
