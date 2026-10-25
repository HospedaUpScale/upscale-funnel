-- Migration 0033: retorno automático. A rifa informa na venda onde receber o aviso de cotas pagas e a chave que ela confere.
ALTER TABLE order_access_tokens ADD COLUMN callback_url TEXT DEFAULT NULL;
ALTER TABLE order_access_tokens ADD COLUMN callback_key TEXT DEFAULT NULL;
