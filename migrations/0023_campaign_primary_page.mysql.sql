ALTER TABLE campaigns ADD COLUMN primary_funnel_id INT UNSIGNED DEFAULT NULL;
UPDATE campaigns c LEFT JOIN (SELECT campaign_id, workspace_id, MAX(id) AS page_id FROM funnels WHERE status = 'published' GROUP BY campaign_id, workspace_id) f ON f.campaign_id = c.id AND f.workspace_id = c.workspace_id SET c.primary_funnel_id = f.page_id;
