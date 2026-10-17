# Editor de páginas

O editor ativo é o **clássico, estilo Elementor**, em `hub/index.html`. GrapesJS foi removido do frontend, dos arquivos vendor e do processo de inicialização. O endereço antigo `hub/visual-editor.html` encaminha ao editor clássico, preservando o workspace, a campanha e o ID da página.

## Campanhas e ofertas

Abra **Campanhas → Página e funil → Nova página nesta campanha**. A galeria cria uma nova página vinculada à campanha, sem reutilizar a página anterior. Você pode ter várias páginas e escolher a principal do link da campanha.

Dentro do editor clássico, a lateral esquerda contém **Campanha da experiência** e **Experiência do comprador**. Vincule uma campanha quando a página ainda não tiver uma. As opções são **Raspadinha**, **Roleta** e **Direto para o upsell**. Trocar a opção insere ou atualiza um único bloco nativo, mantendo o restante do HTML. O botão **Inserir experiência na página** permite inserir a opção já selecionada. A prévia mostra o rascunho da própria oferta, e **Publicar** aplica a escolha ao link público. A escolha também é salva no rascunho para reabertura.

Raspadinha e roleta nativas continuam em `index.html`. A roleta possui giro, segmentos e revelação da oferta e não depende de nenhuma biblioteca de edição. Os modelos e o bloco HTML do editor clássico continuam disponíveis.

Cada página tem seu endereço próprio `/f/{slug}`. A campanha mantém sua oferta compartilhada e o link `/o/{slug}`, que abre a página marcada como principal. A primeira publicação torna-se principal automaticamente; publicar outras páginas não troca essa escolha. Use **Tornar principal** na lista para alterá-la. Se a principal for despublicada ou excluída, o link da campanha volta à oferta nativa. Um iframe de oferta pode usar `{{campaign_offer_url}}&experience=campaign` para respeitar a mecânica configurada. Sem `experience=campaign`, a opção `native=1` entra direto na oferta. Isso evita recursão e mantém compatibilidade com páginas publicadas anteriormente.

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

## Assistente de textos com Claude

A aba **Conteúdo** inclui um assistente para sugerir os seis textos principais da oferta. A sugestão é revisada antes de ser aplicada e segue o fluxo normal de salvar/publicar. Veja [configuração da API Claude no EasyPanel e limites de consumo](CLAUDE_API.md).

## Criar funis pela galeria de novas páginas

Em **Nova página**, os modelos **Funil com raspadinha**, **Funil com roleta** e **Oferta direta** ficam na mesma galeria de Pressel Premium, Booster Padrão e Página em Branco. Selecione o modelo, escolha a campanha e clique em **Usar este modelo**. A campanha é obrigatória nesses três modelos, pois fornece os dados da oferta e o checkout.

A criação gera uma página independente em rascunho com a experiência escolhida e o vínculo da campanha. A página publicada continua ativa até publicar a nova. O editor mantém aberto o rascunho recém-criado, mesmo se foi aberto a partir do endereço de outra página. Na lateral, **Editar etapas e oferta** abre **Conteúdo da oferta** no próprio editor da página, sem navegação para outra tela.

A prévia ampliada desses três modelos mostra a sequência do funil, sem executar checkout ou tracking. A prévia do editor usa os dados reais da campanha. O teste de navegador verificou os três modelos, campanha obrigatória, salvamento do vínculo e mecânica, preservação da publicação anterior e acesso ao editor das etapas.


## Gerenciar todas as páginas

A opção **Páginas** do menu lista todas as páginas do workspace. Busque por nome/endereço e filtre por campanha ou status. A lista permite editar, duplicar como rascunho, copiar o link, definir como principal da campanha, despublicar e excluir. Exclusão e despublicação pedem confirmação na interface. A listagem usa respostas resumidas, sem carregar o HTML completo de cada página.

Dentro da campanha, **Páginas desta campanha** mostra a mesma gestão com o vínculo já filtrado. **Nova página nesta campanha** mantém a campanha selecionada para qualquer modelo, inclusive Pressel e Página em Branco. Cada página conserva seu HTML e publicação independentes.

## Oferta no próprio editor

A lateral **Conteúdo da oferta** permite editar título, subtítulo, preço, cotas, título/destaque da tela final, CTA, legenda, cronômetro, embed VTurb ou vídeo MP4, cores, sons e checkout. Os botões **Ver revelação** e **Ver oferta** alternam a prévia. **Salvar rascunho** salva o conteúdo da página e a oferta; **Publicar** salva a oferta pendente antes de publicar.

A oferta pertence à campanha e é compartilhada por suas páginas. Alterar o rascunho não troca o conteúdo nativo publicado; publicar a oferta atualiza os dados comuns usados por todas as páginas da campanha. O Pix usa o preço do snapshot publicado, preservando o valor enquanto se edita um novo rascunho. A página nativa envia o ID da sua própria oferta ao checkout.

A migration `0023_campaign_primary_page` preserva como principal a página publicada mais recente de cada campanha existente. Validação: múltiplas páginas com URLs distintas, escolha explícita da principal e isolamento entre workspaces; gestão pela interface; criação dentro da campanha; edição no mesmo editor, persistência e publicação; VTurb com carregador simulado e Pix com gateway de teste, sem cobrança real.
