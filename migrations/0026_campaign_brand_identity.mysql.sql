-- Migration 0026: identidade visual da campanha (tema aplicado em todos os modelos)
ALTER TABLE campaigns ADD COLUMN brand_identity TEXT NULL;
