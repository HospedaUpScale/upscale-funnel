# Deploy (painel de container: Dokploy / Coolify)

Este guia coloca no ar a versão com: segurança, rastreamento de vendas, admin global,
painel consolidado, Pressel e roleta. **Faça os passos 1 e 2 ANTES de implantar** —
sem as variáveis, o login e os pagamentos quebram e os dados continuam sumindo.

## 1. Banco de dados persistente (resolve a perda de dados)

No serviço **upscale-funnel → Ambiente**, aponte o app para o serviço de banco
(`upscale-db`), em vez do SQLite dentro do container:

```
DB_DRIVER=mysql
DB_HOST=upscale-db        # nome do serviço de banco na rede interna
DB_PORT=3306
DB_DATABASE=<nome do banco do upscale-db>
DB_USERNAME=<usuário do upscale-db>
DB_PASSWORD=<senha do upscale-db>
```

> Os valores vêm do serviço **upscale-db** (aba Ambiente/Visão Geral dele).
> O sistema suporta MySQL ou SQLite. Se o upscale-db for Postgres, me avise.

## 2. Login, admin global e webhooks

Ainda em **upscale-funnel → Ambiente**:

```
APP_ENV=production
SUPER_ADMIN_TOKEN=<chave aleatoria longa, 64 caracteres>

# Administrador global (super_admin de todos os workspaces) e login master do Hub
ADMIN_USER=admin
ADMIN_EMAIL=isaiasmktd@gmail.com
ADMIN_PASSWORD=<sua senha forte>
ADMIN_NAME=Admin upScale

# Webhooks (assinatura do postback de cotas)
POSTBACK_SECRET=<chave aleatoria longa>
# WEBHOOK_SECRET=  # opcional: segredo global do webhook de pagamento
```

## 3. Implantar

Clique em **Implantar**. No start, o sistema roda as migrations no `upscale-db` e
cria/atualiza o admin automaticamente. Depois:

- Acesse o Hub com **ADMIN_EMAIL / ADMIN_PASSWORD** (aba Master).
- No seletor de workspace, escolha **“🌐 Todos os workspaces”** para o painel consolidado.

## 4. Depois do deploy (webhooks das plataformas)

Os webhooks passaram a exigir token. No Hub → Configurações → Credenciais & API:

1. Gere o **token de API** do workspace.
2. Copie as URLs mostradas e cadastre nas plataformas (vendas da rifa e gateway Pix),
   com o token (`Authorization: Bearer ...` ou `?token=...`).

Sem isso, os pagamentos param de ser confirmados.
