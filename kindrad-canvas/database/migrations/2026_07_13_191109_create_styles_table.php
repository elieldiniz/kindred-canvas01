<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('styles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('prompt_fragment');
            $table->string('thumbnail_path')->nullable();
            $table->foreignId('status_id')->constrained('style_statuses');
            $table->timestamps();

            $table->index('status_id', 'styles_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('styles');
    }
};
