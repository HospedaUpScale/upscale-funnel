# Domínios personalizados e funis HTML

## Preparação do sistema

1. Configure o banco no arquivo `.env`.
2. Defina `BASE_DOMAIN` com o domínio principal da instalação.
3. Execute `php scripts/migrate.php` para criar as tabelas `custom_domains` e `funnels`.
4. Confirme que o Apache está com `mod_rewrite` habilitado e permite o uso do `.htaccess`.

## Apontamento na Hostinger

1. No hPanel, abra a zona DNS do domínio comprado.
2. Crie ou atualize o registro `A` do host `@` com o IP exibido em **Domínios & Funis HTML**.
3. Crie o registro `CNAME` do host `www` apontando para o domínio base exibido no painel.
4. No painel do site principal, abra **Domínios estacionados** e estacione o novo domínio para ele usar o mesmo `public_html` do sistema.
5. Ative o SSL gratuito da Hostinger para o domínio e para `www`.
6. No sistema, abra **Domínios & Funis HTML**, adicione o domínio e clique em **Verificar** após a propagação.

Se o provedor não permitir `CNAME` em `www`, use outro registro `A` com o mesmo IP da instalação. O registro TXT mostrado no painel também pode ser usado para comprovar o domínio sem depender do apontamento final.

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
