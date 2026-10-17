CREATE TABLE IF NOT EXISTS ai_generation_quota (
    bucket VARCHAR(20) NOT NULL PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO ai_generation_quota (bucket, attempts) VALUES ('total', 0);
