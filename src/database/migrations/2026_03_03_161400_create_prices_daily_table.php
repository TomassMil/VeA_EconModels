<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices_daily', function (Blueprint $table) {
            $table->unsignedBigInteger('instrument_id');
            $table->date('date');

            $table->decimal('open', 18, 6)->nullable();
            $table->decimal('low', 18, 6)->nullable();
            $table->decimal('high', 18, 6)->nullable();
            $table->decimal('close', 18, 6)->nullable();
            $table->decimal('adj_close', 18, 6)->nullable();
            $table->decimal('dividend', 18, 6)->nullable();
            $table->unsignedBigInteger('volume')->nullable();
            $table->unsignedBigInteger('shares_outstanding')->nullable();

            $table->primary(['instrument_id', 'date']);
            $table->index('date');
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices_daily');
    }
};
