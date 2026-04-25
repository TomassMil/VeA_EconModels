<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            if (!Schema::hasColumn('instruments', 'industry_id')) {
                $table->integer('industry_id')->nullable()->after('simfin_id');
            }
            if (!Schema::hasColumn('instruments', 'sector')) {
                $table->string('sector', 50)->nullable()->after('industry_id');
            }
            if (!Schema::hasColumn('instruments', 'industry')) {
                $table->string('industry', 100)->nullable()->after('sector');
            }
        });

        $existing = collect(Schema::getConnection()
            ->select("SELECT indexname FROM pg_indexes WHERE tablename = 'instruments'"))
            ->pluck('indexname')->all();

        Schema::table('instruments', function (Blueprint $table) use ($existing) {
            if (!in_array('instruments_sector_index', $existing, true)) {
                $table->index('sector');
            }
            if (!in_array('instruments_industry_index', $existing, true)) {
                $table->index('industry');
            }
        });
    }

    public function down(): void
    {
        Schema::table('instruments', function (Blueprint $table) {
            $table->dropIndex(['sector']);
            $table->dropIndex(['industry']);
            $table->dropColumn(['industry_id', 'sector', 'industry']);
        });
    }
};
