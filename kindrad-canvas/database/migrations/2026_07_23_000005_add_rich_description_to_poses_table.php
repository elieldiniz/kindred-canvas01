<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poses', function (Blueprint $table): void {
            $table->text('rich_description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('poses', function (Blueprint $table): void {
            $table->dropColumn('rich_description');
        });
    }
};
