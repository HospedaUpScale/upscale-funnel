/**
 * Componentes customizados do editor visual upScale.
 * Registra UMA VEZ (antes de loadProjectData) via veComponents.register(editor).
 * Todos usam a classe .ups-* definida no CSS do modelo (DefaultTemplate.php),
 * para que a prévia dentro do editor fique igual à página pública.
 */
(function (window) {
  'use strict';

  function register(editor) {
    var DomComponents = editor.DomComponents;

    // ─── SEÇÃO/CONTAINER ──────────────────────────────────────────────────
    DomComponents.addType('ups-section', {
      model: {
        defaults: {
          tagName: 'section',
          classes: ['ups-section'],
          droppable: true,
          traits: [
            { type: 'text', name: 'id', label: 'ID (âncora)' },
          ],
        },
      },
    });

    // ─── TÍTULO ───────────────────────────────────────────────────────────
    DomComponents.addType('ups-heading', {
      model: {
        defaults: {
          tagName: 'h2',
          classes: ['ups-heading'],
          editable: true,
          content: 'Novo título',
        },
      },
    });

    // ─── PARÁGRAFO/TEXTO ─────────────────────────────────────────────────
    DomComponents.addType('ups-text', {
      model: {
        defaults: {
          tagName: 'p',
          classes: ['ups-text'],
          editable: true,
          content: 'Digite seu texto aqui.',
        },
      },
    });

    // ─── BOTÃO/LINK ──────────────────────────────────────────────────────
    DomComponents.addType('ups-button', {
      model: {
        defaults: {
          tagName: 'a',
          classes: ['ups-button'],
          editable: true,
          attributes: { href: '#' },
          content: 'Clique aqui',
          traits: [
            { type: 'text', name: 'href', label: 'URL destino', placeholder: 'https://...' },
            { type: 'select', name: 'target', label: 'Abrir em', options: [
              { value: '',       name: 'Mesma aba' },
              { value: '_blank', name: 'Nova aba' },
            ]},
          ],
        },
      },
    });

    // ─── IMAGEM ──────────────────────────────────────────────────────────
    DomComponents.addType('ups-image', {
      extend: 'image',
      model: {
        defaults: {
          classes: ['ups-image'],
          traits: [
            { type: 'text',   name: 'alt', label: 'Texto alternativo' },
            { type: 'select', name: 'data-fit', label: 'Enquadramento', options: [
              { value: 'cover',   name: 'Preencher (cover)' },
              { value: 'contain', name: 'Caber (contain)' },
            ]},
          ],
        },
      },
    });

    // ─── VÍDEO INCORPORADO ──────────────────────────────────────────────
    // Provedores explicitamente suportados: YouTube e Vimeo embed URLs.
    DomComponents.addType('ups-video', {
      model: {
        defaults: {
          tagName: 'div',
          classes: ['ups-video'],
          droppable: false,
          attributes: { 'data-src': '' },
          traits: [
            { type: 'text', name: 'data-src', label: 'URL do vídeo (YouTube ou Vimeo)' },
          ],
          components: '<iframe allowfullscreen loading="lazy" src=""></iframe>',
        },
        init() {
          this.on('change:attributes:data-src', this.updateIframe);
          this.updateIframe();
        },
        updateIframe() {
          var raw = this.getAttributes()['data-src'] || this.getAttributes().src || '';
          var safe = normalizeVideoUrl(raw);
          var iframe = this.components().at(0);
          if (iframe && safe) {
            iframe.setAttributes(Object.assign({}, iframe.getAttributes(), { src: safe }));
          }
        },
      },
    });

    function normalizeVideoUrl(url) {
      if (!url) return '';
      try {
        var u = new URL(url, window.location.href);
        if (u.hostname.match(/^(www\.)?(youtube\.com|youtu\.be)$/)) {
          var id = u.hostname === 'youtu.be' ? u.pathname.slice(1) : u.searchParams.get('v');
          if (id) return 'https://www.youtube.com/embed/' + encodeURIComponent(id);
          if (u.pathname.startsWith('/embed/')) return u.href;
        }
        if (u.hostname.match(/vimeo\.com$/)) {
          var vid = u.pathname.replace('/', '');
          if (/^\d+$/.test(vid)) return 'https://player.vimeo.com/video/' + vid;
          if (u.hostname === 'player.vimeo.com') return u.href;
        }
        return '';
      } catch (e) {
        return '';
      }
    }

    // ─── BENEFÍCIOS ──────────────────────────────────────────────────────
    DomComponents.addType('ups-benefits', {
      model: {
        defaults: {
          tagName: 'div',
          classes: ['ups-benefits'],
          attributes: { 'data-items': '[]' },
          traits: [
            { type: 'text', name: 'data-items', label: 'Itens (JSON)',
              placeholder: '[{"icon":"🎯","title":"...","text":"..."}]' },
          ],
        },
        init() {
          this.on('change:attributes:data-items', this.render);
          this.render();
        },
        render() {
          var items;
          try { items = JSON.parse(this.getAttributes()['data-items'] || '[]'); } catch (e) { items = []; }
          var html = items.map(function (it) {
            return '<div class="ups-benefit">' +
              '<div class="ic">' + escapeHtml(it.icon || '✓') + '</div>' +
              '<h3>' + escapeHtml(it.title || '') + '</h3>' +
              '<p>' + escapeHtml(it.text || '') + '</p>' +
            '</div>';
          }).join('');
          this.components(html);
        },
      },
    });

    // ─── DEPOIMENTOS ─────────────────────────────────────────────────────
    DomComponents.addType('ups-testimonials', {
      model: {
        defaults: {
          tagName: 'div',
          classes: ['ups-testimonials'],
          attributes: { 'data-items': '[]' },
          traits: [
            { type: 'text', name: 'data-items', label: 'Depoimentos (JSON)',
              placeholder: '[{"name":"...","text":"..."}]' },
          ],
        },
        init() {
          this.on('change:attributes:data-items', this.render);
          this.render();
        },
        render() {
          var items;
          try { items = JSON.parse(this.getAttributes()['data-items'] || '[]'); } catch (e) { items = []; }
          var html = items.map(function (it) {
            return '<div class="ups-testimonial">' +
              '<div class="nm">' + escapeHtml(it.name || '') + '</div>' +
              '<div class="tx">' + escapeHtml(it.text || '') + '</div>' +
            '</div>';
          }).join('');
          this.components(html);
        },
      },
    });

    // ─── OFERTA ──────────────────────────────────────────────────────────
    DomComponents.addType('ups-offer', {
      model: {
        defaults: {
          tagName: 'div',
          classes: ['ups-offer'],
          attributes: { 'data-title': 'Oferta', 'data-price': 'R$ 0,00', 'data-cta': 'Comprar', 'data-url': '#' },
          traits: [
            { type: 'text', name: 'data-title', label: 'Título da oferta' },
            { type: 'text', name: 'data-price', label: 'Preço' },
            { type: 'text', name: 'data-cta',   label: 'Texto do botão' },
            { type: 'text', name: 'data-url',   label: 'URL do botão' },
          ],
        },
        init() {
          ['data-title','data-price','data-cta','data-url'].forEach(function (k) {
            this.on('change:attributes:' + k, this.render);
          }, this);
          this.render();
        },
        render() {
          var a = this.getAttributes();
          var html =
            '<div class="ti">' + escapeHtml(a['data-title'] || '') + '</div>' +
            '<div class="pr">' + escapeHtml(a['data-price'] || '') + '</div>' +
            '<a class="ups-button" href="' + escapeAttr(a['data-url'] || '#') + '">' + escapeHtml(a['data-cta'] || 'Comprar') + '</a>';
          this.components(html);
        },
      },
    });

    // ─── FAQ ─────────────────────────────────────────────────────────────
    DomComponents.addType('ups-faq', {
      model: {
        defaults: {
          tagName: 'div',
          classes: ['ups-faq'],
          attributes: { 'data-items': '[]' },
          traits: [
            { type: 'text', name: 'data-items', label: 'Perguntas (JSON)',
              placeholder: '[{"q":"...","a":"..."}]' },
          ],
        },
        init() {
          this.on('change:attributes:data-items', this.render);
          this.render();
        },
        render() {
          var items;
          try { items = JSON.parse(this.getAttributes()['data-items'] || '[]'); } catch (e) { items = []; }
          var html = items.map(function (it) {
            return '<details><summary>' + escapeHtml(it.q || '') + '</summary><div class="a">' + escapeHtml(it.a || '') + '</div></details>';
          }).join('');
          this.components(html);
        },
      },
    });

    // ─── TEMPORIZADOR ────────────────────────────────────────────────────
    // Comportamento: contagem regressiva por minutos (evergreen por padrão) ou
    // até uma data/hora final em ISO 8601. Reencerramento: oculta o bloco por
    // padrão, ou pode exibir "Oferta encerrada".
    DomComponents.addType('ups-timer', {
      model: {
        defaults: {
          tagName: 'div',
          classes: ['ups-timer'],
          attributes: { 'data-minutes': '15', 'data-end': '', 'data-onend': 'hide' },
          traits: [
            { type: 'text',   name: 'data-minutes', label: 'Minutos (evergreen)' },
            { type: 'text',   name: 'data-end',     label: 'Data final (ISO, opcional)', placeholder: 'YYYY-MM-DDTHH:MM:SS-03:00' },
            { type: 'select', name: 'data-onend',   label: 'Ao encerrar', options: [
              { value: 'hide',   name: 'Ocultar' },
              { value: 'label',  name: 'Mostrar "Oferta encerrada"' },
            ]},
          ],
          components:
            '<span class="b"><span data-t="h">00</span><small>h</small></span>' +
            '<span class="b"><span data-t="m">00</span><small>m</small></span>' +
            '<span class="b"><span data-t="s">00</span><small>s</small></span>',
        },
      },
    });

    // ─── RODAPÉ ──────────────────────────────────────────────────────────
    DomComponents.addType('ups-footer', {
      model: {
        defaults: {
          tagName: 'footer',
          classes: ['ups-footer'],
          attributes: { 'data-text': 'Rodapé da página' },
          traits: [
            { type: 'text', name: 'data-text', label: 'Texto do rodapé' },
          ],
        },
        init() { this.on('change:attributes:data-text', this.render); this.render(); },
        render() { this.components(escapeHtml(this.getAttributes()['data-text'] || '')); },
      },
    });

    // ── helpers ────────────────────────────────────────────────────────
    function escapeHtml(s) {
      return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }
    function escapeAttr(s) { return escapeHtml(s); }
  }

  // Script de interações da PÁGINA PÚBLICA (não vai pro editor).
  // É injetado no HTML publicado para que FAQ, timer e vídeo funcionem fora do editor.
  var publicRuntime = `
<script>
(function(){
  // Timer: respeita data-end (prioridade) ou data-minutes (evergreen por bloco+dia)
  document.querySelectorAll('.ups-timer').forEach(function(root){
    var end = null, onend = root.getAttribute('data-onend') || 'hide';
    var endAttr = root.getAttribute('data-end');
    if (endAttr) { var d = new Date(endAttr); if (!isNaN(d)) end = d; }
    if (!end) {
      var min = parseInt(root.getAttribute('data-minutes') || '15', 10);
      var key = 'ups_tmr_' + (root.id || Math.abs(hashCode(root.outerHTML)));
      var startedAt = parseInt(localStorage.getItem(key) || '0', 10);
      if (!startedAt) { startedAt = Date.now(); try { localStorage.setItem(key, String(startedAt)); } catch(e){} }
      end = new Date(startedAt + min * 60000);
    }
    var h = root.querySelector('[data-t="h"]'), m = root.querySelector('[data-t="m"]'), s = root.querySelector('[data-t="s"]');
    function pad(n){ return (n<10?'0':'')+n; }
    function tick(){
      var diff = Math.max(0, Math.floor((end - Date.now())/1000));
      if (h) h.textContent = pad(Math.floor(diff/3600));
      if (m) m.textContent = pad(Math.floor((diff%3600)/60));
      if (s) s.textContent = pad(diff%60);
      if (diff === 0) {
        clearInterval(iv);
        if (onend === 'label') root.innerHTML = '<span class="b">Oferta encerrada</span>';
        else root.style.display = 'none';
      }
    }
    tick();
    var iv = setInterval(tick, 1000);
  });
  function hashCode(str){ var h=0,i,c; for(i=0;i<str.length;i++){ c=str.charCodeAt(i); h=((h<<5)-h)+c; h|=0; } return h; }
})();
<\/script>
`;

  window.veComponents = { register: register, publicRuntime: publicRuntime };
})(window);
