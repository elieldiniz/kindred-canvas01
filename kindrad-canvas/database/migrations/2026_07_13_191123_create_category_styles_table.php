<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_styles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('style_id')->constrained('styles');
            $table->timestamps();

            $table->unique(['category_id', 'style_id'], 'category_styles_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_styles');
    }
};
