-- Migration 0020: Editor Visual (GrapesJS)
-- Preserva as paginas legadas em CodeMirror (editor_type='code') e marca as novas
-- paginas feitas no editor visual com editor_type='visual'. O JSON do GrapesJS
-- (projeto completo) fica em grapesjs_data; a versao publicada em grapesjs_published.
-- O HTML/CSS continua indo para html_content / published_content (resultado da
-- renderizacao), o que mantem a rota publica (funnel.php) funcionando sem mudancas.

ALTER TABLE funnels ADD COLUMN editor_type VARCHAR(20) NOT NULL DEFAULT 'code' AFTER template_slug;
ALTER TABLE funnels ADD COLUMN grapesjs_data LONGTEXT NULL AFTER editor_type;
ALTER TABLE funnels ADD COLUMN grapesjs_published LONGTEXT NULL AFTER grapesjs_data;
ALTER TABLE funnels ADD COLUMN project_format_version VARCHAR(10) NOT NULL DEFAULT '1' AFTER grapesjs_published;
ALTER TABLE funnels ADD COLUMN revision INT UNSIGNED NOT NULL DEFAULT 0 AFTER project_format_version;
