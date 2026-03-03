<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instrument_coverage', function (Blueprint $table) {
            $table->unsignedBigInteger('instrument_id');
            $table->date('start_date');
            $table->date('end_date');

            $table->primary('instrument_id');
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_coverage');
    }
};
