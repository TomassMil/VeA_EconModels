<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('currency', 3)->default('USD');
            $table->decimal('free_capital', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('portfolio_instrument', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_invested', 15, 2)->default(0);
            $table->decimal('shares', 15, 6)->default(0);
            $table->timestamps();

            $table->unique(['portfolio_id', 'instrument_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_instrument');
        Schema::dropIfExists('portfolios');
    }
};
