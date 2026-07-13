<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layouts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('preview_path')->nullable();
            $table->json('safe_area_overlay')->nullable();
            $table->string('proportion_ratio');
            $table->foreignId('status_id')->constrained('layout_statuses');
            $table->timestamps();

            $table->index('status_id', 'layouts_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layouts');
    }
};
