-- Migration 0018: Suporte a vínculo entre Funil/Pressel, Campanha e Template Selecionado
ALTER TABLE funnels ADD COLUMN campaign_id INT UNSIGNED NULL AFTER workspace_id;
ALTER TABLE funnels ADD COLUMN template_slug VARCHAR(64) NOT NULL DEFAULT 'default' AFTER blocks_config;

ALTER TABLE funnels ADD CONSTRAINT fk_funnels_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL;
CREATE INDEX idx_funnels_campaign ON funnels(campaign_id);
CREATE INDEX idx_funnels_template ON funnels(template_slug);
