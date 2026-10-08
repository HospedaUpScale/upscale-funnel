# 🚀 Como Funciona o Sistema upScale

> **Guia Completo de Arquitetura, Mecânicas de Conversão, APIs e Operação**  
> *Versão:* 2.0 (Atualizada com Módulos de Segurança, Testes TDD e Painel de Monitoramento)

---

## 📌 1. Visão Geral: O que é o upScale?

O **upScale** é uma **camada de inteligência e otimização de conversão (Optimization & Upsell Layer)** desenhada para potencializar o faturamento de vendas online, rifas, sorteios e infoprodutos.

Ele atua entre a plataforma de vendas/rifas externa (ex.: *SorteiMe, Play55, Sorteamos, Kiwify, Hotmart*) e os gateways de pagamento Pix, permitindo:

1. **Aumento Imediato do Ticket Médio:** Ofertas de Order Bump e Upsell de 1-clique com cotas adicionais ou bônus aceleradores.
2. **Checkout Sem Fricção:** Reaproveitamento imediato dos dados do comprador (Nome, Telefone, CPF) para gerar cobranças Pix instantâneas sem exigir novo preenchimento.
3. **Gamificação de Alta Conversão:** Elementos visuais como raspadinhas digitais, contadores de escassez, bônus multiplicadores (ex.: *Booster 110X*) e VSLs (vídeos de vendas).
4. **Multi-Tenancy & Split de Comissões:** Gestão de múltiplos lojistas/workspaces com divisão automática de comissões entre afiliados, parceiros e o operador da plataforma.

---

## 🏗️ 2. Arquitetura do Sistema

O sistema é modular, leve e construído com foco em **zero dependências pesadas** e **execução ultrarrápida** tanto em ambiente local de desenvolvimento quanto em servidores de produção (VPS/Hostinger/Docker).

```mermaid
graph TD
    User([Comprador Final]) -->|Acessa Funil/Checkout| Frontend[Widget & Funil Front-end<br/>index.html / funnel.php]
    Admin([Lojista / Super Admin]) -->|Gerencia Operação| Hub[Painel Hub SaaS<br/>hub/index.html]

    Frontend -->|Requisições HTTP / JSON| Router[Roteador PHP<br/>router.php]
    Hub -->|APIs Administrativas| Router

    Router --> RateLimiter[Camada de Segurança<br/>src/Security/RateLimiter.php]
    RateLimiter --> Endpoints[Endpoints da API<br/>api/*.php]

    Endpoints --> Repositories[Camada de Dados & Repositórios<br/>src/Repositories/*]
    Repositories --> DB[(Banco de Dados<br/>SQLite em Dev | MySQL em Prod)]

    Endpoints --> Gateway[Gateways de Pagamento Pix<br/>Split Automático / Webhooks]
```

### Principais Componentes:

