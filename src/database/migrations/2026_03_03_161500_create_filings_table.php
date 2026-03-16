<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instrument_id');
            $table->date('period_end');
            $table->char('filing_type', 1);
            $table->smallInteger('fiscal_year')->nullable();
            $table->string('fiscal_period', 5)->nullable();
            $table->string('doc_type', 10)->nullable();
            $table->string('source_file')->nullable();
            $table->timestamps();

            $table->unique(['instrument_id', 'period_end', 'filing_type'], 'filings_instrument_period_type_unique');
            $table->index('period_end');
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filings');
    }
};