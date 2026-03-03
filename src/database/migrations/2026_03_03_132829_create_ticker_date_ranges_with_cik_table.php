<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('ticker_date_ranges_with_cik', function (Blueprint $table) {
            $table->string('ticker', 16);
            $table->unsignedBigInteger('cik')->nullable();
            $table->date('start_date');
            $table->date('end_date');

            $table->index('ticker');
            $table->index('cik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticker_date_ranges_with_cik');
    }
};
