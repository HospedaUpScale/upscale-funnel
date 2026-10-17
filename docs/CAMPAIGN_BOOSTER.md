# Booster na campanha

Em **Criar campanha**, configure o título da campanha, a plataforma e o preço da cota base. Na seção **Oferta Booster das páginas**, informe:

- **Nome do Booster**: por exemplo, Booster Ouro.
- **Quantas vezes (X)**: por exemplo, 200. Esse número é a quantidade de cotas/chances do Booster.
- **Valor do Booster**: o preço total da oferta, independente do preço da cota base.

O sistema monta o título `Booster Ouro 200X` e preenche os títulos da raspadinha, da roleta, da oferta e do botão. A fatia premiada da roleta e a raspadinha mostram a quantidade configurada. As páginas nativas dessa campanha carregam esses dados ao abrir o editor.

Para alterar uma campanha existente, abra seus detalhes e clique em **Editar campanha e Booster**. Os dados são carregados do rascunho atual da oferta. As alterações são salvas no rascunho; publique a oferta ou uma página para atualizar a versão pública. Os textos gerados podem ser personalizados no editor depois.

O downsell mantém seus próprios dados. Cores, vídeo e outras configurações da oferta são preservados. Clientes antigos da API podem continuar criando campanhas sem os novos campos; quando algum campo `booster_name`, `booster_multiplier` ou `booster_price` é enviado, os três são obrigatórios e validados antes de salvar a campanha e sua oferta em uma transação.
