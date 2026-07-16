<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_published')
                ->default(true)
                ->after('first_generated_at')
                ->comment('When false, project is hidden from the public /explore feed.');
            $table->boolean('is_in_explore')
                ->default(true)
                ->after('is_published')
                ->comment('Free-tier per-project opt-out for the public gallery.');
            $table->unsignedBigInteger('remixed_from_project_id')
                ->nullable()
                ->after('is_in_explore');
            $table->foreign('remixed_from_project_id')
                ->references('id')->on('projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['remixed_from_project_id']);
            $table->dropColumn(['is_in_explore', 'is_published', 'remixed_from_project_id']);
        });
    }
};
