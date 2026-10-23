-- Migration 0030: endereço da plataforma da rifa que recebe o aviso de cotas pagas
ALTER TABLE workspaces ADD COLUMN postback_url VARCHAR(500) NULL;
