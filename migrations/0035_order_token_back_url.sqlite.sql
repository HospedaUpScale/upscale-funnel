-- Migration 0035: link de volta. A rifa informa na venda a página do pedido; a LP da oferta leva o comprador de volta para lá.
ALTER TABLE order_access_tokens ADD COLUMN back_url TEXT DEFAULT NULL;
