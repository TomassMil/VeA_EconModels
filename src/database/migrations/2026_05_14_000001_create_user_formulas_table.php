<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * User-saglabātās formulas priekš backtest wizard (Custom Formula stratēģijas).
     *
     * Katra formula:
     *   - Pieder noteiktam user_id
     *   - Ir vārds (unikāls per user)
     *   - Satur math izteiksmi (piem. "revenue / market_cap + 2 * eps")
     *   - top_n + apraksts kā opcijas
     */
    public function up(): void
    {
        Schema::create('user_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->text('formula');
            $table->integer('top_n')->default(20);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_formulas');
    }
};
