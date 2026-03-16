<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices_daily', function (Blueprint $table) {
            $table->date('time');
            $table->unsignedBigInteger('instrument_id');
            $table->float('open')->nullable();
            $table->float('high')->nullable();
            $table->float('low')->nullable();
            $table->float('close')->nullable();
            $table->float('adj_close')->nullable();
            $table->bigInteger('volume')->nullable();
            $table->timestamps();

            $table->index(['instrument_id', 'time']);
            $table->foreign('instrument_id')->references('id')->on('instruments')->onDelete('cascade');
        });

        // Convert to TimescaleDB hypertable if extension is available
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT create_hypertable('prices_daily', 'time', chunk_time_interval => INTERVAL '1 month', if_not_exists => TRUE)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prices_daily');
    }
};