<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 16);
            $table->date('transaction_date');
            $table->decimal('shares', 18, 6)->nullable();
            $table->decimal('price_per_share', 18, 6)->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['portfolio_id', 'transaction_date']);
            $table->index(['portfolio_id', 'instrument_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_transactions');
    }
};
