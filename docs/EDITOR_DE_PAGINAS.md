# Editor de páginas

O editor ativo é o **clássico, estilo Elementor**, em `hub/index.html`. GrapesJS foi removido do frontend, dos arquivos vendor e do processo de inicialização. O endereço antigo `hub/visual-editor.html` encaminha ao editor clássico, preservando o workspace, a campanha e o ID da página.

## Campanhas e ofertas

Abra **Campanhas → Página e funil**, selecione **Raspadinha**, **Roleta** ou **Direto para o upsell** e clique em **Criar / editar página e etapas**. O editor clássico abre a página da campanha. Os valores e estilos da oferta são configurados em **Editar oferta e checkout**.

Raspadinha e roleta nativas continuam em `index.html`. A roleta possui giro, segmentos e revelação da oferta e não depende de nenhuma biblioteca de edição. Os modelos e o bloco HTML do editor clássico continuam disponíveis.

Cada campanha mantém sua própria oferta e endereço `/o/{slug}`. Publicar uma página vinculada publica a própria oferta e a campanha, mantendo o mesmo link. Um iframe de oferta pode usar `{{campaign_offer_url}}&experience=campaign` para respeitar a mecânica configurada. Sem `experience=campaign`, a opção `native=1` entra direto na oferta. Isso evita recursão e mantém compatibilidade com páginas publicadas anteriormente.

Páginas existentes permanecem no banco e podem ser abertas pelo HTML salvo, sem carregar o antigo motor. Campos de projeto e a biblioteca de modelos do editor removido permanecem no banco para preservar dados históricos; a interface antiga da biblioteca foi removida junto com aquele editor. Não exclua nem reverta migrações já aplicadas.

## Ambiente e testes

Ative o PHP, execute `php scripts/migrate.php` e inicie `php -S 127.0.0.1:8000 router.php`. Não é mais necessário baixar dependências de um editor visual externo. Execute `php scripts/test.php`.

A falha preexistente `testMasterCanListAllUsersWithWorkspacesAndRoles` ocorre quando o seed não contém usuário. O restante da suíte e o teste de navegador do fluxo campanha → editor clássico → roleta nativa passaram. Nenhuma cobrança real foi efetuada nos testes.
