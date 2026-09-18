# 📘 Manual de Integração – Raspadinha Booster 110X (Vendedor Sincero)

Este documento contém todas as especificações técnicas, contratos de mensagens, cabeçalhos de segurança e códigos necessários para integrar o card interativo da **Raspadinha Booster 110X** em qualquer plataforma de rifas ou checkout (ex: *Sincero Prêmios*, *WordPress/Elementor*, *WooCommerce*, etc.).

---

## 📑 Sumário

1. [Visão Geral da Arquitetura](#1-visão-geral-da-arquitetura)
2. [Hospedagem na Hostinger (Apache)](#2-hospedagem-na-hostinger-apache)
3. [Código de Embed na Rifa](#3-código-de-embed-na-rifa)
4. [Tabela de Parâmetros e Atributos `data-*`](#4-tabela-de-parâmetros-e-atributos-data-)
5. [Contrato de Mensagens (`postMessage`)](#5-contrato-de-mensagens-postmessage)
6. [Resolução do Nome do Comprador](#6-resolução-do-nome-do-comprador)
7. [Parâmetros de URL (Query String)](#7-parâmetros-de-url-query-string)
8. [API Dinâmica e Telemetria (SSR)](#8-api-dinâmica-e-telemetria-ssr)
9. [Navegação e Fuga de Iframe (Sandbox Buster)](#9-navegação-e-fuga-de-iframe-sandbox-buster)
10. [Diagnóstico e Solução de Problemas](#10-diagnóstico-e-solução-de-problemas)
11. [Checklist Pré-Publicação](#11-checklist-pré-publicação)

---

## 1. Visão Geral da Arquitetura

O card funciona em uma arquitetura desacoplada via **`<iframe>` inteligente**. O iframe é servido pelo seu domínio (`https://oferta.vagnerpecuaria.site/`) e embutido na página de agradecimento/sucesso da rifa (`sinceropremios.com` via Sorteamos).

```
┌─────────────────────────────────────────────────────────────┐
│  Página de Obrigado da Rifa (ex: sinceropremios.com)        │
│                                                             │
│   ┌─────────────────────────────────────────────────────┐   │
│   │ <iframe> (oferta.vagnerpecuaria.site/)              │   │
│   │                                                     │   │
│   │  [1. Raspadinha: Revela 110 Cotas por R$ 90]       │   │
│   │        │                                            │   │
│   │  [2. Transição: Reservando cotas... (1.8s)]         │   │
│   │        │                                            │   │
│   │  [3. Oferta: Vídeo + Timer + Botão de Compra]       │   │
│   │                                                     │   │
│   │  Comunicação com o Pai:                             │   │
│   │  • Envia "dx:altura" ─────────► Redimensiona frame │   │
│   └─────────────────────────────────────────────────────┘   │
│                                                             │
│   [Clique em Comprar] ──(Fuga do Iframe)──► Checkout Top   │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Hospedagem na Hostinger (Apache)

### 2.1 Estrutura de Diretórios
No painel da Hostinger (hPanel ➔ Gerenciador de Arquivos do domínio `oferta.vagnerpecuaria.site`), os arquivos ficam em:

```
public_html/
├── index.html            <-- O card consolidado da raspadinha
├── .htaccess             <-- Cabeçalhos e liberação de iframe
├── teste/
│   └── index.html        <-- Ferramenta de diagnóstico de origem
└── documentacao/
    └── index.html        <-- Documentação técnica web
```

> **IMPORTANTE:** Sempre utilize a URL com a barra final: `https://oferta.vagnerpecuaria.site/`. Sem a barra, o Apache pode emitir um redirecionamento HTTP 301 desnecessário dentro do frame.

### 2.2 Arquivo `.htaccess` Obrigatório
A Hostinger aplica por padrão o cabeçalho `X-Frame-Options: SAMEORIGIN`. Como o card roda dentro de outro domínio (a rifa), esse cabeçalho **precisa ser desativado**, substituindo-o pelo padrão moderno `Content-Security-Policy`:

```apache
# /public_html/rasp2/.htaccess

<IfModule mod_headers.c>
    # 1. Desativa a restrição SAMEORIGIN da Hostinger
    Header always unset X-Frame-Options
    Header unset X-Frame-Options

    # 2. Libera a incorporação em iframe para qualquer origem confiável
    Header always set Content-Security-Policy "frame-ancestors *;"

    # 3. Libera CORS para fontes e assets
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
</IfModule>
```

---

## 3. Código de Embed na Rifa

Cole este código no painel da plataforma da rifa (HTML personalizado, Elementor ou página de sucesso):

```html
<!-- BLOCO DE INTEGRAÇÃO DA RASPABOOSTER (CANAL 1) -->
<div id="wrapper-raspa-booster" style="width:100%; max-width:680px; margin:0 auto; padding:8px 0;">
  <iframe 
    id="iframe-booster"
    src="https://oferta.vagnerpecuaria.site/?nome={{customer.name}}"
    style="width:100%; min-height:420px; border:none; overflow:hidden; display:block; background:transparent; transition:height 0.25s ease;"
    scrolling="no"
    loading="eager"
    allow="clipboard-write">
  </iframe>
</div>

<script>
  // Ajuste automático de altura (dx:altura)
  window.addEventListener('message', function(e) {
    if (e.data && (e.data.tipo === 'dx:altura' || e.data.altura)) {
      var h = e.data.altura;
      var f = document.getElementById('iframe-booster');
      if (f && typeof h === 'number' && h > 50) {
        f.style.height = h + 'px';
      }
    }
  });
</script>
```

---

## 4. Tabela de Parâmetros e Atributos `data-*`

O card pode ser customizado via atributos HTML na tag `<div id="rp">` no `index.html`:

| Atributo | Padrão | Descrição |
| :--- | :--- | :--- |
| `data-nome` | `""` | Nome do comprador. Se vazio, busca da URL ou via `postMessage`. |
| `data-premio` | `"Booster 110X"` | Nome principal do produto da oferta. |
| `data-campanha` | `"Kit Bobzão Bitruck + Ford F250"` | Título da ação/sorteio em andamento. |
| `data-cotas` | `"110 Cotas"` | Quantidade de títulos entregues no pacote. |
| `data-preco` | `"90"` | Preço promocional em reais. |
| `data-cta-url` | `"https://sinceropremios.com"` | Link direto de checkout do pacote. |
| `data-cta-label`| `"Garantir 110 Cotas por R$90"` | Texto exibido no botão principal de compra. |
| `data-timer-minutes` | `"3"` | Tempo em minutos para a contagem regressiva de urgência. |
| `data-video-src`| `""` | URL do arquivo `.mp4` do vídeo explicativo. Se vazio, oculta o player. |
| `data-threshold`| `"22"` | Porcentagem raspada necessária para acionar a revelação automática. |
| `data-origens` | `domínios autorizados` | Lista separada por vírgula das origens pai autorizadas a enviar mensagens. |

---

## 5. Contrato de Mensagens (`postMessage`)

A comunicação entre a página da rifa e o card no iframe utiliza três tipos de eventos seguros:

### 5.1 Mensagem Enviada pelo Card: `dx:altura`
Disparada toda vez que o tamanho do conteúdo muda (ex: ao raspar, na tela de carregamento ou ao carregar a página).
* **Payload:**
  ```javascript
  {
    tipo: "dx:altura",
    altura: 480 // valor em pixels (inteiro)
  }
  ```

### 5.2 Mensagem Enviada pelo Card: `card:pronto`
Disparada na inicialização para avisar à página-mãe que o card está renderizado e pronto para receber dados do comprador.
* **Payload:** `{ tipo: "card:pronto" }`

### 5.3 Mensagem Enviada pela Rifa: `dx:nome`
Se a rifa possuir o nome do comprador na sessão e quiser repassar ao card via JavaScript:
* **Payload:**
  ```javascript
  iframe.contentWindow.postMessage({
    tipo: "dx:nome",
    nome: "Isaias"
  }, "*");
  ```

---

## 6. Resolução do Nome do Comprador

Para oferecer uma experiência hiper-personalizada, o card resolve o primeiro nome do comprador seguindo a **ordem de precedência estrita**:

1. **Query String da URL:** Lê o parâmetro `?nome=` ou `?name=` da URL do iframe.
2. **Mensagem `postMessage` (`dx:nome`):** Recebida do pai após o carregamento.
3. **Atributo `data-nome`:** Preenchido diretamente no HTML ou via substituição no servidor.
4. **Fallback Padrão:** Caso nenhum nome seja encontrado, o card exibe de forma limpa e natural: *"Você desbloqueou uma condição exclusiva"*.

---

## 7. Parâmetros de URL (Query String)

Qualquer parâmetro anexado à URL do iframe sobrescreve as configurações padrão:

```
https://oferta.vendedorsincero.pro/rasp2/?nome=Isaias&checkout=https://sinceropremios.com/checkout/110x&utm_source=pos-compra
```

* `nome` / `name`: Personaliza os cabeçalhos com o nome do cliente.
* `checkout`: Substitui dinamicamente o link para onde o botão CTA redireciona.
* `utm_*`: Parâmetros de rastreamento mantidos no fluxo de conversão.

---

## 8. API Dinâmica e Telemetria (SSR)

Para cenários onde a plataforma da rifa (ou o CRM de tráfego) desejar parametrizar os dados da campanha no momento da requisição sem alterar arquivos físicos, o projeto disponibiliza um **motor de API em PHP** com renderização do lado do servidor (SSR).

### 8.1 Endpoint Principal do Widget (`/api/`)
* **URL:** `https://oferta.vendedorsincero.pro/api/`
* **Método:** `GET`
* **Como funciona:** O endpoint recebe os dados pela URL, carrega o layout da raspadinha, injeta os valores dinamicamente no HTML e devolve a resposta compilada pronta com os cabeçalhos de segurança de iframe.

**Parâmetros de Entrada:**
* `sid`: Identificador da campanha (ex: `booster110x`).
* `nome` ou `name`: Primeiro nome do comprador para personalizar o eyebrow e a topbar.
* `checkout`: URL de destino do botão de compra (permite enviar links de checkout com parâmetros de afiliado ou IDs de pedido específicos).
* `utm_*`: Todas as tags UTM são repassadas no fluxo de compra.

**Exemplo de Embed via API:**
```html
<iframe src="https://oferta.vendedorsincero.pro/api/?sid=booster110x&nome={{customer.name}}&checkout={{order.custom_checkout_url}}"></iframe>
```

---

### 8.2 Endpoint de Telemetria e Métricas (`/api/view.php`)
O card registra automaticamente métricas de conversão através de requisições assíncronas do tipo *Beacon*:
* **URL:** `https://oferta.vendedorsincero.pro/api/view.php`
* **Eventos Rastreados:**
  * `evento=view`: Registrado após 2 segundos de visualização na tela do cliente.
  * `evento=scratch`: Registrado assim que o cliente raspa a folha metálica e atinge a porcentagem de revelação.
  * `evento=click`: Registrado no momento do clique no botão CTA de checkout.

---

## 9. Navegação e Fuga de Iframe (Sandbox Buster)

Para evitar o erro crítico onde o checkout abre esmagado dentro da caixa do iframe:

1. **Tag `<base>` no Head:**
   ```html
   <base href="." target="_top">
   ```
2. **Cascata de Redirecionamento no JavaScript:**
   O clique do botão de compra executa 4 tentativas em sequência para atingir a janela principal:
   ```javascript
   function navegarCheckout(u) {
     try {
       if (window.top && window.top !== window) {
         window.top.location.href = u;
         return;
       }
     } catch (e) {}
     try { if (window.open(u, '_top')) return; } catch (e) {}
     try { if (window.open(u, '_blank')) return; } catch (e) {}
     window.location.href = u;
   }
   ```

---

## 10. Diagnóstico e Solução de Problemas

| Sintoma | Causa Mais Provável | Solução |
| :--- | :--- | :--- |
| **Iframe em branco** | O Apache da Hostinger injetou `X-Frame-Options: SAMEORIGIN`. | Suba o `.htaccess` fornecido para `/public_html/rasp2/`. |
| **Barra de rolagem dupla no celular** | O script de embed não adicionou o listener de `dx:altura`. | Verifique se o código do `embed-rifa.html` está completo na página da rifa. |
| **Nome não aparece** | A rifa não repassou `?nome=` na URL do iframe. | Utilize a ferramenta em `/rasp2/teste/` para inspecionar os parâmetros recebidos. |
| **Erro de redirecionamento 301** | O `src` foi configurado sem a barra final `/`. | Ajuste o link do card para terminar com `/rasp2/`. |

---

## 11. Checklist Pré-Publicação

- [ ] Arquivo `index.html` enviado para `/public_html/rasp2/index.html`.
- [ ] Arquivo `.htaccess` enviado para `/public_html/rasp2/.htaccess`.
- [ ] Acesso direto testado no navegador (`https://oferta.vendedorsincero.pro/rasp2/`).
- [ ] Teste de raspagem manual realizado no celular e no computador.
- [ ] Validação da transição suave e do carimbo "LIBERADO".
- [ ] Link do botão CTA verificado (deve levar diretamente para o checkout do Booster 110X).
- [ ] Bloco `embed-rifa.html` colado no painel da rifa.
- [ ] Teste real de compra para validar a abertura do card na tela de obrigado da rifa.