| Componente | Localização | Descrição |
| :--- | :--- | :--- |
| **Widget de Conversão & Funil** | [`index.html`](file:///c:/Users/Isaias/Downloads/UPSCALE/index.html), [`funnel.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/funnel.php) | Interface pública focada em altíssima conversão, com raspadinha, contadores e geração de Pix. |
| **Painel de Gestão (Hub SaaS)** | [`hub/index.html`](file:///c:/Users/Isaias/Downloads/UPSCALE/hub/index.html) | Dashboard administrativo completo com métricas em tempo real, regras de split de parceiros e gerenciamento de workspaces. |
| **Core de Segurança & Middleware** | [`src/Security/RateLimiter.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/src/Security/RateLimiter.php) | Bloqueador de ataques de força bruta com janelas de decaimento configuráveis (protege logins e APIs críticas). |
| **Observabilidade & Diagnóstico** | [`api/health.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/api/health.php) | Endpoint de saúde que relata latência do banco de dados, status de escrita em disco e parâmetros de execução PHP. |
| **Suite de Testes Automatizada** | [`scripts/test.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/scripts/test.php), [`tests/`](file:///c:/Users/Isaias/Downloads/UPSCALE/tests) | Executor autônomo de testes unitários e de integração (TDD) com zero dependências externas. |

---

## ⚡ 3. Mecânicas de Conversão e Fluxo de Venda

O sistema implementa duas estratégias fundamentais de monetização:

### Estratégia A: Order Bump Pré-Pix (Carrinho Turbinado)
1. O comprador escolhe suas cotas ou produto principal na página.
2. O widget apresenta uma oportunidade complementar imediata (ex.: *"Adicione 50 cotas VIP com 70% de desconto"*).
3. Ao marcar a opção, o valor é consolidado em uma **única cobrança Pix**.
4. O cliente escaneia o QR Code uma única vez, pagando o valor somado.

### Estratégia B: 1-Click Upsell Pós-Pix Imediato
1. O cliente conclui o pagamento do pedido principal.
2. Os dados de contato e identificação são retidos com tempo de expiração curto (TTL) em sessão segura.
3. A página de confirmação carrega uma oferta irresistível com vídeo e raspadinha interativa.
4. Ao clicar em **"ATIVAR BOOSTER"**, uma nova cobrança Pix é gerada instantaneamente para o mesmo CPF/telefone, sem exigir digitação de cadastro novamente.

---

## 📡 4. Mapa de APIs e Endpoints

Todas as requisições passam pelo [`router.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/router.php) (desenvolvimento) ou pelas regras do [`.htaccess`](file:///c:/Users/Isaias/Downloads/UPSCALE/.htaccess) / Nginx (produção).

### 🟢 Observabilidade & Sistema
* **`GET /api/health`** ([`api/health.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/api/health.php))
  * **Função:** Diagnóstico de integridade da infraestrutura.
  * **Retorno:** Status de conexão com banco de dados, tempo de latência em milissegundos, permissões de escrita em disco e versão do PHP.

### 🔒 Autenticação & Administração
* **`POST /api/admin/login`** ([`api/admin/login.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/api/admin/login.php))
  * **Função:** Autenticação no painel administrativo.
  * **Segurança:** Protegido pelo `RateLimiter`. Após 5 tentativas inválidas consecutivas por IP, bloqueia o acesso por 60 segundos retornando `HTTP 429 Too Many Requests`.
* **`GET /api/admin/workspaces`** ([`api/admin/workspaces.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/api/admin/workspaces.php))
  * **Função:** Gestão multi-tenant das contas e configurações dos lojistas parceiros.

### 💳 Transações e Conversão
* **`POST /api/pix-upsell`** ([`api/pix-upsell.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/api/pix-upsell.php))
  * **Função:** Criação e recuperação da cobrança Pix de upsell com QR Code e Copia-e-Cola.
* **`GET /api/order-context`** ([`api/order-context.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/api/order-context.php))
  * **Função:** Recupera dados de sessão do cliente para pré-preencher o fluxo de 1-clique.
* **`GET /api/v1/winner`** ([`api/v1/winner.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/api/v1/winner.php))
  * **Função:** Consulta e exibição de ganhadores e resultados de sorteios.

---

## 🗄️ 5. Banco de Dados e Multi-Tenancy

O sistema possui uma camada flexível em [`src/Database/Connection.php`](file:///c:/Users/Isaias/Downloads/UPSCALE/src/Database/Connection.php) que suporta dois drivers:

1. **SQLite (`DB_DRIVER=sqlite`):**
   * Usado para desenvolvimento local rápido e testes.
   * Não requer instalação de servidores MySQL ou PostgreSQL locais.
   * Arquivo persistido em [`storage/database.sqlite`](file:///c:/Users/Isaias/Downloads/UPSCALE/storage).
2. **MySQL (`DB_DRIVER=mysql`):**
   * Usado em ambientes de homologação e produção (Hostinger / AWS / VPS).
   * Configuração simples via variáveis do arquivo [`.env`](file:///c:/Users/Isaias/Downloads/UPSCALE/.env).

### Principais Entidades e Tabelas:
* `workspaces`: Representa os lojistas/tenants independentes.
* `orders`: Registro dos pedidos e valores principais.
* `upsell_offers`: Configurações de ofertas ativas, boosters e preços promocionais.
* `partner_split_rules`: Regras de rateio e divisão de comissão entre afiliados e plataforma.

---

## 🛠️ 6. Como Rodar e Testar o Sistema Localmente

### Iniciar o Servidor Web de Desenvolvimento:
Abra o terminal na pasta do projeto e execute:
```bash
php -S 127.0.0.1:8000 router.php
```

Acesse no seu navegador:
* **Painel Administrativo:** [http://localhost:8000/hub/](http://localhost:8000/hub/)
* **Página de Venda / Funil:** [http://localhost:8000/index.html](http://localhost:8000/index.html)
* **Diagnóstico de Saúde:** [http://localhost:8000/api/health](http://localhost:8000/api/health)

---

## 🧪 7. Suite de Testes Automatizados (TDD)

O projeto possui um executor de testes nativo que não exige a instalação do Composer ou PHPUnit:

```bash
php scripts/test.php
```

O executor roda os testes das pastas [`tests/Unit/`](file:///c:/Users/Isaias/Downloads/UPSCALE/tests/Unit) e [`tests/Integration/`](file:///c:/Users/Isaias/Downloads/UPSCALE/tests/Integration), validando:
* Regras de split de comissão de afiliados.
* Bloqueio do `RateLimiter` sob tentativas abusivas de login.
* Integridade das tabelas do banco de dados e workspaces obrigatórios.
* Conexão e retorno estruturado do endpoint de integridade (`api/health.php`).

---

## 📁 8. Mapa de Diretórios do Projeto

```text
UPSCALE/
├── api/                    # Endpoints HTTP da API (JSON)
│   ├── admin/              # Endpoints restritos (login, workspaces, etc.)
│   ├── health.php          # Verificação de status do servidor e banco
│   └── pix-upsell.php      # Geração de cobranças Pix e upsells
├── config/                 # Arquivos de configuração da aplicação
├── deploy/                 # Configurações para produção (Nginx, Docker)
├── docs/                   # Especificações técnicas e planos de melhoria
├── documentacao/           # Guias de integração externos (Sorteamos, etc.)
├── hub/                    # Painel SaaS Administrativo (Hub de Monetização)
│   └── index.html          # Interface do Dashboard
├── migrations/             # Scripts de criação e atualização de tabelas SQL
├── scripts/
│   └── test.php            # Executor de testes TDD sem dependências
├── src/                    # Código-fonte PHP orientado a objetos
│   ├── Database/           # Conexão PDO (SQLite / MySQL)
│   ├── Repositories/       # Acesso e persistência de dados
│   └── Security/           # Camadas de segurança (RateLimiter)
├── storage/                # Logs, caches efêmeros e banco SQLite local
├── tests/                  # Testes automatizados (Unitários e Integração)
├── .env                    # Variáveis de ambiente ativas
├── funnel.php              # Roteador de renderização dinâmica de funis
├── index.html              # Frontend padrão do funil de conversão / booster
└── router.php              # Roteador do servidor local PHP CLI
```
