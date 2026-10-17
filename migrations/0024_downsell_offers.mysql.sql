ALTER TABLE upsell_offers ADD COLUMN offer_role VARCHAR(16) NOT NULL DEFAULT 'upsell';
CREATE INDEX idx_offers_campaign_role ON upsell_offers(workspace_id, campaign_id, offer_role);
