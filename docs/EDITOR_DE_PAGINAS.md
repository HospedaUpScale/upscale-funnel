# Editor de páginas

O editor ativo é o **clássico, estilo Elementor**, em `hub/index.html`. GrapesJS foi removido do frontend, dos arquivos vendor e do processo de inicialização. O endereço antigo `hub/visual-editor.html` encaminha ao editor clássico, preservando o workspace, a campanha e o ID da página.

## Campanhas e ofertas

Abra **Campanhas → Página e funil**, selecione **Raspadinha**, **Roleta** ou **Direto para o upsell** e clique em **Criar / editar página e etapas**. O editor clássico abre a página da campanha. Os valores e estilos da oferta são configurados em **Editar oferta e checkout**.

Dentro do editor clássico, a lateral esquerda contém **Campanha da experiência** e **Experiência do comprador**. Vincule uma campanha quando a página ainda não tiver uma. As opções são **Raspadinha**, **Roleta** e **Direto para o upsell**. Trocar a opção insere ou atualiza um único bloco nativo, mantendo o restante do HTML. O botão **Inserir experiência na página** permite inserir a opção já selecionada. A prévia mostra o rascunho da própria oferta, e **Publicar** aplica a escolha ao link público. A escolha também é salva no rascunho para reabertura.

Raspadinha e roleta nativas continuam em `index.html`. A roleta possui giro, segmentos e revelação da oferta e não depende de nenhuma biblioteca de edição. Os modelos e o bloco HTML do editor clássico continuam disponíveis.

Cada campanha mantém sua própria oferta e endereço `/o/{slug}`. Publicar uma página vinculada publica a própria oferta e a campanha, mantendo o mesmo link. Um iframe de oferta pode usar `{{campaign_offer_url}}&experience=campaign` para respeitar a mecânica configurada. Sem `experience=campaign`, a opção `native=1` entra direto na oferta. Isso evita recursão e mantém compatibilidade com páginas publicadas anteriormente.

Páginas existentes permanecem no banco e podem ser abertas pelo HTML salvo, sem carregar o antigo motor. Campos de projeto e a biblioteca de modelos do editor removido permanecem no banco para preservar dados históricos; a interface antiga da biblioteca foi removida junto com aquele editor. Não exclua nem reverta migrações já aplicadas.

## Embed de vídeo VTurb

Em **Editar oferta e checkout → Mídia**, cole o código completo em **Código de incorporação VTurb**. O painel identifica a conta, o player, o caminho do carregador (inclusive `/v4/player.js`) e a proporção do placeholder. Os IDs manuais continuam disponíveis para integrações antigas. Apagar o código também limpa os IDs preenchidos automaticamente; editar um ID manualmente desativa o código anterior.

O código colado fica salvo no tema/rascunho da oferta. A aplicação reconstrói o smartplayer e carrega somente o `player.js` HTTPS de `scripts.converteai.net`; outros scripts do texto colado não são executados. Um embed com IDs conflitantes ou vários players diferentes é rejeitado. O carregador não é duplicado em atualizações da mesma prévia, e a opção de MP4 continua disponível quando não há um player VTurb configurado.

Os testes verificaram extração do embed, persistência, publicação, estrutura do smartplayer e troca para MP4. No teste de navegador, o carregador externo foi simulado: o acesso direto ao servidor VTurb retornou 403 neste ambiente, portanto a reprodução real deve ser conferida no site publicado.

## Efeitos sonoros

A roleta possui cliques durante o giro, que desaceleram até a revelação. A raspadinha possui som de raspagem enquanto o visitante movimenta o dedo ou mouse e um toque musical ao revelar a oferta. O áudio é sintetizado com Web Audio; não depende de arquivos ou serviços externos e só é iniciado após interação do visitante.

Em **Editar oferta e checkout → Aparência → Área de raspagem**, configure **Ativar efeitos sonoros** e **Volume dos efeitos**. O padrão é ativado, com volume de 20%. O visitante pode usar **Som ativado / Som desativado** na própria experiência; a preferência de silêncio fica salva no navegador. A opção de upsell direto não toca a revelação. Em navegadores sem Web Audio, os jogos continuam funcionando sem efeitos. Os cliques do giro são interrompidos quando a página é ocultada ou o som é silenciado.

## Ambiente e testes

Ative o PHP, execute `php scripts/migrate.php` e inicie `php -S 127.0.0.1:8000 router.php`. Não é mais necessário baixar dependências de um editor visual externo. Execute `php scripts/test.php`.

A falha preexistente `testMasterCanListAllUsersWithWorkspacesAndRoles` ocorre quando o seed não contém usuário. O restante da suíte e o teste de navegador do fluxo campanha → editor clássico → roleta nativa passaram. Nenhuma cobrança real foi efetuada nos testes.

## Tela final da oferta

Após revelar a raspadinha ou roleta, a oferta usa apresentação ampla e responsiva com etapa concluída, selo de desbloqueio, título em duas partes, vídeo antes do produto/preço, cronômetro e CTA de pagamento. O acesso direto usa a mesma apresentação. O preço, produto, vídeo e botão continuam usando os dados da oferta publicada.

Em **Editar oferta e checkout → Conteúdo**, os campos **Título da tela final** e **Destaque da tela final** alteram a chamada principal. São salvos no rascunho e publicados junto ao tema. Sem vídeo configurado, o player e a frase que convida a assistir ficam ocultos. A prévia permite voltar à roleta/raspadinha e restaura a apresentação inicial.

Validação: layout sem rolagem horizontal em 320, 390, 941 e 1440 px; preço configurado, cronômetro, retorno da prévia e Pix simulado. Salvamento/reabertura/publicação dos textos e integração VTurb v4/MP4 verificados com carregador externo simulado.
