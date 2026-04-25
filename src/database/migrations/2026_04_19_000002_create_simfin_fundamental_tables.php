<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('simfin_income_statement')) {
            Schema::create('simfin_income_statement', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('instrument_id');
                $table->string('source_type', 10)->default('general');
                $table->smallInteger('fiscal_year');
                $table->string('fiscal_period', 5);
                $table->date('report_date');
                $table->date('publish_date')->nullable();
                $table->date('restated_date')->nullable();
                $table->bigInteger('shares_basic')->nullable();
                $table->bigInteger('shares_diluted')->nullable();
                $table->double('revenue')->nullable();
                $table->double('cost_of_revenue')->nullable();
                $table->double('gross_profit')->nullable();
                $table->double('operating_expenses')->nullable();
                $table->double('sga')->nullable();
                $table->double('rd')->nullable();
                $table->double('depreciation_amortization')->nullable();
                $table->double('operating_income')->nullable();
                $table->double('non_operating_income')->nullable();
                $table->double('interest_expense_net')->nullable();
                $table->double('pretax_income_adj')->nullable();
                $table->double('abnormal_gains_losses')->nullable();
                $table->double('pretax_income')->nullable();
                $table->double('income_tax')->nullable();
                $table->double('income_continuing_ops')->nullable();
                $table->double('extraordinary_gains')->nullable();
                $table->double('net_income')->nullable();
                $table->double('net_income_common')->nullable();
                $table->double('provision_loan_losses')->nullable();
                $table->double('net_revenue_after_provisions')->nullable();
                $table->double('total_non_interest_expense')->nullable();
                $table->double('total_claims_losses')->nullable();
                $table->double('income_from_affiliates')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();

                $table->foreign('instrument_id')->references('id')->on('instruments')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('simfin_balance_sheet')) {
            Schema::create('simfin_balance_sheet', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('instrument_id');
                $table->string('source_type', 10)->default('general');
                $table->smallInteger('fiscal_year');
                $table->string('fiscal_period', 5);
                $table->date('report_date');
                $table->date('publish_date')->nullable();
                $table->date('restated_date')->nullable();
                $table->bigInteger('shares_basic')->nullable();
                $table->bigInteger('shares_diluted')->nullable();
                $table->double('cash_and_equivalents')->nullable();
                $table->double('accounts_receivable')->nullable();
                $table->double('inventories')->nullable();
                $table->double('total_current_assets')->nullable();
                $table->double('ppe_net')->nullable();
                $table->double('lt_investments')->nullable();
                $table->double('other_lt_assets')->nullable();
                $table->double('total_noncurrent_assets')->nullable();
                $table->double('total_assets')->nullable();
                $table->double('payables_accruals')->nullable();
                $table->double('short_term_debt')->nullable();
                $table->double('total_current_liabilities')->nullable();
                $table->double('long_term_debt')->nullable();
                $table->double('total_noncurrent_liabilities')->nullable();
                $table->double('total_liabilities')->nullable();
                $table->double('share_capital')->nullable();
                $table->double('treasury_stock')->nullable();
                $table->double('retained_earnings')->nullable();
                $table->double('total_equity')->nullable();
                $table->double('total_liabilities_equity')->nullable();
                $table->double('interbank_assets')->nullable();
                $table->double('net_loans')->nullable();
                $table->double('short_lt_investments')->nullable();
                $table->double('net_fixed_assets')->nullable();
                $table->double('total_deposits')->nullable();
                $table->double('preferred_equity')->nullable();
                $table->double('total_investments')->nullable();
                $table->double('insurance_reserves')->nullable();
                $table->double('policyholders_equity')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();

                $table->foreign('instrument_id')->references('id')->on('instruments')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('simfin_cashflow')) {
            Schema::create('simfin_cashflow', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('instrument_id');
                $table->string('source_type', 10)->default('general');
                $table->smallInteger('fiscal_year');
                $table->string('fiscal_period', 5);
                $table->date('report_date');
                $table->date('publish_date')->nullable();
                $table->date('restated_date')->nullable();
                $table->bigInteger('shares_basic')->nullable();
                $table->bigInteger('shares_diluted')->nullable();
                $table->double('net_income_starting')->nullable();
                $table->double('depreciation_amortization')->nullable();
                $table->double('non_cash_items')->nullable();
                $table->double('change_working_capital')->nullable();
                $table->double('change_receivables')->nullable();
                $table->double('change_inventories')->nullable();
                $table->double('change_payables')->nullable();
                $table->double('change_other')->nullable();
                $table->double('net_cash_operating')->nullable();
                $table->double('change_fixed_assets')->nullable();
                $table->double('net_change_lt_investment')->nullable();
                $table->double('net_cash_acquisitions')->nullable();
                $table->double('net_cash_investing')->nullable();
                $table->double('dividends_paid')->nullable();
                $table->double('cash_from_debt')->nullable();
                $table->double('cash_from_equity')->nullable();
                $table->double('net_cash_financing')->nullable();
                $table->double('net_change_cash')->nullable();
                $table->double('change_loans_interbank')->nullable();
                $table->double('net_change_investments')->nullable();
                $table->double('fx_effects')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();

                $table->foreign('instrument_id')->references('id')->on('instruments')->cascadeOnDelete();
            });
        }

        $this->ensureIndex('simfin_income_statement', 'idx_simfin_is_instrument_year', '(instrument_id, fiscal_year DESC)');
        $this->ensureIndex('simfin_income_statement', 'simfin_is_instr_year_period_uk', '(instrument_id, fiscal_year, fiscal_period)', unique: true);
        $this->ensureIndex('simfin_balance_sheet', 'idx_simfin_bs_instrument_year', '(instrument_id, fiscal_year DESC)');
        $this->ensureIndex('simfin_balance_sheet', 'simfin_bs_instr_year_period_uk', '(instrument_id, fiscal_year, fiscal_period)', unique: true);
        $this->ensureIndex('simfin_cashflow', 'idx_simfin_cf_instrument_year', '(instrument_id, fiscal_year DESC)');
    }

    private function ensureIndex(string $table, string $indexName, string $columns, bool $unique = false): void
    {
        $exists = collect(Schema::getConnection()
            ->select("SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]))
            ->isNotEmpty();

        if (!$exists) {
            $kind = $unique ? 'UNIQUE INDEX' : 'INDEX';
            Schema::getConnection()->statement("CREATE {$kind} {$indexName} ON {$table} {$columns}");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('simfin_cashflow');
        Schema::dropIfExists('simfin_balance_sheet');
        Schema::dropIfExists('simfin_income_statement');
    }
};
