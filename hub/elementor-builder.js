/**
 * ══════════════════════════════════════════════════════════════════
 * UPSCALE ELEMENTOR-STYLE FUNNEL BUILDER
 * Motor de edição visual, isolamento em iframe, blocos dinâmicos,
 * temporizador evergreen, vinculação de ofertas Pix e histórico.
 * ══════════════════════════════════════════════════════════════════
 */

(function (window, document) {
  'use strict';

  // ══ ESTADO GLOBAL DO BUILDER ══
  var state = {
    funnelId: null,
    name: 'Oferta Especial',
    slug: 'oferta-especial',
    currentHtml: '',
    selectedBlockId: null,
    activeDevice: 'desktop',
    activeZoom: 75,
    canvasMode: 'edit', // 'edit' ou 'test'
    activeTab: 'elements', // 'elements', 'style', 'code'
    isModified: false,
    historyStack: [],
    historyIndex: -1,
    availableOffers: [],
    codeMirror: null,
    debounceTimer: null
  };

  // ══ TEMPLATE PADRÃO DE ALTA CONVERSÃO (INSPIRADO NA REFERÊNCIA) ══
  function getDefaultTemplate() {
    return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Oferta Especial — upScale</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
    body { background:#f4f7fb; color:#182230; line-height:1.5; padding:40px 16px; min-height:100vh; display:flex; flex-direction:column; align-items:center; }
    .funnel-container { width:100%; max-width:860px; margin:0 auto; }
    .badge-top { display:inline-block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:#175CFF; background:#edf2fe; padding:6px 14px; border-radius:999px; margin-bottom:14px; text-align:center; }
    .heading-hero { font-size:36px; font-weight:800; color:#0f172a; text-align:center; line-height:1.2; letter-spacing:-0.03em; margin-bottom:10px; }
    .subheading-hero { font-size:16px; color:#64748b; text-align:center; margin-bottom:28px; }
    
    /* Bloco Temporizador */
    .timer-wrap { display:flex; flex-direction:column; align-items:center; margin-bottom:34px; }
    .timer-label { font-size:12px; font-weight:600; color:#64748b; margin-bottom:8px; }
    .timer-boxes { display:flex; gap:10px; }
    .timer-box { background:#0f172a; color:#ffffff; padding:10px 16px; border-radius:10px; min-width:68px; text-align:center; }
    .timer-box strong { display:block; font-size:24px; font-weight:800; font-family:'Space Grotesk', sans-serif; }
    .timer-box span { font-size:10px; text-transform:uppercase; color:#94a3b8; font-weight:600; }

    /* Card da Oferta */
    .offer-card { background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; box-shadow:0 12px 35px -8px rgba(0,0,0,0.06); padding:24px; display:grid; grid-template-columns:1fr 1.2fr; gap:24px; align-items:center; margin-bottom:24px; }
    .offer-image-box { border-radius:14px; overflow:hidden; background:#06152d; aspect-ratio:4/3; display:flex; align-items:center; justify-content:center; }
    .offer-image-box img { width:100%; height:100%; object-fit:cover; display:block; }
    .offer-content { display:flex; flex-direction:column; gap:10px; }
    .offer-title { font-size:24px; font-weight:800; color:#0f172a; }
    .offer-desc { font-size:13px; color:#64748b; }
    .offer-price-row { display:flex; align-items:baseline; gap:8px; margin:4px 0; }
    .offer-price { font-size:32px; font-weight:800; color:#175CFF; }
    .offer-old-price { font-size:14px; color:#94a3b8; text-decoration:line-through; }
    .offer-benefits { list-style:none; display:flex; flex-direction:column; gap:6px; margin:8px 0; }
    .offer-benefits li { font-size:13px; font-weight:500; color:#334155; display:flex; align-items:center; gap:8px; }
    .offer-benefits li svg { width:16px; height:16px; color:#175CFF; flex-shrink:0; }

    /* Botão CTA Principal */
    .btn-cta { display:inline-flex; align-items:center; justify-content:center; gap:8px; width:100%; background:#175CFF; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none; padding:15px 24px; border-radius:12px; border:none; cursor:pointer; box-shadow:0 4px 14px rgba(23,92,255,0.3); transition:all 0.2s ease; }
    .btn-cta:hover { background:#0f4de6; transform:translateY(-1px); box-shadow:0 6px 20px rgba(23,92,255,0.4); }

    .footer-note { text-align:center; font-size:11.5px; color:#94a3b8; margin-top:20px; }

    @media (max-width: 640px) {
      .heading-hero { font-size:26px; }
      .offer-card { grid-template-columns:1fr; gap:16px; }
      body { padding:24px 12px; }
    }
  </style>
</head>
<body>
  <div class="funnel-container">
    
    <div style="text-align:center;" data-block-id="blk_top_badge" data-block-type="container">
      <span class="badge-top">CONDIÇÃO ESPECIAL</span>
    </div>

    <h1 class="heading-hero" data-block-id="blk_hero_title" data-block-type="heading">Sua próxima oportunidade começa aqui</h1>
    <p class="subheading-hero" data-block-id="blk_hero_sub" data-block-type="text">Confira os detalhes e escolha sua oferta.</p>

    <!-- Bloco Temporizador -->
    <div class="timer-wrap" data-block-id="blk_timer_1" data-block-type="timer" data-timer-type="evergreen" data-timer-duration="15" data-timer-end="">
      <span class="timer-label">Oferta disponível até</span>
      <div class="timer-boxes">
        <div class="timer-box"><strong class="t-hours">02</strong><span>Horas</span></div>
        <div class="timer-box"><strong class="t-mins">14</strong><span>Minutos</span></div>
        <div class="timer-box"><strong class="t-secs">38</strong><span>Segundos</span></div>
      </div>
    </div>

    <!-- Bloco Oferta Especial -->
    <div class="offer-card" data-block-id="blk_offer_1" data-block-type="offer">
      <div class="offer-image-box">
        <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&auto=format&fit=crop&q=80" alt="Booster 200X Ouro">
      </div>
      <div class="offer-content">
        <h3 class="offer-title">Booster 200X Ouro</h3>
        <p class="offer-desc">Conheça as condições desta oferta.</p>
        <div class="offer-price-row">
          <span class="offer-price">R$ 120,00</span>
          <span class="offer-old-price">R$ 190,00</span>
        </div>
        <ul class="offer-benefits">
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Detalhes da oferta</span>
          </li>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            <span>Pagamento via Pix</span>
          </li>
          <li>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Checkout seguro</span>
          </li>
        </ul>
        <div style="margin-top:10px;">
          <a href="#checkout" class="btn-cta" data-block-id="blk_btn_1" data-block-type="button" data-dest-type="checkout" data-offer-id="1">Quero aproveitar →</a>
        </div>
      </div>
    </div>

    <p class="footer-note" data-block-id="blk_footer_note" data-block-type="text">Consultar condições da oferta.</p>
  </div>

  <!-- Runtime de contagem regressiva resiliente -->
  <script>
    (function() {
      var timerEls = document.querySelectorAll('[data-block-type="timer"]');
      timerEls.forEach(function(el) {
        var timerId = el.getAttribute('data-block-id') || 'default_timer';
        var type = el.getAttribute('data-timer-type') || 'evergreen';
        var durationMins = parseInt(el.getAttribute('data-timer-duration') || '15', 10);
        var targetTime;

        if (type === 'evergreen') {
          var storageKey = 'upscale_timer_' + timerId;
          var saved = localStorage.getItem(storageKey);
          if (saved && !isNaN(parseInt(saved, 10)) && parseInt(saved, 10) > Date.now()) {
            targetTime = parseInt(saved, 10);
          } else {
            targetTime = Date.now() + (durationMins * 60 * 1000);
            localStorage.setItem(storageKey, targetTime);
          }
        } else {
          var endStr = el.getAttribute('data-timer-end');
          targetTime = endStr ? new Date(endStr).getTime() : (Date.now() + 3600000);
        }

        var hEl = el.querySelector('.t-hours');
        var mEl = el.querySelector('.t-mins');
        var sEl = el.querySelector('.t-secs');

        function tick() {
          var diff = Math.max(0, targetTime - Date.now());
          var totalSecs = Math.floor(diff / 1000);
          var h = Math.floor(totalSecs / 3600);
          var m = Math.floor((totalSecs % 3600) / 60);
          var s = totalSecs % 60;

          if (hEl) hEl.textContent = String(h).padStart(2, '0');
          if (mEl) mEl.textContent = String(m).padStart(2, '0');
          if (sEl) sEl.textContent = String(s).padStart(2, '0');

          if (diff <= 0) {
            var action = el.getAttribute('data-end-action') || 'message';
            if (action === 'message') {
              el.innerHTML = '<div style="font-weight:700;color:#ef4444;padding:8px;text-align:center;">Promoção finalizada!</div>';
            }
          }
        }
        tick();
        setInterval(tick, 1000);
      });
    })();
  <\/script>
</body>
</html>`;
  }

  // ══ SCRIPT INJETADO NO IFRAME PARA O MODO "EDITAR" E POSTMESSAGE ══
  function getIframeHelperScript() {
    return `<script id="__elementor_inspector_script__">
    (function() {
      var currentSelected = null;
      var activeMode = 'edit';

      var styleTag = document.createElement('style');
      styleTag.id = '__elementor_styles__';
      styleTag.textContent = \`
        [data-block-id] { position: relative; transition: outline 0.15s ease; }
        .elementor-hover-outline { outline: 2px dashed #175CFF !important; cursor: pointer !important; }
        .elementor-active-selected { outline: 2px solid #175CFF !important; box-shadow: 0 0 0 3px rgba(23,92,255,0.2) !important; position: relative !important; }
        .elementor-active-selected::after {
          content: ''; position: absolute; top: -4px; right: -4px; width: 8px; height: 8px; background: #175CFF; border: 2px solid #ffffff; border-radius: 50%; pointer-events: none;
        }
        .elementor-active-selected::before {
          content: ''; position: absolute; bottom: -4px; left: -4px; width: 8px; height: 8px; background: #175CFF; border: 2px solid #ffffff; border-radius: 50%; pointer-events: none;
        }
      \`;
      document.head.appendChild(styleTag);

      function findBlockParent(el) {
        while (el && el !== document.body) {
          if (el.getAttribute && el.getAttribute('data-block-id')) return el;
          el = el.parentElement;
        }
        return null;
      }

      document.addEventListener('mouseover', function(e) {
        if (activeMode !== 'edit') return;
        var block = findBlockParent(e.target);
        if (block && block !== currentSelected) {
          block.classList.add('elementor-hover-outline');
        }
      });

      document.addEventListener('mouseout', function(e) {
        if (activeMode !== 'edit') return;
        var block = findBlockParent(e.target);
        if (block && block !== currentSelected) {
          block.classList.remove('elementor-hover-outline');
        }
      });

      document.addEventListener('click', function(e) {
        if (activeMode !== 'edit') return;
        var block = findBlockParent(e.target);
        if (block) {
          e.preventDefault();
          e.stopPropagation();

          if (currentSelected) {
            currentSelected.classList.remove('elementor-active-selected');
          }
          currentSelected = block;
          currentSelected.classList.remove('elementor-hover-outline');
          currentSelected.classList.add('elementor-active-selected');

          var blockId = block.getAttribute('data-block-id');
          var blockType = block.getAttribute('data-block-type');
          var textContent = block.innerText || '';
          var tag = block.tagName.toLowerCase();

          window.parent.postMessage({
            type: 'elementor:select',
            blockId: blockId,
            blockType: blockType,
            tag: tag,
            text: textContent.trim().substring(0, 80),
            destType: block.getAttribute('data-dest-type') || '',
            offerId: block.getAttribute('data-offer-id') || '',
            href: block.getAttribute('href') || ''
          }, '*');
        } else {
          if (currentSelected) {
            currentSelected.classList.remove('elementor-active-selected');
            currentSelected = null;
          }
          window.parent.postMessage({ type: 'elementor:unselect' }, '*');
        }
      }, true);

      // Listener para comandos do painel pai
      window.addEventListener('message', function(event) {
        if (!event.data || !event.data.action) return;
        
        if (event.data.action === 'setMode') {
          activeMode = event.data.mode;
          if (activeMode === 'test') {
            document.querySelectorAll('.elementor-hover-outline, .elementor-active-selected').forEach(function(el) {
              el.classList.remove('elementor-hover-outline', 'elementor-active-selected');
            });
            currentSelected = null;
          }
        }

        if (event.data.action === 'selectBlock') {
          var b = document.querySelector('[data-block-id="' + event.data.blockId + '"]');
          if (b) {
            if (currentSelected) currentSelected.classList.remove('elementor-active-selected');
            currentSelected = b;
            currentSelected.classList.add('elementor-active-selected');
            b.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        }

        if (event.data.action === 'resetTimer') {
          for (var i = 0; i < localStorage.length; i++) {
            var k = localStorage.key(i);
            if (k && k.indexOf('upscale_timer_') === 0) {
              localStorage.removeItem(k);
            }
          }
          window.location.reload();
        }
      });
    })();
    <\/script>`;
  }

  // ══ INICIALIZAR O BUILDER ══
  function initBuilder() {
    setupCodeMirror();
    carregarOfertasWorkspace();
    setupEventListeners();
    carregarFunilInicial();
  }

  // ══ CARREGAR OFERTAS DO SISTEMA PARA VINCULAÇÃO ══
  function carregarOfertasWorkspace() {
    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php?action=get_offers&workspace_id=' + wsId)
      .then(function (data) {
        state.availableOffers = data.offers || [];
      })
      .catch(function (e) {
        console.warn('Ofertas não carregadas para dropdown:', e);
      });
  }

  // ══ CONFIGURAÇÃO DO EDITOR CODEMIRROR ══
  function setupCodeMirror() {
    var textarea = document.getElementById('funnel-code-editor');
    if (!textarea) return;

    if (window.CodeMirror) {
      state.codeMirror = window.CodeMirror.fromTextArea(textarea, {
        lineNumbers: true,
        mode: 'htmlmixed',
        theme: 'eclipse',
        lineWrapping: true,
        tabSize: 2,
        autoCloseTags: true,
        autoCloseBrackets: true
      });

      state.codeMirror.on('change', function () {
        if (state.canvasMode === 'edit') {
          clearTimeout(state.debounceTimer);
          state.debounceTimer = setTimeout(function () {
            var val = state.codeMirror.getValue();
            if (val !== state.currentHtml) {
              state.currentHtml = val;
              renderPreview(false); // atualiza sem recriar histórico a cada tecla
              marcarModificado();
            }
          }, 450);
        }
      });
    }
  }

  // ══ RENDERIZAR PRÉVIA NO IFRAME ISOLADO ══
  function renderPreview(pushHistory) {
    var iframe = (state.activeDevice === 'mobile')
      ? document.getElementById('builder-iframe-mobile')
      : document.getElementById('builder-iframe');

    if (!iframe) return;

    if (pushHistory !== false) {
      pushStateToHistory(state.currentHtml);
    }

    // Garante que o script do Elementor Runtime seja injetado no final do body
    var helperScript = getIframeHelperScript();
    var htmlToInject = state.currentHtml;

    if (htmlToInject.indexOf('</body>') !== -1) {
      htmlToInject = htmlToInject.replace('</body>', helperScript + '</body>');
    } else {
      htmlToInject = htmlToInject + helperScript;
    }

    iframe.srcdoc = htmlToInject;

    // Atualiza o display da URL pública no footer da barra lateral
    var urlDisplay = document.getElementById('sidebar-public-url-display');
    var openBtn = document.getElementById('sidebar-open-url-btn');
    var pubUrl = window.location.origin + '/f/' + (state.slug || 'pagina');
    if (urlDisplay) urlDisplay.textContent = window.location.host + '/f/' + (state.slug || 'pagina');
    if (openBtn) openBtn.href = pubUrl;

    // Notifica modo atual para o iframe logo que carregar
    iframe.onload = function () {
      iframe.contentWindow.postMessage({ action: 'setMode', mode: state.canvasMode }, '*');
      if (state.selectedBlockId) {
        iframe.contentWindow.postMessage({ action: 'selectBlock', blockId: state.selectedBlockId }, '*');
      }
    };
  }

  // ══ HISTÓRICO UNDO / REDO ══
  function pushStateToHistory(html) {
    if (state.historyIndex >= 0 && state.historyStack[state.historyIndex] === html) return;
    
    // Remove estados futuros se estiver no meio da pilha
    state.historyStack = state.historyStack.slice(0, state.historyIndex + 1);
    state.historyStack.push(html);
    if (state.historyStack.length > 30) state.historyStack.shift();
    state.historyIndex = state.historyStack.length - 1;

    updateUndoRedoButtons();
  }

  function builderUndo() {
    if (state.historyIndex > 0) {
      state.historyIndex--;
      state.currentHtml = state.historyStack[state.historyIndex];
      if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
      renderPreview(false);
      updateUndoRedoButtons();
      marcarModificado();
    }
  }

  function builderRedo() {
    if (state.historyIndex < state.historyStack.length - 1) {
      state.historyIndex++;
      state.currentHtml = state.historyStack[state.historyIndex];
      if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
      renderPreview(false);
      updateUndoRedoButtons();
      marcarModificado();
    }
  }

  function updateUndoRedoButtons() {
    var btnUndo = document.getElementById('btn-builder-undo');
    var btnRedo = document.getElementById('btn-builder-redo');
    if (btnUndo) btnUndo.disabled = (state.historyIndex <= 0);
    if (btnRedo) btnRedo.disabled = (state.historyIndex >= state.historyStack.length - 1);
  }

  // ══ ALTERNAR DISPOSITIVOS (DESKTOP / TABLET / MOBILE) ══
  function setBuilderDevice(device) {
    state.activeDevice = device;
    var wrap = document.getElementById('elementor-viewport-wrap');
    var standardBox = document.getElementById('standard-screen-box');
    var iphoneBox = document.getElementById('iphone-screen-box');
    var infoText = document.getElementById('bottom-device-info-text');

    document.querySelectorAll('.elementor-device-btn').forEach(function (b) { b.classList.remove('active'); });
    var activeBtn = document.getElementById('btn-device-' + device);
    if (activeBtn) activeBtn.classList.add('active');

    if (device === 'mobile') {
      wrap.className = 'elementor-viewport-wrap device-mobile';
      standardBox.style.display = 'none';
      iphoneBox.style.display = 'block';
      if (infoText) infoText.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg> <span>iPhone 15 — 393 × 852 px</span>';
    } else if (device === 'tablet') {
      wrap.className = 'elementor-viewport-wrap device-tablet';
      standardBox.style.display = 'block';
      iphoneBox.style.display = 'none';
      if (infoText) infoText.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg> <span>Tablet — 768 px</span>';
    } else {
      wrap.className = 'elementor-viewport-wrap device-desktop';
      standardBox.style.display = 'block';
      iphoneBox.style.display = 'none';
      if (infoText) infoText.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg> <span>Desktop — 1440 px</span>';
    }

    renderPreview(false);
  }

  // ══ CONTROLE DE ZOOM ══
  function setBuilderZoom(val) {
    state.activeZoom = parseInt(val, 10);
    var wrap = document.getElementById('elementor-viewport-wrap');
    if (!wrap) return;
    var scale = state.activeZoom / 100;
    if (scale === 1) {
      wrap.style.transform = 'none';
    } else {
      wrap.style.transform = 'scale(' + scale + ')';
      wrap.style.transformOrigin = 'top center';
    }
  }

  // ══ ALTERNAR MODO: EDITAR VS TESTAR ══
  function setCanvasMode(mode) {
    state.canvasMode = mode;
    document.getElementById('btn-mode-edit').classList.toggle('active', mode === 'edit');
    document.getElementById('btn-mode-test').classList.toggle('active', mode === 'test');

    var iframe = (state.activeDevice === 'mobile')
      ? document.getElementById('builder-iframe-mobile')
      : document.getElementById('builder-iframe');

    if (iframe && iframe.contentWindow) {
      iframe.contentWindow.postMessage({ action: 'setMode', mode: mode }, '*');
    }

    if (mode === 'test') {
      desmarcarElemento();
      window.showToast && window.showToast('Modo de teste ativo: clique em botões e links normalmente.', 'success');
    }
  }

  // ══ ALTERNAR ABAS DA SIDEBAR (ELEMENTOS, ESTILO, CÓDIGO) ══
  function switchSidebarTab(tabName) {
    state.activeTab = tabName;
    document.getElementById('tab-btn-elements').classList.toggle('active', tabName === 'elements');
    document.getElementById('tab-btn-style').classList.toggle('active', tabName === 'style');
    document.getElementById('tab-btn-code').classList.toggle('active', tabName === 'code');

    document.getElementById('pane-elements').style.display = (tabName === 'elements') ? 'block' : 'none';
    document.getElementById('pane-style').style.display = (tabName === 'style') ? 'block' : 'none';
    document.getElementById('pane-code').style.display = (tabName === 'code') ? 'block' : 'none';

    if (tabName === 'code' && state.codeMirror) {
      setTimeout(function () { state.codeMirror.refresh(); }, 50);
    }
  }

  // ══ INSERÇÃO DE NOVOS BLOCOS ══
  function inserirBloco(type) {
    var newId = 'blk_' + type + '_' + Date.now().toString(36);
    var snippet = '';

    switch (type) {
      case 'heading':
        snippet = `\n<h2 data-block-id="${newId}" data-block-type="heading" style="font-size:26px; font-weight:700; margin:16px 0; color:#0f172a; text-align:center;">Novo Título Impactante</h2>`;
        break;
      case 'text':
        snippet = `\n<p data-block-id="${newId}" data-block-type="text" style="font-size:15px; color:#475569; line-height:1.6; margin:12px 0;">Digite aqui o seu texto explicativo ou argumentos de persuasão para o cliente.</p>`;
        break;
      case 'image':
        snippet = `\n<div data-block-id="${newId}" data-block-type="image" style="text-align:center; margin:16px 0;"><img src="https://images.unsplash.com/photo-1557821552-17105176677c?w=700&auto=format&fit=crop&q=80" alt="Imagem" style="max-width:100%; border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,0.08);"></div>`;
        break;
      case 'video':
        snippet = `\n<div data-block-id="${newId}" data-block-type="video" style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:14px; margin:18px 0; background:#000;"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allowfullscreen></iframe></div>`;
        break;
      case 'button':
        var defaultOffer = state.availableOffers[0] || { id: 1, title: 'Oferta Especial' };
        snippet = `\n<div style="text-align:center; margin:16px 0;"><a href="#checkout" data-block-id="${newId}" data-block-type="button" data-dest-type="checkout" data-offer-id="${defaultOffer.id}" class="btn-cta" style="display:inline-block; max-width:320px;">Garantir Minha Vaga →</a></div>`;
        break;
      case 'container':
        snippet = `\n<div data-block-id="${newId}" data-block-type="container" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; margin:20px 0;"><p style="color:#64748b; font-size:13px; text-align:center;">Container vazio. Adicione elementos dentro.</p></div>`;
        break;
      case 'html':
        snippet = `\n<div data-block-id="${newId}" data-block-type="html" style="margin:14px 0;"><!-- Código HTML Personalizado -->\n<div style="padding:12px; background:#f1f5f9; border-radius:8px; font-family:monospace; font-size:12px;">Bloco HTML livre</div></div>`;
        break;
      case 'timer':
        snippet = `\n<div class="timer-wrap" data-block-id="${newId}" data-block-type="timer" data-timer-type="evergreen" data-timer-duration="15" style="margin:20px 0;"><span class="timer-label">Oferta disponível por tempo limitado:</span><div class="timer-boxes"><div class="timer-box"><strong class="t-hours">00</strong><span>Horas</span></div><div class="timer-box"><strong class="t-mins">15</strong><span>Minutos</span></div><div class="timer-box"><strong class="t-secs">00</strong><span>Segundos</span></div></div></div>`;
        break;
      case 'offer':
        var off = state.availableOffers[0] || { title: 'Booster Diamante', price: 97.00, quota_count: 100 };
        snippet = `\n<div class="offer-card" data-block-id="${newId}" data-block-type="offer" style="margin:20px 0;"><div class="offer-image-box"><img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&auto=format&fit=crop&q=80" alt="${off.title}"></div><div class="offer-content"><h3 class="offer-title">${off.title}</h3><p class="offer-desc">Super condição exclusiva com cotas extras.</p><div class="offer-price-row"><span class="offer-price">R$ ${Number(off.price).toFixed(2).replace('.', ',')}</span></div><a href="#checkout" class="btn-cta" data-block-id="btn_${newId}" data-block-type="button" data-dest-type="checkout" data-offer-id="${off.id}">Aproveitar agora →</a></div></div>`;
        break;
      case 'spacer':
        snippet = `\n<div data-block-id="${newId}" data-block-type="spacer" style="height:32px;"></div>`;
        break;
    }

    // Inserir antes do fechamento do container principal ou antes do body
    if (state.currentHtml.indexOf('</div>\n\n  <!-- Runtime') !== -1) {
      state.currentHtml = state.currentHtml.replace('</div>\n\n  <!-- Runtime', snippet + '\n  </div>\n\n  <!-- Runtime');
    } else if (state.currentHtml.indexOf('</body>') !== -1) {
      state.currentHtml = state.currentHtml.replace('</body>', snippet + '\n</body>');
    } else {
      state.currentHtml += snippet;
    }

    if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
    renderPreview(true);
    marcarModificado();
    window.showToast && window.showToast('Elemento adicionado!', 'success');
  }

  // ══ SELEÇÃO E INSPEÇÃO DE ELEMENTO NO PAINEL LATERAL ══
  function selecionarElementoPeloId(data) {
    state.selectedBlockId = data.blockId;
    var inspectorBox = document.getElementById('builder-inspector-box');
    var fieldsBox = document.getElementById('inspector-fields-container');
    var typeTitle = document.getElementById('inspector-element-name');

    if (!inspectorBox || !fieldsBox) return;

    inspectorBox.style.display = 'flex';
    var type = data.blockType || 'element';
    typeTitle.textContent = formatarNomeTipo(type);

    // Constrói os campos específicos para cada tipo de bloco
    var fieldsHtml = '';

    if (type === 'button') {
      var offersOptions = state.availableOffers.map(function(o) {
        var sel = String(o.id) === String(data.offerId) ? 'selected' : '';
        return `<option value="${o.id}" ${sel}>${escapeHtml(o.title)} (R$ ${Number(o.price).toFixed(2)})</option>`;
      }).join('');

      fieldsHtml = `
        <div class="elementor-prop-row">
          <label class="elementor-prop-label">Texto do Botão</label>
          <input type="text" class="elementor-prop-input" id="prop-btn-text" value="${escapeHtml(data.text || 'Quero aproveitar')}" oninput="atualizarPropriedadeBloco('button', 'text', this.value)">
        </div>
        <div class="elementor-prop-row">
          <label class="elementor-prop-label">Destino do Clique</label>
          <select class="elementor-prop-select" id="prop-btn-dest" onchange="atualizarDestinoBotao(this.value)">
            <option value="checkout" ${data.destType === 'checkout' ? 'selected' : ''}>Checkout da oferta (Pix)</option>
            <option value="whatsapp" ${data.destType === 'whatsapp' ? 'selected' : ''}>Conversa no WhatsApp</option>
            <option value="external" ${data.destType === 'external' ? 'selected' : ''}>URL externa</option>
            <option value="anchor" ${data.destType === 'anchor' ? 'selected' : ''}>Seção da página (#id)</option>
          </select>
        </div>
        <div class="elementor-prop-row" id="group-btn-offer" style="${data.destType === 'whatsapp' || data.destType === 'external' || data.destType === 'anchor' ? 'display:none;' : ''}">
          <label class="elementor-prop-label">Oferta Vinculada</label>
          <select class="elementor-prop-select" id="prop-btn-offer-select" onchange="atualizarPropriedadeBloco('button', 'offer_id', this.value)">
            ${offersOptions || '<option value="1">Oferta Padrão</option>'}
          </select>
        </div>
        <div class="elementor-prop-row" id="group-btn-external" style="${data.destType !== 'external' && data.destType !== 'anchor' ? 'display:none;' : ''}">
          <label class="elementor-prop-label">Link de Destino</label>
          <input type="text" class="elementor-prop-input" id="prop-btn-href" value="${escapeHtml(data.href || 'https://')}" oninput="atualizarPropriedadeBloco('button', 'href', this.value)">
        </div>
        <div class="elementor-switch-wrap">
          <span class="elementor-switch-label">Abrir em nova aba</span>
          <label class="elementor-toggle-switch">
            <input type="checkbox" id="prop-btn-target-blank" onchange="atualizarPropriedadeBloco('button', 'target', this.checked)">
            <span class="elementor-toggle-slider"></span>
          </label>
        </div>
      `;
    } else if (type === 'timer') {
      fieldsHtml = `
        <div class="elementor-prop-row">
          <label class="elementor-prop-label">Tipo de Temporizador</label>
          <select class="elementor-prop-select" id="prop-timer-type" onchange="atualizarPropriedadeBloco('timer', 'timer_type', this.value)">
            <option value="evergreen" selected>Duração por visitante (Evergreen)</option>
            <option value="fixed">Prazo fixo (Data e hora)</option>
          </select>
        </div>
        <div class="elementor-prop-row">
          <label class="elementor-prop-label">Duração em minutos</label>
          <input type="number" class="elementor-prop-input" id="prop-timer-duration" value="15" min="1" max="1440" oninput="atualizarPropriedadeBloco('timer', 'duration', this.value)">
        </div>
        <div class="elementor-prop-row">
          <label class="elementor-prop-label">Ação ao encerrar</label>
          <select class="elementor-prop-select" id="prop-timer-action" onchange="atualizarPropriedadeBloco('timer', 'end_action', this.value)">
            <option value="message">Exibir mensagem de encerramento</option>
            <option value="hide">Ocultar a oferta</option>
            <option value="disable">Desabilitar botão de compra</option>
          </select>
        </div>
        <button type="button" class="btn-secondary" style="margin-top:6px; min-height:30px; font-size:11px;" onclick="reiniciarTimerPreview()">
          🔄 Reiniciar contador na prévia (teste)
        </button>
      `;
    } else if (type === 'heading' || type === 'text') {
      fieldsHtml = `
        <div class="elementor-prop-row">
          <label class="elementor-prop-label">Texto</label>
          <textarea class="elementor-prop-textarea" id="prop-text-content" rows="3" oninput="atualizarPropriedadeBloco('${type}', 'text', this.value)">${escapeHtml(data.text || '')}</textarea>
        </div>
      `;
    } else if (type === 'offer') {
      var offersOpts = state.availableOffers.map(function(o) {
        return `<option value="${o.id}">${escapeHtml(o.title)} — R$ ${Number(o.price).toFixed(2)}</option>`;
      }).join('');
      fieldsHtml = `
        <div class="elementor-prop-row">
          <label class="elementor-prop-label">Preencher com Oferta do Sistema</label>
          <select class="elementor-prop-select" onchange="vincularOfertaAoBloco(this.value)">
            <option value="">Selecione para auto-preencher...</option>
            ${offersOpts}
          </select>
        </div>
        <div class="elementor-prop-row">
          <label class="elementor-prop-label">Título da Oferta</label>
          <input type="text" class="elementor-prop-input" id="prop-offer-title" value="Booster 200X Ouro" oninput="atualizarPropriedadeBloco('offer', 'title', this.value)">
        </div>
        <div class="elementor-prop-row">
          <label class="elementor-prop-label">Preço (R$)</label>
          <input type="text" class="elementor-prop-input" id="prop-offer-price" value="R$ 120,00" oninput="atualizarPropriedadeBloco('offer', 'price', this.value)">
        </div>
      `;
    } else {
      fieldsHtml = `
        <p style="font-size:12px; color:#64748b;">Edite os estilos finos deste elemento na aba <strong>Estilo</strong> ou seu código bruto na aba <strong>Código</strong>.</p>
      `;
    }

    fieldsBox.innerHTML = fieldsHtml;
  }

  function formatarNomeTipo(type) {
    var map = {
      heading: 'Título Selecionado',
      text: 'Texto Selecionado',
      image: 'Imagem Selecionada',
      video: 'Vídeo Selecionado',
      button: 'Botão Selecionado',
      container: 'Container Selecionado',
      html: 'HTML Selecionado',
      timer: 'Temporizador Selecionado',
      offer: 'Oferta Selecionada',
      spacer: 'Espaçador Selecionado'
    };
    return map[type] || 'Elemento Selecionado';
  }

  function desmarcarElemento() {
    state.selectedBlockId = null;
    var inspectorBox = document.getElementById('builder-inspector-box');
    if (inspectorBox) inspectorBox.style.display = 'none';

    var iframe = (state.activeDevice === 'mobile')
      ? document.getElementById('builder-iframe-mobile')
      : document.getElementById('builder-iframe');

    if (iframe && iframe.contentWindow) {
      iframe.contentWindow.postMessage({ action: 'setMode', mode: state.canvasMode }, '*');
    }
  }

  function atualizarDestinoBotao(val) {
    var offGroup = document.getElementById('group-btn-offer');
    var extGroup = document.getElementById('group-btn-external');
    if (offGroup) offGroup.style.display = (val === 'checkout') ? 'flex' : 'none';
    if (extGroup) extGroup.style.display = (val === 'external' || val === 'anchor') ? 'flex' : 'none';
    atualizarPropriedadeBloco('button', 'dest_type', val);
  }

  // ══ ATUALIZAR PROPRIEDADE DO BLOCO NO HTML ══
  function atualizarPropriedadeBloco(type, prop, value) {
    if (!state.selectedBlockId) return;

    var parser = new DOMParser();
    var doc = parser.parseFromString(state.currentHtml, 'text/html');
    var el = doc.querySelector('[data-block-id="' + state.selectedBlockId + '"]');
    if (!el) return;

    if (type === 'button') {
      if (prop === 'text') el.textContent = value;
      if (prop === 'dest_type') el.setAttribute('data-dest-type', value);
      if (prop === 'offer_id') {
        el.setAttribute('data-offer-id', value);
        el.setAttribute('href', '#checkout-offer-' + value);
      }
      if (prop === 'href') el.setAttribute('href', value);
      if (prop === 'target') el.setAttribute('target', value ? '_blank' : '_self');
    } else if (type === 'heading' || type === 'text') {
      if (prop === 'text') el.textContent = value;
    } else if (type === 'timer') {
      if (prop === 'timer_type') el.setAttribute('data-timer-type', value);
      if (prop === 'duration') el.setAttribute('data-timer-duration', value);
      if (prop === 'end_action') el.setAttribute('data-end-action', value);
    } else if (type === 'offer') {
      if (prop === 'title') {
        var t = el.querySelector('.offer-title');
        if (t) t.textContent = value;
      }
      if (prop === 'price') {
        var p = el.querySelector('.offer-price');
        if (p) p.textContent = value;
      }
    }

    state.currentHtml = '<!DOCTYPE html>\n' + doc.documentElement.outerHTML;
    if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
    renderPreview(true);
    marcarModificado();
  }

  // ══ APLICAR ESTILO RÁPIDO DO PAINEL DE ESTILO ══
  function aplicarEstiloRapido(styleProp, value) {
    if (!state.selectedBlockId) {
      window.showToast && window.showToast('Selecione primeiro um elemento na prévia para estilizar.', 'neutral');
      return;
    }

    var parser = new DOMParser();
    var doc = parser.parseFromString(state.currentHtml, 'text/html');
    var el = doc.querySelector('[data-block-id="' + state.selectedBlockId + '"]');
    if (!el) return;

    el.style[styleProp] = value;
    state.currentHtml = '<!DOCTYPE html>\n' + doc.documentElement.outerHTML;
    if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
    renderPreview(true);
    marcarModificado();
  }

  // ══ VINCULAR OFERTA REAL AO BLOCO OFERTA ══
  function vincularOfertaAoBloco(offerId) {
    var off = state.availableOffers.find(function(o) { return String(o.id) === String(offerId); });
    if (!off) return;

    var inputTitle = document.getElementById('prop-offer-title');
    var inputPrice = document.getElementById('prop-offer-price');
    if (inputTitle) inputTitle.value = off.title;
    if (inputPrice) inputPrice.value = 'R$ ' + Number(off.price).toFixed(2).replace('.', ',');

    atualizarPropriedadeBloco('offer', 'title', off.title);
    atualizarPropriedadeBloco('offer', 'price', 'R$ ' + Number(off.price).toFixed(2).replace('.', ','));
  }

  // ══ DUPLICAR E EXCLUIR BLOCO SELECIONADO ══
  function duplicarElementoSelecionado() {
    if (!state.selectedBlockId) return;

    var parser = new DOMParser();
    var doc = parser.parseFromString(state.currentHtml, 'text/html');
    var el = doc.querySelector('[data-block-id="' + state.selectedBlockId + '"]');
    if (!el) return;

    var clone = el.cloneNode(true);
    var newId = 'blk_' + Date.now().toString(36);
    clone.setAttribute('data-block-id', newId);
    el.parentNode.insertBefore(clone, el.nextSibling);

    state.currentHtml = '<!DOCTYPE html>\n' + doc.documentElement.outerHTML;
    if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
    renderPreview(true);
    marcarModificado();
    window.showToast && window.showToast('Elemento duplicado!', 'success');
  }

  function excluirElementoSelecionado() {
    if (!state.selectedBlockId) return;

    var parser = new DOMParser();
    var doc = parser.parseFromString(state.currentHtml, 'text/html');
    var el = doc.querySelector('[data-block-id="' + state.selectedBlockId + '"]');
    if (!el) return;

    el.remove();
    desmarcarElemento();

    state.currentHtml = '<!DOCTYPE html>\n' + doc.documentElement.outerHTML;
    if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
    renderPreview(true);
    marcarModificado();
    window.showToast && window.showToast('Elemento excluído!', 'success');
  }

  // ══ FILTRAR BLOCOS NA PALETA ══
  function filtrarBlocos(termo) {
    var q = (termo || '').toLowerCase().trim();
    document.querySelectorAll('.elementor-block-card').forEach(function(card) {
      var name = (card.querySelector('.elementor-block-name') || {}).textContent || '';
      card.style.display = (!q || name.toLowerCase().indexOf(q) !== -1) ? 'flex' : 'none';
    });
  }

  // ══ REINICIAR TIMER NA PRÉVIA ══
  function reiniciarTimerPreview() {
    var iframe = (state.activeDevice === 'mobile')
      ? document.getElementById('builder-iframe-mobile')
      : document.getElementById('builder-iframe');

    if (iframe && iframe.contentWindow) {
      iframe.contentWindow.postMessage({ action: 'resetTimer' }, '*');
      window.showToast && window.showToast('Temporizador reiniciado para testes.', 'success');
    }
  }

  // ══ RECARREGAR PRÉVIA MANUALMENTE ══
  function recarregarPreview() {
    renderPreview(false);
    window.showToast && window.showToast('Prévia recarregada.', 'success');
  }

  // ══ MARCAR FUNIL COMO MODIFICADO / SALVO ══
  function marcarModificado() {
    state.isModified = true;
    var pill = document.getElementById('funnel-status-pill');
    var text = document.getElementById('funnel-status-text');
    if (pill) {
      pill.className = 'elementor-status-pill modified';
      if (text) text.textContent = 'Alterações pendentes';
    }
  }

  function marcarSalvo(statusTxt) {
    state.isModified = false;
    var pill = document.getElementById('funnel-status-pill');
    var text = document.getElementById('funnel-status-text');
    if (pill) {
      pill.className = 'elementor-status-pill';
      if (text) text.textContent = statusTxt || 'Rascunho salvo';
    }
  }

  // ══ SALVAR RASCUNHO (NÃO AFETA A VERSÃO NO AR) ══
  function salvarFunilRascunho() {
    var nameInput = document.getElementById('funnel-name');
    var name = (nameInput ? nameInput.value : state.name) || 'Página';
    var slug = state.slug || 'pagina';

    var pill = document.getElementById('funnel-status-pill');
    var text = document.getElementById('funnel-status-text');
    if (pill) {
      pill.className = 'elementor-status-pill saving';
      if (text) text.textContent = 'Salvando rascunho...';
    }

    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'save',
        funnel_id: state.funnelId,
        name: name,
        slug: slug,
        draft_content: state.currentHtml,
        html_content: state.currentHtml,
        workspace_id: wsId
      })
    })
    .then(function(data) {
      if (data.status === 'success') {
        state.funnelId = data.funnel_id;
        state.slug = data.slug;
        marcarSalvo('Rascunho salvo');
        window.showToast && window.showToast('Rascunho salvo com sucesso!', 'success');
        carregarListaGaveta();
      } else {
        window.showToast && window.showToast(data.message || 'Erro ao salvar rascunho.', 'error');
        marcarModificado();
      }
    })
    .catch(function(e) {
      console.error(e);
      window.showToast && window.showToast('Falha na comunicação ao salvar.', 'error');
    });
  }

  // ══ PUBLICAR PÁGINA (COLOCA NO AR COM LINK PÚBLICO REAL) ══
  function publicarFunilCompleto() {
    var nameInput = document.getElementById('funnel-name');
    var name = (nameInput ? nameInput.value : state.name) || 'Página';
    var slug = state.slug || 'pagina';

    var pill = document.getElementById('funnel-status-pill');
    var text = document.getElementById('funnel-status-text');
    if (pill) {
      pill.className = 'elementor-status-pill saving';
      if (text) text.textContent = 'Publicando página...';
    }

    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'publish',
        funnel_id: state.funnelId,
        name: name,
        slug: slug,
        html_content: state.currentHtml,
        draft_content: state.currentHtml,
        workspace_id: wsId
      })
    })
    .then(function(data) {
      if (data.status === 'success') {
        state.funnelId = data.funnel_id;
        state.slug = data.slug;
        marcarSalvo('Página no ar');
        
        var pubUrl = data.public_url || (window.location.origin + '/f/' + data.slug);
        
        // Abre o modal de sucesso com link público pronto
        var modalInput = document.getElementById('modal-published-url-input');
        var modalLink = document.getElementById('btn-modal-open-link');
        var modal = document.getElementById('modal-funnel-published');
        if (modalInput) modalInput.value = pubUrl;
        if (modalLink) modalLink.href = pubUrl;
        if (modal && typeof modal.showModal === 'function') {
          modal.showModal();
        } else {
          window.showToast && window.showToast('Página publicada com sucesso: ' + pubUrl, 'success');
        }

        carregarListaGaveta();
      } else {
        window.showToast && window.showToast(data.message || 'Erro ao publicar página.', 'error');
        marcarModificado();
      }
    })
    .catch(function(e) {
      console.error(e);
      window.showToast && window.showToast('Falha ao publicar a página.', 'error');
    });
  }

  // ══ GAVETA LATERAL DE TODAS AS PÁGINAS ══
  function togglePagesDrawer() {
    var drawer = document.getElementById('builder-pages-drawer');
    var backdrop = document.getElementById('builder-drawer-backdrop');
    if (!drawer || !backdrop) return;
    var isOpen = drawer.classList.contains('open');
    if (isOpen) {
      drawer.classList.remove('open');
      backdrop.classList.remove('open');
    } else {
      carregarListaGaveta();
      drawer.classList.add('open');
      backdrop.classList.add('open');
    }
  }

  function carregarListaGaveta() {
    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php?workspace_id=' + wsId)
      .then(function(data) {
        var funnels = data.funnels || [];
        var countBadge = document.getElementById('builder-pages-count');
        if (countBadge) countBadge.textContent = funnels.length;

        var list = document.getElementById('builder-drawer-list');
        if (!list) return;

        if (!funnels.length) {
          list.innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8; font-size:12.5px;">Nenhuma página criada ainda. Clique em "+ Nova" acima.</div>';
          return;
        }

        list.innerHTML = funnels.map(function(f) {
          var isCurrent = (f.id === state.funnelId);
          var isPub = (f.status === 'published');
          var badgeClass = isPub ? 'badge-success' : 'badge-neutral';
          var badgeTxt = isPub ? 'No ar' : 'Rascunho';
          var pubUrl = f.public_url || (window.location.origin + '/f/' + f.slug);

          return `
            <div class="elementor-page-item ${isCurrent ? 'active' : ''}" onclick="abrirPaginaElementor(${f.id})">
              <div class="elementor-page-item-top">
                <span class="elementor-page-item-title">${escapeHtml(f.name || f.slug)}</span>
                <span class="badge ${badgeClass}" style="font-size:10.5px;"><span class="badge-dot-indicator"></span> ${badgeTxt}</span>
              </div>
              <span class="elementor-page-item-slug">/f/${escapeHtml(f.slug)}</span>
              <div class="elementor-page-item-actions" onclick="event.stopPropagation()">
                <button type="button" class="btn-secondary" style="min-height:26px; padding:2px 8px; font-size:11px;" onclick="copiarValor('${pubUrl}')">Copiar Link</button>
                <a href="${pubUrl}" target="_blank" class="btn-secondary" style="min-height:26px; padding:2px 8px; font-size:11px; text-decoration:none;">Abrir</a>
                <button type="button" class="btn-secondary" style="min-height:26px; padding:2px 8px; font-size:11px; color:#ef4444;" onclick="excluirPagina(${f.id})">Excluir</button>
              </div>
            </div>
          `;
        }).join('');
      })
      .catch(function(e) {
        console.error('Erro ao listar páginas:', e);
      });
  }

  function abrirPaginaElementor(id) {
    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php?workspace_id=' + wsId)
      .then(function(data) {
        var f = (data.funnels || []).find(function(x) { return x.id === id; });
        if (!f) return;

        state.funnelId = f.id;
        state.name = f.name || 'Página';
        state.slug = f.slug || 'pagina';
        state.currentHtml = f.draft_content || f.html_content || getDefaultTemplate();

        var nameInput = document.getElementById('funnel-name');
        if (nameInput) nameInput.value = state.name;

        if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
        renderPreview(true);
        marcarSalvo(f.status === 'published' ? 'Página no ar' : 'Rascunho salvo');
        togglePagesDrawer();
      });
  }

  function novoFunilElementor() {
    state.funnelId = null;
    state.name = 'Nova Página ' + Math.floor(Math.random() * 900 + 100);
    state.slug = 'oferta-' + Math.floor(Math.random() * 9000 + 1000);
    state.currentHtml = getDefaultTemplate();

    var nameInput = document.getElementById('funnel-name');
    if (nameInput) nameInput.value = state.name;

    if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
    renderPreview(true);
    marcarModificado();
    togglePagesDrawer();
    window.showToast && window.showToast('Nova página criada no editor!', 'success');
  }

  function excluirPagina(id) {
    if (!confirm('Deseja realmente excluir esta página?')) return;
    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php?id=' + id + '&workspace_id=' + wsId, { method: 'DELETE' })
      .then(function(data) {
        window.showToast && window.showToast('Página removida com sucesso.', 'success');
        if (state.funnelId === id) novoFunilElementor();
        else carregarListaGaveta();
      });
  }

  // ══ CARREGAR FUNIL INICIAL DO WORKSPACE ══
  function carregarFunilInicial() {
    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php?workspace_id=' + wsId)
      .then(function(data) {
        var funnels = data.funnels || [];
        var countBadge = document.getElementById('builder-pages-count');
        if (countBadge) countBadge.textContent = funnels.length;

        if (funnels.length > 0) {
          var f = funnels[0];
          state.funnelId = f.id;
          state.name = f.name || 'Oferta Especial';
          state.slug = f.slug || 'oferta-especial';
          state.currentHtml = f.draft_content || f.html_content || getDefaultTemplate();
          
          var nameInput = document.getElementById('funnel-name');
          if (nameInput) nameInput.value = state.name;
          marcarSalvo(f.status === 'published' ? 'Página no ar' : 'Rascunho salvo');
        } else {
          state.currentHtml = getDefaultTemplate();
          marcarSalvo('Rascunho padrão');
        }

        if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
        renderPreview(true);
      })
      .catch(function(e) {
        state.currentHtml = getDefaultTemplate();
        if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
        renderPreview(true);
      });
  }

  // ══ AÇÕES DE CÓDIGO HTML (FORMATAR E COPIAR) ══
  function copiarCodigoHtml() {
    var code = state.codeMirror ? state.codeMirror.getValue() : state.currentHtml;
    window.copiarValor && window.copiarValor(code);
  }

  function formatarCodigoHtml() {
    if (!state.codeMirror) return;
    // Indentação básica de tags
    var val = state.codeMirror.getValue();
    state.codeMirror.setValue(val);
    window.showToast && window.showToast('Código formatado com sucesso.', 'success');
  }

  function copiarLinkPublicoAtual() {
    var pubUrl = window.location.origin + '/f/' + (state.slug || 'oferta-especial');
    window.copiarValor && window.copiarValor(pubUrl);
  }

  // ══ EVENT LISTENERS DE POSTMESSAGE E ATALHOS ══
  function setupEventListeners() {
    window.addEventListener('message', function(event) {
      if (!event.data || !event.data.type) return;

      if (event.data.type === 'elementor:select') {
        selecionarElementoPeloId(event.data);
      }

      if (event.data.type === 'elementor:unselect') {
        desmarcarElemento();
      }
    });

    // Atalhos Ctrl+Z e Ctrl+Y
    document.addEventListener('keydown', function(e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
        if (!e.shiftKey) {
          e.preventDefault();
          builderUndo();
        } else {
          e.preventDefault();
          builderRedo();
        }
      }
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') {
        e.preventDefault();
        builderRedo();
      }
    });
  }

  // ══ EXPOR FUNÇÕES GLOBAIS PARA O TEMPLATE ══
  window.builderUndo = builderUndo;
  window.builderRedo = builderRedo;
  window.setBuilderDevice = setBuilderDevice;
  window.setBuilderZoom = setBuilderZoom;
  window.setCanvasMode = setCanvasMode;
  window.switchSidebarTab = switchSidebarTab;
  window.inserirBloco = inserirBloco;
  window.desmarcarElemento = desmarcarElemento;
  window.duplicarElementoSelecionado = duplicarElementoSelecionado;
  window.excluirElementoSelecionado = excluirElementoSelecionado;
  window.atualizarPropriedadeBloco = atualizarPropriedadeBloco;
  window.atualizarDestinoBotao = atualizarDestinoBotao;
  window.aplicarEstiloRapido = aplicarEstiloRapido;
  window.vincularOfertaAoBloco = vincularOfertaAoBloco;
  window.filtrarBlocos = filtrarBlocos;
  window.reiniciarTimerPreview = reiniciarTimerPreview;
  window.recarregarPreview = recarregarPreview;
  window.salvarFunilRascunho = salvarFunilRascunho;
  window.publicarFunilCompleto = publicarFunilCompleto;
  window.togglePagesDrawer = togglePagesDrawer;
  window.abrirPaginaElementor = abrirPaginaElementor;
  window.novoFunilElementor = novoFunilElementor;
  window.excluirPagina = excluirPagina;
  window.copiarCodigoHtml = copiarCodigoHtml;
  window.formatarCodigoHtml = formatarCodigoHtml;
  window.copiarLinkPublicoAtual = copiarLinkPublicoAtual;
  window.marcarFunilModificado = marcarModificado;
  window.initElementorBuilder = initBuilder;

})(window, document);
