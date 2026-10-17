-- A pressel (Pressel Premium, Landing Enxuta) fica na campanha, mas não é a página do link do upsell.
-- Campanhas cuja principal era uma pressel passam a usar a página de upsell publicada mais recente,
-- ou nenhuma (o link volta para a oferta nativa).
UPDATE campaigns c
JOIN funnels p ON p.id = c.primary_funnel_id AND (p.template_slug = 'pressel-premium' OR p.template_slug LIKE 'landing-enxuta%')
LEFT JOIN (SELECT campaign_id, workspace_id, MAX(id) AS page_id FROM funnels WHERE status = 'published' AND template_slug <> 'pressel-premium' AND template_slug NOT LIKE 'landing-enxuta%' AND template_slug <> 'funil-downsell' GROUP BY campaign_id, workspace_id) f ON f.campaign_id = c.id AND f.workspace_id = c.workspace_id
SET c.primary_funnel_id = f.page_id;
