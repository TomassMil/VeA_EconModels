<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('ticker', 32);
            $table->unsignedBigInteger('cik')->nullable()->index();
            $table->unsignedInteger('simfin_id')->nullable()->unique();
            $table->string('exchange', 16)->nullable();

            $table->unique(['ticker', 'exchange']); // safer long-term
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};
