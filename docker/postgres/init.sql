-- Enable TimescaleDB extension
CREATE EXTENSION IF NOT EXISTS timescaledb;

-- ============================================================
-- INSTRUMENTS (same as MySQL)
-- ============================================================
CREATE TABLE IF NOT EXISTS instruments (
    id BIGSERIAL PRIMARY KEY,
    ticker VARCHAR(32) NOT NULL,
    cik BIGINT DEFAULT NULL,
    simfin_id INTEGER DEFAULT NULL,
    exchange VARCHAR(16) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL,
    company_name VARCHAR(255) DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS instruments_ticker_exchange_unique ON instruments(ticker, exchange);
CREATE UNIQUE INDEX IF NOT EXISTS instruments_simfin_id_unique ON instruments(simfin_id);
CREATE INDEX IF NOT EXISTS instruments_cik_index ON instruments(cik);
CREATE INDEX IF NOT EXISTS instruments_company_name_index ON instruments(company_name);

-- ============================================================
-- FILINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS filings (
    id BIGSERIAL PRIMARY KEY,
    instrument_id BIGINT NOT NULL REFERENCES instruments(id) ON DELETE CASCADE,
    period_end DATE NOT NULL,
    filing_type CHAR(1) NOT NULL,
    fiscal_year SMALLINT DEFAULT NULL,
    fiscal_period VARCHAR(5) DEFAULT NULL,
    doc_type VARCHAR(10) DEFAULT NULL,
    source_file VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS filings_instrument_period_type_unique ON filings(instrument_id, period_end, filing_type);
CREATE INDEX IF NOT EXISTS filings_period_end_index ON filings(period_end);

-- ============================================================
-- FINANCIAL_DATA
-- Note: TEXT columns in PostgreSQL have no practical size limit.
-- dimension and value_text are TEXT from the start — no ALTER needed later.
-- ============================================================
CREATE TABLE IF NOT EXISTS financial_data (
    id BIGSERIAL PRIMARY KEY,
    filing_id BIGINT NOT NULL REFERENCES filings(id) ON DELETE CASCADE,
    xbrl_tag VARCHAR(255) NOT NULL,
    context_date DATE DEFAULT NULL,
    period_start DATE DEFAULT NULL,
    period_end DATE DEFAULT NULL,
    dimension TEXT DEFAULT NULL,
    value_num DOUBLE PRECISION DEFAULT NULL,
    value_text TEXT DEFAULT NULL,
    unit VARCHAR(100) DEFAULT NULL,
    decimals SMALLINT DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS fd_filing_tag ON financial_data(filing_id, xbrl_tag);
CREATE INDEX IF NOT EXISTS fd_tag_context ON financial_data(xbrl_tag, context_date);
CREATE INDEX IF NOT EXISTS fd_tag_period ON financial_data(xbrl_tag, period_end);

-- ============================================================
-- PRICES_DAILY — TimescaleDB hypertable
-- This is where TimescaleDB shines: auto-partitioned by time
-- ============================================================
CREATE TABLE IF NOT EXISTS prices_daily (
    time DATE NOT NULL,
    instrument_id BIGINT NOT NULL REFERENCES instruments(id) ON DELETE CASCADE,
    open DOUBLE PRECISION,
    high DOUBLE PRECISION,
    low DOUBLE PRECISION,
    close DOUBLE PRECISION,
    adj_close DOUBLE PRECISION,
    volume BIGINT,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL
);

-- Convert to hypertable (auto-partitions by month)
-- The if_not_exists flag prevents errors on re-run
SELECT create_hypertable('prices_daily', 'time',
    chunk_time_interval => INTERVAL '1 month',
    if_not_exists => TRUE
);

-- Composite index for the most common query: "prices for ticker X in date range"
CREATE INDEX IF NOT EXISTS prices_instrument_time ON prices_daily(instrument_id, time DESC);

-- ============================================================
-- TICKER DATE RANGES (reference table)
-- ============================================================
CREATE TABLE IF NOT EXISTS ticker_date_ranges_with_cik (
    ticker VARCHAR(16) NOT NULL,
    cik BIGINT DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL
);
CREATE INDEX IF NOT EXISTS tdr_ticker_index ON ticker_date_ranges_with_cik(ticker);
CREATE INDEX IF NOT EXISTS tdr_cik_index ON ticker_date_ranges_with_cik(cik);

-- ============================================================
-- LARAVEL FRAMEWORK TABLES
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS sessions_user_id_index ON sessions(user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON sessions(last_activity);

CREATE TABLE IF NOT EXISTS cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INTEGER DEFAULT NULL,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS jobs_queue_index ON jobs(queue);

CREATE TABLE IF NOT EXISTS job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INTEGER NOT NULL,
    pending_jobs INTEGER NOT NULL,
    failed_jobs INTEGER NOT NULL,
    failed_job_ids TEXT NOT NULL,
    options TEXT DEFAULT NULL,
    cancelled_at INTEGER DEFAULT NULL,
    created_at INTEGER NOT NULL,
    finished_at INTEGER DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);