<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sistēmas portfeļi (modeļu backtest rezultāti) — pieejami visiem lietotājiem,
     * neparādās personīgajos sarakstos, redzami kā zaļi punkti uz risk-vs-return scatter plot.
     *
     * Shēma:
     *   user_id    — nullable; NULL ja `is_system = true`
     *   is_system  — boolean default false
     */
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('user_id');
            $table->string('description', 500)->nullable()->after('name');
        });

        // user_id nullable — Postgres needs raw SQL since the FK uses constrained()
        Schema::table('portfolios', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn(['is_system', 'description']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
