<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('reason_id')->constrained('credit_transaction_reasons');
            $table->integer('delta');
            $table->integer('balance_after');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'credit_transactions_user_created_idx');
            $table->index(['reference_type', 'reference_id'], 'credit_transactions_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
