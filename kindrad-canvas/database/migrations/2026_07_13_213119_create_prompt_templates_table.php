<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('style_id')->constrained('styles');
            $table->foreignId('layout_id')->constrained('layouts');
            $table->text('body');
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->unique(
                ['product_id', 'category_id', 'style_id', 'layout_id'],
                'prompt_templates_4tuple_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_templates');
    }
};
