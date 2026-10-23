-- Migration 0030: endereço da plataforma da rifa que recebe o aviso de cotas pagas
ALTER TABLE workspaces ADD COLUMN postback_url TEXT DEFAULT NULL;
