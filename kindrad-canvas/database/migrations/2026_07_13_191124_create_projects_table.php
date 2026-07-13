<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->foreignId('style_id')->nullable()->constrained('styles');
            $table->foreignId('layout_id')->nullable()->constrained('layouts');
            $table->foreignId('mode_id')->nullable()->constrained('project_modes');
            $table->foreignId('status_id')->constrained('project_statuses');
            $table->string('title')->nullable();
            $table->json('inputs')->default('{}');
            $table->foreignId('source_image_id')->nullable()->constrained('source_images');
            $table->timestamp('first_generated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'deleted_at'], 'projects_user_active_idx');
            $table->index('status_id', 'projects_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
