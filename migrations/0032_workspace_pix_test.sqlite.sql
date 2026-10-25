-- Migration 0032: Pix de teste por workspace (simular pagamento na própria oferta). Desliga sozinho no horário gravado.
ALTER TABLE workspaces ADD COLUMN pix_test_until TEXT DEFAULT NULL;
