-- Migration 0031: guarda o ID da campanha que a rifa enviou na venda, para devolvê-lo no aviso de cotas pagas
ALTER TABLE order_access_tokens ADD COLUMN raffle_campaign_ref VARCHAR(120) NULL;
