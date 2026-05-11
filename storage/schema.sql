CREATE TABLE IF NOT EXISTS symbols (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(255) NULL,
    market VARCHAR(20) NOT NULL DEFAULT 'BIST',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS price_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    symbol_id BIGINT UNSIGNED NOT NULL,
    timeframe VARCHAR(10) NOT NULL DEFAULT '1d',
    candle_time DATETIME NOT NULL,
    open_price DECIMAL(14,4) NOT NULL,
    high_price DECIMAL(14,4) NOT NULL,
    low_price DECIMAL(14,4) NOT NULL,
    close_price DECIMAL(14,4) NOT NULL,
    volume BIGINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY unique_symbol_timeframe_time (symbol_id, timeframe, candle_time),
    KEY idx_price_history_symbol_time (symbol_id, candle_time)
);

CREATE TABLE IF NOT EXISTS indicator_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    symbol_id BIGINT UNSIGNED NOT NULL,
    snapshot_date DATETIME NOT NULL,
    timeframe VARCHAR(10) NOT NULL DEFAULT '1d',
    rsi_14 DECIMAL(10,4) NULL,
    macd DECIMAL(10,4) NULL,
    macd_signal DECIMAL(10,4) NULL,
    ema_20 DECIMAL(10,4) NULL,
    ema_50 DECIMAL(10,4) NULL,
    bb_width DECIMAL(10,4) NULL,
    atr_14 DECIMAL(10,4) NULL,
    KEY idx_indicator_symbol_date (symbol_id, snapshot_date)
);

CREATE TABLE IF NOT EXISTS scanner_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    symbol_id BIGINT UNSIGNED NOT NULL,
    scanner_key VARCHAR(50) NOT NULL,
    score DECIMAL(10,2) NOT NULL,
    payload JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_scanner_results_symbol (symbol_id, scanner_key, created_at)
);

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS watchlists (
    user_id BIGINT UNSIGNED NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, symbol)
);

CREATE TABLE IF NOT EXISTS portfolios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    quantity DECIMAL(14,4) NOT NULL,
    average_cost DECIMAL(14,4) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_portfolio_user_symbol (user_id, symbol)
);

CREATE TABLE IF NOT EXISTS alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    alert_type VARCHAR(50) NOT NULL,
    threshold_value DECIMAL(14,4) NULL,
    channel VARCHAR(20) NOT NULL,
    cooldown_minutes INT UNSIGNED NOT NULL DEFAULT 15,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_alert_symbol_active (symbol, is_active)
);

CREATE TABLE IF NOT EXISTS notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_id BIGINT UNSIGNED NULL,
    channel VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notification_logs_alert (alert_id, created_at)
);

CREATE TABLE IF NOT EXISTS cron_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL,
    message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cron_logs_job (job_name, created_at)
);
