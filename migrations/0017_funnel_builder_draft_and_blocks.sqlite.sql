-- Migration 0017: Campos para o Editor Visual de Funis (Estilo Elementor)
-- Separação rascunho vs publicado e persistência de blocos

ALTER TABLE funnels ADD COLUMN draft_content TEXT DEFAULT NULL;
ALTER TABLE funnels ADD COLUMN published_content TEXT DEFAULT NULL;
ALTER TABLE funnels ADD COLUMN blocks_config TEXT DEFAULT NULL;
ALTER TABLE funnels ADD COLUMN settings_config TEXT DEFAULT NULL;

-- Atualiza registros existentes para que draft_content e published_content recebam html_content inicial
UPDATE funnels SET draft_content = html_content WHERE draft_content IS NULL;
UPDATE funnels SET published_content = html_content WHERE status = 'published' AND published_content IS NULL;
