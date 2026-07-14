<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('subject_type')->nullable();
            $table->text('custom_prompt')->nullable();
            $table->foreignId('pose_id')->nullable()->constrained('poses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropForeign(['pose_id']);
            $table->dropColumn(['subject_type', 'custom_prompt', 'pose_id']);
        });
    }
};
