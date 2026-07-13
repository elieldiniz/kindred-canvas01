<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('status_id')->constrained('product_statuses');
            $table->decimal('print_width_mm', 6, 2);
            $table->decimal('print_height_mm', 6, 2);
            $table->integer('min_dpi')->default(300);
            $table->decimal('safe_area_mm', 6, 2)->default(5.0);
            $table->foreignId('color_mode_id')->constrained('color_modes');
            $table->timestamps();

            $table->index('status_id', 'products_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
