-- Migration 0020: Editor Visual (GrapesJS)
ALTER TABLE funnels ADD COLUMN editor_type TEXT NOT NULL DEFAULT 'code';
ALTER TABLE funnels ADD COLUMN grapesjs_data TEXT NULL;
ALTER TABLE funnels ADD COLUMN grapesjs_published TEXT NULL;
ALTER TABLE funnels ADD COLUMN project_format_version TEXT NOT NULL DEFAULT '1';
ALTER TABLE funnels ADD COLUMN revision INTEGER NOT NULL DEFAULT 0;
