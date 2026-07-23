<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layouts', function (Blueprint $table): void {
            $table->text('prompt_fragment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('layouts', function (Blueprint $table): void {
            $table->dropColumn('prompt_fragment');
        });
    }
};
