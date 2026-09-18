# 🚀 Manual de Integração – Widget Raspadinha Booster 110X
### Plataforma: Sorteamos.com.br
**Campanha:** Vendedor Sincero (Kit Bobzão Bitruck + Ford F250)  
**Destinatário:** Administradores e Desenvolvedores da Sorteamos.com.br  

---

## 📌 1. Visão Geral da Integração

A plataforma Sorteamos possui suporte nativo a upsell pós-compra através da aba **DX HUB**.  
Para conectar nosso sistema próprio de upsell (Raspadinha Booster 110X) sem depender de intermediários, disponibilizamos **dois métodos**:

1. **Método 1 (Oficial e Recomendado - 2 Minutos):** Configuração nativa via Painel DX HUB da Sorteamos (sem alterar código da rifa).
2. **Método 2 (Alternativo / Manual):** Inserção de bloco HTML/Iframe diretamente no template da página de confirmação de pagamento.

---

## ⚡ Método 1: Integração Nativa no Painel Sorteamos (Aba DX HUB)

Acesse o painel administrativo da Sorteamos na seção **DX HUB** e preencha exatamente como indicado abaixo:

### Passo a Passo Visual:

1. **Configuração dos Interruptores:**
   * **Ativar Integração DX Mídia UP Sell:** ❌ **DESATIVADO (OFF)** *(Importante: deve ficar desligado para evitar conflito de provedores).*
   * **Ativar API DX Mídia:** ✅ **ATIVADO (ON)**

2. **Preenchimento dos Campos:**
   * **Base URL da DX Mídia \*:**
     ```
     https://oferta.vendedorsincero.pro/api
     ```
     *(Ou se a pasta física for `/upscale1/api`: `https://oferta.vendedorsincero.pro/upscale1/api`)*
   * **Token da API DX Mídia \*:**
     ```
     sincero-booster-110x
     ```

3. **Seção Capacidades:**
   * **Upsell:** ✅ **ATIVADO (ON)**
   * **Tipo de Upsell:** Marcar a opção **`[x] Iframe`** *(ou `[x] Personalizado`)*.

4. **Salvar Configurações:**
   * Clique no botão verde **`Salvar Configurações`**.
   * A Sorteamos enviará um teste de conexão (handshake) para a nossa API, que retornará `HTTP 200 OK` imediatamente.

### Como a Sorteamos e a API se comunicam no fluxo de compra:
* Quando o comprador conclui a compra de cotas, a Sorteamos faz uma requisição `POST` para a nossa API contendo os dados do comprador (`nome`, `telefone`, `order_id`).
* Nossa API responde com o JSON:
  ```json
  {
    "status": "success",
    "has_offer": true,
    "type": "iframe",
    "iframe_url": "https://oferta.vendedorsincero.pro/upscale1/index.html?nome=Isaias&order_id=123",
    "height": 640
  }
  ```
* O Sorteamos embute o card interativo e o comprador raspa a cartela já personalizada com o nome dele!

---

## 🧩 Método 2: Incorporação Manual no HTML (Fallback)

Caso prefira inserir o código diretamente na página de obrigado/sucesso da campanha, utilize o bloco abaixo:

```html
<div id="booster-wrap" style="width:100%;max-width:640px;margin:0 auto;line-height:0">
  <iframe
    id="booster-frame"
    src="https://oferta.vendedorsincero.pro/upscale1/"
    title="Sua condição especial"
    scrolling="no"
    allow="autoplay; encrypted-media"
    loading="eager"
    style="display:block;width:100%;border:0;height:900px;background:transparent"
  ></iframe>
</div>

<script>
(function () {
  'use strict';

  var CARD_URL = 'https://oferta.vendedorsincero.pro';
  var MERGE = '{{nome}}';
  var ALTURA_MIN = 620;

  var frame = document.getElementById('booster-frame');
  var enviado = '';

  function primeiroNome(s) {
    if (!s) return '';
    s = String(s).trim();
    if (s.indexOf('{{') === 0 || s.indexOf('${') === 0) return '';
    var p = s.split(/\s+/)[0];
    if (!p || p.length < 2 || p.length > 24) return '';
    if (/\d/.test(p)) return '';
    if (!/^[A-Za-zÀ-ÿ'-]+$/.test(p)) return '';
    return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
  }

  function daMergeTag() { return primeiroNome(MERGE); }

  function daUrl() {
    try {
      var q = new URLSearchParams(location.search);
      var chaves = ['nome', 'name', 'first_name', 'firstname', 'cliente', 'comprador'];
      for (var i = 0; i < chaves.length; i++) {
        var n = primeiroNome(q.get(chaves[i]));
        if (n) return n;
      }
    } catch (e) {}
    return '';
  }

  function envia(nome) {
    if (!nome || nome === enviado) return;
    enviado = nome;
    try {
      frame.contentWindow.postMessage({ tipo: 'nome', valor: nome }, CARD_URL);
    } catch (e) {}
  }

  window.addEventListener('message', function (e) {
    if (e.origin !== CARD_URL) return;
    var d = e.data;
    if (!d || typeof d !== 'object') return;

    if (d.tipo === 'pronto') {
      var n = daMergeTag() || daUrl();
      if (n) envia(n);
    }

    if (d.tipo === 'altura' && typeof d.valor === 'number') {
      var h = Math.max(ALTURA_MIN, Math.ceil(d.valor));
      frame.style.height = h + 'px';
    }
  });
})();
</script>
```

---

## 🔒 3. Resolução de Bloqueios e Segurança

* **CORS e Iframe:** Nosso servidor envia os headers `Content-Security-Policy: frame-ancestors *;` e `Access-Control-Allow-Origin: *`, permitindo o carregamento em `sinceropremios.com` e `sorteamos.com.br`.
* **Escape do Iframe no Checkout:** O botão de checkout do Booster 110X utiliza `target="_top"`, garantindo que o usuário seja levado diretamente ao carrinho na janela inteira sem ficar preso dentro do iframe.
