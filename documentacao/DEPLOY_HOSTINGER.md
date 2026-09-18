# Deploy na Hostinger

## 1. Criar o site

No hPanel, acesse **Sites → Adicionar site** e escolha **Site PHP/HTML em branco**. Conecte o domínio principal que hospedará o sistema.

Em **Configuração PHP**, selecione PHP 8.2 ou superior.

## 2. Enviar os arquivos

Compacte o projeto sem o `.env` local, sem `.git` e sem `storage/database.sqlite`. Envie o arquivo pelo Gerenciador de Arquivos, abra `public_html` e extraia o conteúdo diretamente nessa pasta.

O resultado deve ser `public_html/index.html`, `public_html/funnel.php`, `public_html/api`, `public_html/hub` e as demais pastas do projeto. Não deixe tudo dentro de uma pasta adicional chamada `UPSCALE`.

## 3. Criar o MySQL

No hPanel, abra **Gerenciamento Banco de Dados** e crie um banco, um usuário e uma senha forte. Anote os nomes completos gerados pela Hostinger.

## 4. Criar o `.env`

Copie `.env.example` para `.env` e configure:

```env
APP_ENV=production
BASE_DOMAIN=seudominio.com.br
DEFAULT_WORKSPACE_SLUG=sincero
SUPER_ADMIN_TOKEN=UMA_CHAVE_ALEATORIA_DE_64_CARACTERES
ENFORCE_API_TOKEN=true

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_upscale
DB_USERNAME=u123456789_upscale
DB_PASSWORD=SUA_SENHA_REAL
```

Gere o token com `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` e guarde-o em um gerenciador de senhas. Defina a permissão do `.env` como `600`, se o plano permitir.

## 5. Criar as tabelas

Habilite o SSH no hPanel, entre no diretório `public_html` e execute:

```bash
php scripts/migrate.php
```

Guarde os tokens exibidos no primeiro seed. O acesso web às pastas `scripts`, `migrations`, `src`, `config` e `storage` já é bloqueado pelo `.htaccess`.

## 6. Testar

- Abra `https://seudominio.com.br/api/index.php?format=json`.
- Abra `https://seudominio.com.br/hub/`.
- No modal de acesso, cole o valor de `SUPER_ADMIN_TOKEN`.
- Confira **Domínios & Funis HTML** e publique um funil de teste.

## 7. Adicionar outros domínios

Para cada domínio de funil, use **Sites → Dashboard → Domínios estacionados** no site principal. Depois aponte o registro `A` para o IP da hospedagem, configure `www`, aguarde o SSL e cadastre o domínio em **Domínios & Funis HTML**.
