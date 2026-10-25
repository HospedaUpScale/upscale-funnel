-- Migration 0033: retorno automático. A rifa informa na venda onde receber o aviso de cotas pagas e a chave que ela confere.
ALTER TABLE order_access_tokens ADD COLUMN callback_url VARCHAR(500) NULL, ADD COLUMN callback_key VARCHAR(200) NULL;
