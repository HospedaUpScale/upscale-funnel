# Editor de páginas, HTML e funis

No Hub, abra **Campanhas → Abrir campanha → Página e funil desta campanha**. Escolha **Raspadinha → upsell**, **Roleta → upsell** ou **Direto para o upsell** e clique em **Criar / editar página e etapas**. O editor mantém o workspace e a campanha selecionados. A criação de páginas em Domínios e funis também encaminha para campanhas. Páginas criadas pelo editor visual voltam a abrir nele na lista de páginas; o editor clássico continua disponível para páginas antigas.

Escolha **Landing page**, **Funil de vendas**, **Raspadinha**, **Roleta**, **Página em branco** ou **HTML pronto**. Cada escolha cria uma cópia independente. Os modelos de raspadinha e roleta têm apresentação, interação, resultado, oferta/checkout e conclusão.

## Página vinculada à campanha

Cada campanha tem sua própria oferta. Ao criar uma campanha, a oferta é preparada em rascunho e recebe um endereço `/o/{slug}` exclusivo. A API de ofertas recebe `campaign_id` para carregar e salvar a oferta correta; chamadas antigas sem campanha mantêm o comportamento legado.

A página usa o mesmo link da oferta: ao publicar, `/o/{slug}` serve o funil da campanha. O editor salva `campaign_id` na página e publica página, oferta e campanha na mesma transação. Salvar somente o rascunho mantém a página publicada anterior.

Raspadinha e roleta têm etapas de interação, resultado e upsell. O acesso direto tem somente a etapa de upsell. A etapa **Upsell e checkout** usa a oferta real da campanha, em um bloco nativo; os dados, estilos e pagamento dessa etapa são editados em **Editar oferta e checkout**. Não são copiados de uma oferta de outro cliente nem substituídos por um checkout demonstrativo. O bloco usa `native=1` para evitar recursão e preserva os parâmetros do pedido e UTMs. Enquanto a oferta não está publicada, o editor exibe uma indicação de rascunho.

Os modelos genéricos e HTML pronto continuam disponíveis no editor. Trocar o tipo de funil de uma página existente pede confirmação antes de substituir as etapas. Modelos salvos podem ser reutilizados na campanha, com o bloco de upsell vinculado à oferta de destino.

## Editar e publicar

- Clique na etapa à esquerda. Clique no texto para editar e use blocos, estilos e propriedades para personalizar o conteúdo.
- Adicione, renomeie, reordene ou exclua etapas. Ao selecionar um botão/link, use **Ir para próxima etapa** ou **Ir para etapa** nas propriedades para definir a navegação.
- Em **Código HTML**, cole o código da etapa. Para uma página HTML única, o documento completo é preservado, incluindo estilos, metadados e scripts. Em funis com várias etapas, o conteúdo e os recursos de cada documento são incorporados à página do funil.
- A conversão de HTML para edição visual é explícita: scripts e recursos do `<head>` podem exigir ajuste. Para preservar código pronto exatamente, continue no modo HTML.
- Configure o endereço da página e o link de checkout do botão da oferta.
- **Visualizar** testa o fluxo em uma prévia isolada. **Salvar rascunho** mantém a publicação anterior. **Publicar** salva alterações pendentes antes de colocar a nova versão no endereço `/f/{slug}`.
- O editor salva automaticamente após três segundos sem alterações. Conflitos entre abas interrompem a gravação e pedem recarregamento.

## Criar seus próprios modelos

1. Monte uma página ou funil usando os modelos iniciais, blocos ou HTML pronto.
2. Clique em **Salvar como modelo** e informe nome, categoria e, opcionalmente, uma URL HTTPS para a capa.
3. Abra **Modelos e HTML pronto → Meus modelos** e clique em **Criar página**. Uma nova aba abre uma cópia independente com todas as etapas.
4. Personalize e publique a cópia. Alterações na cópia não modificam o modelo nem a página de origem.

A biblioteca pertence ao workspace selecionado. Salvar um modelo não publica a página. Excluir um modelo não exclui páginas já criadas. Para criar uma nova versão de um modelo, abra uma cópia, edite e salve como outro modelo; depois exclua a versão antiga se desejar. A migração `0021_page_templates` cria a biblioteca em SQLite e MySQL.

## Interações e pagamentos

A raspadinha permite raspar com mouse ou toque. A roleta anima o giro e avança ao resultado. O texto/condição do resultado é configurado no editor; os modelos não calculam probabilidades nem concedem prêmios aleatórios. Nos modelos genéricos, o checkout usa o link configurado pelo responsável; nas páginas de campanha, o bloco nativo usa o checkout da oferta vinculada. A conclusão de demonstração não confirma pagamento; a confirmação financeira pertence ao checkout/gateway.

## Ambiente e validação

PHP precisa de PDO SQLite ou MySQL, `iconv`, `curl` e `mbstring`. Para uploads, também precisa de `fileinfo`. Aplique `php scripts/migrate.php` e instale GrapesJS com `sh scripts/vendor_grapesjs.sh`. O projeto usa GrapesJS Community 0.21.9 e os arquivos gerados ficam em `vendor/grapesjs/`.

Execute `php scripts/test.php`. Os testes de `SecurityHardeningTest` cobrem autenticação, escopo de workspace, endereços reservados, persistência do projeto, separação entre rascunho/publicação e revisões obsoletas. O teste preexistente `testMasterCanListAllUsersWithWorkspacesAndRoles` pressupõe um usuário cadastrado, que não existe no seed padrão.

Validação no navegador: criar cada modelo, editar, salvar, reabrir, publicar e percorrer suas etapas. No modo HTML, verificar também que CSS e scripts foram preservados na página pública.
