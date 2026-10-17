CREATE TABLE IF NOT EXISTS ai_generation_quota (
    bucket TEXT PRIMARY KEY,
    attempts INTEGER NOT NULL DEFAULT 0
);
INSERT OR IGNORE INTO ai_generation_quota (bucket, attempts) VALUES ('total', 0);
