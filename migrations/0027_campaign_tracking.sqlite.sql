-- Migration 0027: eventos ligados à campanha/oferta/página e domínio de publicação da campanha
ALTER TABLE conversion_events ADD COLUMN offer_id INTEGER DEFAULT NULL;
ALTER TABLE conversion_events ADD COLUMN campaign_id INTEGER DEFAULT NULL;
ALTER TABLE conversion_events ADD COLUMN funnel_id INTEGER DEFAULT NULL;
CREATE INDEX IF NOT EXISTS idx_events_campaign_created ON conversion_events (campaign_id, event_name, created_at);
ALTER TABLE campaigns ADD COLUMN public_domain_id INTEGER DEFAULT NULL;
