-- Migration 0018: Suporte a vínculo entre Funil/Pressel, Campanha e Template Selecionado
ALTER TABLE funnels ADD COLUMN campaign_id INTEGER NULL;
ALTER TABLE funnels ADD COLUMN template_slug TEXT DEFAULT 'default';

CREATE INDEX IF NOT EXISTS idx_funnels_campaign ON funnels(campaign_id);
CREATE INDEX IF NOT EXISTS idx_funnels_template ON funnels(template_slug);
