<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('filing_id');
            $table->string('xbrl_tag');
            $table->date('context_date')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->text('dimension')->nullable();
            $table->float('value_num')->nullable();
            $table->text('value_text')->nullable();
            $table->string('unit', 100)->nullable();
            $table->smallInteger('decimals')->nullable();

            $table->index(['filing_id', 'xbrl_tag'], 'fd_filing_tag');
            $table->index(['xbrl_tag', 'context_date'], 'fd_tag_context');
            $table->index(['xbrl_tag', 'period_end'], 'fd_tag_period');
            $table->foreign('filing_id')->references('id')->on('filings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_data');
    }
};