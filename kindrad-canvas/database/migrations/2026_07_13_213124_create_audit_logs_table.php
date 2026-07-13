<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users');
            $table->foreignId('action_id')->constrained('audit_log_actions');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['actor_user_id', 'created_at'], 'audit_logs_actor_created_idx');
            $table->index(['target_type', 'target_id'], 'audit_logs_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
