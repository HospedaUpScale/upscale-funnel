ALTER TABLE campaigns ADD COLUMN primary_funnel_id INTEGER DEFAULT NULL;
UPDATE campaigns SET primary_funnel_id = (SELECT MAX(f.id) FROM funnels f WHERE f.campaign_id = campaigns.id AND f.workspace_id = campaigns.workspace_id AND f.status = 'published');
