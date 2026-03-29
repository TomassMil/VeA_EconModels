<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('index_instrument', 'updated_at')) {
            Schema::table('index_instrument', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('index_instrument', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }
};
