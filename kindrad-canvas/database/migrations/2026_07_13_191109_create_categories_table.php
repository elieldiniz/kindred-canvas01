<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->foreignId('status_id')->constrained('category_statuses');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'slug'], 'categories_product_slug_unique');
            $table->index('status_id', 'categories_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
