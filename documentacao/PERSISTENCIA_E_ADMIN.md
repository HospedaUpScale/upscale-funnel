# Persistência de dados e administrador global

## Por que os dados somem a cada deploy (e como resolver)

Se você sobe o diretório do site e os dados desaparecem, é porque o banco está em
**SQLite** — um arquivo (`storage/database.sqlite`) dentro da pasta do site. Ao
reenviar a pasta, esse arquivo é sobrescrito e os dados vão junto.

**Solução recomendada (Hostinger): use MySQL.** Os dados do MySQL ficam no servidor
de banco, separados dos arquivos. Assim você pode subir/atualizar os arquivos quantas
vezes quiser que **nada é perdido**.

No `.env` do servidor:

```
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_upscale
DB_USERNAME=u123456789_upscale
DB_PASSWORD=SuaSenhaForte
```

Crie o banco e o usuário no painel da Hostinger (MySQL) e rode as migrations uma vez:

```
php scripts/migrate.php
```

**Se precisar mesmo de SQLite em produção**, aponte o banco para **fora** da pasta
pública, para o deploy não tocá-lo:

```
DB_DRIVER=sqlite
DB_SQLITE_PATH=/home/u123456789/upscale_data/database.sqlite
```

E, no deploy, **nunca** sobrescreva esse arquivo.

> Nenhuma migration apaga dados: todas usam `CREATE TABLE IF NOT EXISTS` / `ALTER TABLE`.
> A perda vem de sobrescrever o arquivo do SQLite, não do código.

## Administrador global (gerencia todos os workspaces)

O admin é criado/atualizado a partir do ambiente, então a senha nunca fica no código
(o repositório é público). No `.env` do servidor:

```
ADMIN_EMAIL=seu@email.com
ADMIN_PASSWORD=SuaSenhaForte
ADMIN_NAME=Seu Nome
```

Ele é aplicado automaticamente:

- a cada `php scripts/migrate.php`;
- a cada start do container Docker (`deploy/start.sh`).

Ou rode manualmente quando quiser:

```
ADMIN_EMAIL=seu@email.com ADMIN_PASSWORD='SuaSenha' php scripts/create_admin.php
```

O comando é **idempotente**: pode rodar quantas vezes quiser. Ele cria o usuário como
`super_admin`, que enxerga e gerencia **todos os workspaces**.

## Painel de vendas consolidado

Logado como administrador global, o seletor de workspace no topo do painel ganha a
opção **“🌐 Todos os workspaces”**. Selecionando-a, o dashboard e a tela de Transações
passam a somar as vendas de **todos** os workspaces, mostrando em cada linha de qual
workspace (e, com o rastreamento de origem ativo, de qual canal) veio a venda.
