-- Migration 0016: Offer Slugs and Publication (MySQL)
ALTER TABLE upsell_offers 
  ADD COLUMN slug VARCHAR(191) NULL UNIQUE AFTER campaign_id,
  ADD COLUMN is_published TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active,
  ADD COLUMN published_at TIMESTAMP NULL AFTER is_published,
  ADD COLUMN draft_config LONGTEXT NULL AFTER theme_config,
  ADD COLUMN published_config LONGTEXT NULL AFTER draft_config;
