# Domínios personalizados e funis HTML

## Preparação do sistema

1. Configure o banco no arquivo `.env`.
2. Defina `BASE_DOMAIN` com o domínio principal da instalação.
3. Execute `php scripts/migrate.php` para criar as tabelas `custom_domains` e `funnels`.
4. Confirme que o Apache está com `mod_rewrite` habilitado e permite o uso do `.htaccess`.

## Opção 1: Automação com Cloudflare for SaaS (Recomendado - 100 Domínios Grátis)

Com o **Cloudflare for SaaS**, qualquer cliente pode conectar o domínio próprio com SSL (HTTPS) emitido na nuvem em segundos, **sem precisar abrir o Easypanel ou o painel da VPS**:

1. **Conta Cloudflare:** Crie uma conta gratuita na Cloudflare e adicione seu domínio base da rede (ex: `upscalefunnel.site`).
2. **Ativar o SaaS:** No painel da Cloudflare, acesse **SSL/TLS → Custom Hostnames** e clique em **Enable Cloudflare for SaaS** (100 custom hostnames inclusos gratuitamente).
3. **Fallback Origin:** No campo *Fallback Origin*, defina `cname.seudominio.com.br` (crie um registro DNS do tipo A para `cname` apontando para o IP da sua VPS).
4. **Instrução para o Cliente:** Quando o lojista/cliente quiser usar o domínio próprio dele (ex: `sorteiosdorodrigo.com.br`), peça para ele criar apenas 1 registro no DNS dele:
   - **Tipo:** `CNAME`
   - **Nome:** `oferta` (ou `@`)
   - **Destino:** `cname.seudominio.com.br`
5. **No Hub:** Cadastre o domínio do cliente na aba **Sites (Domínios & Funis)** ou direto no campo *Domínio Personalizado* do workspace.

O SSL é gerado automaticamente na nuvem pela Cloudflare e o sistema reconhece o domínio do lojista na hora!

---

## Opção 2: Apontamento Direto na Hostinger / DNS A (Manual)

1. No hPanel da Hostinger, abra a zona DNS do domínio comprado.
2. Crie ou atualize o registro `A` do host `@` com o IP da sua VPS.
3. Crie o registro `CNAME` do host `www` apontando para o domínio base.
4. No Easypanel, entre no serviço e adicione o domínio na aba **Domains** para emitir o SSL Let's Encrypt.
5. No sistema, abra **Domínios & Funis HTML**, adicione o domínio e clique em **Verificar** após a propagação.

## Publicação de um funil

1. Clique no botão **+** em **Meus funis**.
2. Informe nome, slug e o domínio desejado.
3. Cole o documento HTML completo no editor. CSS e JavaScript podem ficar no próprio HTML.
4. Marque **Página inicial** para abrir esse funil diretamente na raiz do domínio.
5. Clique em **Publicar**.

Sem a opção **Página inicial**, a URL pública usa o formato `/f/slug-do-funil`. Um funil salvo como rascunho não fica acessível publicamente.

## Variáveis disponíveis no HTML

- `{{workspace_id}}`
- `{{workspace_slug}}`
- `{{workspace_name}}`
- `{{domain}}`
- `{{base_url}}`
- `{{api_base}}`

Essas marcações são substituídas no momento em que o funil publicado é carregado.

## Observações de DNS

- A propagação pode levar de alguns minutos a algumas horas.
- O domínio só passa ao estado **Ativo** depois que o sistema identifica o DNS ou o registro TXT.
- Tanto o domínio raiz quanto a versão com `www` resolvem para o mesmo workspace.
- Cada domínio pode ter apenas um funil marcado como página inicial.
