<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('type');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at'], 'stripe_events_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');
    }
};
