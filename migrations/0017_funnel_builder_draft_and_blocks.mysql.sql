-- Migration 0017: Campos para o Editor Visual de Funis (Estilo Elementor)
-- Separação rascunho vs publicado e persistência de blocos para MySQL

ALTER TABLE funnels ADD COLUMN draft_content LONGTEXT DEFAULT NULL AFTER html_content;
ALTER TABLE funnels ADD COLUMN published_content LONGTEXT DEFAULT NULL AFTER draft_content;
ALTER TABLE funnels ADD COLUMN blocks_config LONGTEXT DEFAULT NULL AFTER published_content;
ALTER TABLE funnels ADD COLUMN settings_config LONGTEXT DEFAULT NULL AFTER blocks_config;

UPDATE funnels SET draft_content = html_content WHERE draft_content IS NULL;
UPDATE funnels SET published_content = html_content WHERE status = 'published' AND published_content IS NULL;
