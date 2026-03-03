<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('instruments', 'company_name')) {
            Schema::table('instruments', function (Blueprint $table) {
                $table->string('company_name')->nullable()->after('ticker');
            });
        }

        if (!$this->hasCompanyNameIndex()) {
            Schema::table('instruments', function (Blueprint $table) {
                $table->index('company_name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('instruments', 'company_name')) {
            return;
        }

        if ($this->hasCompanyNameIndex()) {
            Schema::table('instruments', function (Blueprint $table) {
                $table->dropIndex('instruments_company_name_index');
            });
        }

        Schema::table('instruments', function (Blueprint $table) {
            $table->dropColumn('company_name');
        });
    }

    private function hasCompanyNameIndex(): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'instruments')
            ->where('index_name', 'instruments_company_name_index')
            ->exists();
    }
};
