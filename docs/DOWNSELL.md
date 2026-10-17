# Downsell

Crie ou abra uma página principal da campanha e clique em **Criar / editar downsell** acima da prévia. Também é possível selecionar **Downsell** em **Nova página**, ou abrir a alternativa pela lista de experiências da campanha.

O downsell usa uma oferta separada da principal, com preço, quantidade, títulos, vídeo, cores e checkout próprios. Edite os campos, salve o rascunho e publique a página do downsell.

Quando a alternativa estiver publicada, a oferta principal passa a mostrar um link discreto abaixo do botão: **Não quero esta oferta. Ver outra opção**. O texto é editável em **Conteúdo → Link discreto para outra oferta**. A navegação conserva os parâmetros do comprador e da campanha. Na prévia do editor, esse link abre a edição do downsell sem realizar pagamento.

Publicar o downsell não substitui a página principal da campanha. Despublicar ou excluir a última página publicada de downsell remove o caminho público para a alternativa. As páginas de downsell da mesma campanha compartilham essa oferta alternativa.

O deploy deve executar as migrations usuais, incluindo `0024_downsell_offers` (MySQL/SQLite). A migração classifica as ofertas existentes como principais (`upsell`), preservando seus valores e conteúdos. O checkout usa o ID e o preço publicado da oferta alternativa; valores de rascunho não alteram o preço público.

A oferta alternativa também pode ser configurada antes de salvar a campanha, pelo botão **Criar oferta de downsell** no formulário. O menu separa **Domínios**, **Funis** e **Editor de páginas**; a lista geral permanece em **Páginas**. Campanhas e páginas usam paginação de 10 itens, com os filtros aplicados antes da divisão em páginas.
