-- Migration 0016: Offer Slugs and Publication
ALTER TABLE upsell_offers ADD COLUMN slug TEXT NULL;
ALTER TABLE upsell_offers ADD COLUMN is_published INTEGER NOT NULL DEFAULT 0;
ALTER TABLE upsell_offers ADD COLUMN published_at TEXT NULL;
ALTER TABLE upsell_offers ADD COLUMN draft_config TEXT NULL;
ALTER TABLE upsell_offers ADD COLUMN published_config TEXT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS idx_upsell_offers_slug ON upsell_offers(slug) WHERE slug IS NOT NULL;
