<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->text('scene_prompt')->nullable();
            $table->text('emotion_hint')->nullable();
            $table->text('lighting_hint')->nullable();
            $table->text('color_palette')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn(['scene_prompt', 'emotion_hint', 'lighting_hint', 'color_palette']);
        });
    }
};
