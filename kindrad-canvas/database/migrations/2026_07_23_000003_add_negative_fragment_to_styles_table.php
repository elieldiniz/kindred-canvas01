<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('styles', function (Blueprint $table): void {
            $table->text('negative_fragment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('styles', function (Blueprint $table): void {
            $table->dropColumn('negative_fragment');
        });
    }
};
