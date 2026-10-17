# Assistente Claude no editor

Em **Editar oferta e checkout → Conteúdo → Assistente Claude Haiku**, descreva a alteração e clique em **Gerar sugestão**. Revise os seis textos e use **Aplicar ao rascunho**. O botão aplica os valores apenas ao formulário e à prévia: salvar e publicar continuam sendo ações explícitas do editor.

O assistente sugere título do Booster, subtítulo, título/destaque da tela final, texto do botão e sua legenda. Preço, quantidade, checkout, código HTML e integrações não são alterados. Não gera páginas HTML nesta versão. Confira as afirmações sugeridas antes de publicar.

## Configuração no EasyPanel

No serviço da aplicação, abra **Ambiente** e configure:

```dotenv
ANTHROPIC_API_KEY=sua_chave_nova_da_anthropic
CLAUDE_TOTAL_REQUEST_LIMIT=100
CLAUDE_DAILY_REQUEST_LIMIT=20
```

A chave precisa ter acesso à API Messages e saldo na Anthropic. Assinaturas de chat não substituem créditos de API. Se uma chave foi compartilhada em chat ou publicada, revogue-a e use uma nova. Nunca coloque a chave no editor, HTML, banco de ofertas ou repositório.

Faça o deploy da `main`. O início do container executa a migration `0022_ai_generation_quota`; em instalações sem o script padrão, execute `php scripts/migrate.php`. Em seguida, entre no painel e abra o assistente. Sem chave, ele exibe a configuração pendente e não permite gerar. O endpoint sempre exige autenticação, inclusive em ambiente local.

## Limites e custo

Usa o modelo fixo `claude-haiku-4-5-20251001`, sem ferramentas, histórico de conversa, geração automática ou tentativas automáticas de repetição. Cada chamada tem até 900 tokens de saída e o contexto enviado é limitado a 6.000 bytes. Apenas os seis textos atuais, preço, quantidade e instrução escrita pelo administrador são enviados à Anthropic. Não inclua dados pessoais na instrução.

Os limites padrão são **20 tentativas por dia UTC e 100 tentativas totais para a instalação**, compartilhados pelos workspaces. Toda chamada reservada conta, inclusive erros de rede, rejeições da API ou respostas inválidas. O total não reinicia mensalmente nem no deploy. As reservas são atômicas no banco; atingir a cota impede novas chamadas. Para desativar, remova a chave ou configure um limite como zero. Aumentar o limite total libera apenas a diferença sobre o número de tentativas já registrado.

Esses limites reduzem o consumo, mas **não medem saldo em dólares nem garantem um teto de US$ 4 na conta Anthropic**. Confira os preços vigentes, consumo e limites de gastos no console da Anthropic. Outros serviços que usem a mesma conta também consomem créditos. O painel mostra tentativas restantes, não saldo financeiro.

## Validação

Testes automatizados cobrem contexto/resposta limitados, retorno inválido ou truncado, seleção dos campos permitidos, cotas persistentes e rollback quando o limite diário é excedido, autenticação e isolamento entre workspaces. O teste de navegador verifica configuração pendente, revisão antes de aplicar, preço preservado, salvamento explícito, falha do provedor e descarte de respostas após trocar de campanha.

As respostas da Anthropic são simuladas nos testes, sem consumir créditos. A chamada real deve ser validada após configurar uma chave válida no servidor. Não há chave armazenada no código nem nos testes.

No editor de páginas, abra a aba **IA**, descreva o pedido e clique em **Gerar novas copys**. Revise os seis textos e clique em **Aplicar à oferta**. A aplicação atualiza a prévia; use **Salvar rascunho** e publique quando estiver pronto. A aba **Aparência** permite editar a paleta das etapas, incluindo CTA, textos, cartões, bordas e gradiente da raspadinha.
