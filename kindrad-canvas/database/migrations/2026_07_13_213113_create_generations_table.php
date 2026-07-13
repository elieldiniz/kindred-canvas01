<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('status_id')->constrained('generation_statuses');
            $table->foreignId('provider_id')->nullable()->constrained('generation_providers');
            $table->text('prompt_snapshot');
            $table->json('constraints_snapshot');
            $table->string('idempotency_key')->unique();
            $table->string('result_path')->nullable();
            $table->string('result_mime_type')->nullable();
            $table->integer('result_width_px')->nullable();
            $table->integer('result_height_px')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('credits_charged')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status_id'], 'generations_project_status_idx');
            $table->index(['user_id', 'created_at'], 'generations_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
