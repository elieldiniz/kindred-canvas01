<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('style_layouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('style_id')->constrained('styles');
            $table->foreignId('layout_id')->constrained('layouts');
            $table->timestamps();

            $table->unique(['style_id', 'layout_id'], 'style_layouts_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('style_layouts');
    }
};
