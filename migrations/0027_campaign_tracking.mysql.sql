-- Migration 0027: eventos ligados à campanha/oferta/página e domínio de publicação da campanha
ALTER TABLE conversion_events ADD COLUMN offer_id INT NULL, ADD COLUMN campaign_id INT NULL, ADD COLUMN funnel_id INT NULL;
CREATE INDEX idx_events_campaign_created ON conversion_events (campaign_id, event_name, created_at);
ALTER TABLE campaigns ADD COLUMN public_domain_id INT NULL;
