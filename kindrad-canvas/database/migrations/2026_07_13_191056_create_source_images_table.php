<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->bigInteger('size_bytes');
            $table->integer('width_px')->nullable();
            $table->integer('height_px')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'source_images_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_images');
    }
};
