-- A pressel (Pressel Premium, Landing Enxuta) fica na campanha, mas não é a página do link do upsell.
-- Campanhas cuja principal era uma pressel passam a usar a página de upsell publicada mais recente,
-- ou nenhuma (o link volta para a oferta nativa).
UPDATE campaigns SET primary_funnel_id = (SELECT MAX(f.id) FROM funnels f WHERE f.campaign_id = campaigns.id AND f.workspace_id = campaigns.workspace_id AND f.status = 'published' AND f.template_slug <> 'pressel-premium' AND f.template_slug NOT LIKE 'landing-enxuta%' AND f.template_slug <> 'funil-downsell')
WHERE primary_funnel_id IN (SELECT id FROM funnels WHERE template_slug = 'pressel-premium' OR template_slug LIKE 'landing-enxuta%');
