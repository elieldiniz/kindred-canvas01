<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scene_presets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('prompt_fragment');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['category_id', 'slug'], 'scene_presets_category_slug_unique');
            $table->index('category_id', 'scene_presets_category_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scene_presets');
    }
};
