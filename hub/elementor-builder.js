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
    templateSlug: 'default',
    customDomainId: null,
    customDomainName: '',
    metaPixelId: '',
    ctaTrackEvent: 'InitiateCheckout',
    currentHtml: '',
    selectedBlockId: null,
    activeDevice: 'desktop',
    activeZoom: 75,
    canvasMode: 'edit', // 'edit' ou 'test'
    activeTab: 'elements', // 'elements', 'style', 'code', 'tracking'
    isModified: false,
    historyStack: [],
    historyIndex: -1,
    availableOffers: [],
    availableDomains: [],
    codeMirror: null,
    debounceTimer: null
  };

  // ══ AUXILIARES DE SLUG E URLS ══
  function sanitizeSlug(val) {
    if (!val) return '';
    return String(val).toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9_-]/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  }

  function getPublicUrl() {
    var sl = state.slug || 'pagina';
    if (state.customDomainName) {
      return 'https://' + state.customDomainName + '/f/' + sl;
    }
    return window.location.origin + '/f/' + sl;
  }

  function atualizarDisplayLinkPublico() {
    var pubUrl = getPublicUrl();
    var hostDisplay = state.customDomainName ? state.customDomainName : window.location.host;
    var urlPath = '/f/' + (state.slug || 'oferta-especial');
    var fullDisplay = hostDisplay + urlPath;

    var urlDisplay = document.getElementById('sidebar-public-url-display');
    var openBtn = document.getElementById('sidebar-open-url-btn');
    if (urlDisplay) urlDisplay.textContent = fullDisplay;
    if (openBtn) openBtn.href = pubUrl;

    var floatingUrlDisplay = document.getElementById('floating-public-url-display');
    var floatingOpenBtn = document.getElementById('floating-open-url-btn');
    if (floatingUrlDisplay) floatingUrlDisplay.textContent = fullDisplay;
    if (floatingOpenBtn) floatingOpenBtn.href = pubUrl;

    var crumbTitle = document.getElementById('builder-crumb-page-title');
    if (crumbTitle) crumbTitle.textContent = state.name || 'Oferta especial';

    var sidebarTitle = document.getElementById('sidebar-page-item-title');
    if (sidebarTitle) sidebarTitle.textContent = state.name || 'Oferta especial';

    var slugInput = document.getElementById('funnel-slug');
    if (slugInput && slugInput !== document.activeElement && slugInput.value !== (state.slug || '')) {
      slugInput.value = state.slug || '';
    }

    var domainSelect = document.getElementById('builder-funnel-domain-select');
    if (domainSelect && state.customDomainId !== null) {
      domainSelect.value = String(state.customDomainId);
    }
  }

  // ══ SUITE DE RASTREAMENTO COMPLETO ("TRAQUEIE TUDO") ══
  function getDefaultTrackingRuntime(pixelId, pixelEvent, contentName, priceValue) {
    var pId = pixelId || state.metaPixelId || '';
    var pEvt = pixelEvent || state.ctaTrackEvent || 'InitiateCheckout';
    var pName = contentName || state.name || 'Campanha';
    var pVal = parseFloat(priceValue || 0);

    var pixelTag = (pId && state.canvasMode !== 'edit') ? `
  <!-- Meta Pixel Code -->
  <script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '${escapeHtml(pId)}');
  fbq('track', 'PageView');
  <\/script>
  <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=${encodeURIComponent(pId)}&ev=PageView&noscript=1"/></noscript>
  <!-- End Meta Pixel Code -->` : '';

    return `${pixelTag}
  <!-- Full Tracking Suite (Traqueamento Completo & UTMs) -->
  <script>
  (function() {
    'use strict';
    var storageKey = 'upscale_campaign_utms';
    var utmMap = {};

    try {
      var saved = localStorage.getItem(storageKey);
      if (saved) utmMap = JSON.parse(saved);
    } catch (e) {}

    var params = new URLSearchParams(window.location.search);
    params.forEach(function(val, key) {
      if (/^(utm_|fbclid|gclid|ttclid|src|sck|kwai|tiktok|source)/i.test(key)) {
        utmMap[key] = val;
      }
    });

    try {
      localStorage.setItem(storageKey, JSON.stringify(utmMap));
      sessionStorage.setItem(storageKey, JSON.stringify(utmMap));
    } catch (e) {}

    // Injetar UTMs em TODOS os links e botões da página
    function applyUtms(link) {
      try {
        if (!link || !link.href || link.href.indexOf('#') === 0 || link.href.indexOf('javascript:') === 0) return;
        var u = new URL(link.href, window.location.href);
        Object.keys(utmMap).forEach(function(k) {
          if (!u.searchParams.has(k)) u.searchParams.set(k, utmMap[k]);
        });
        link.href = u.href;
      } catch (err) {}
    }
    document.querySelectorAll('a').forEach(applyUtms);

    // Meta Pixel Tracking
    if (typeof window.fbq === 'function') {
      window.fbq('track', 'ViewContent', {
        content_name: ${JSON.stringify(pName)},
        content_type: 'product',
        value: ${pVal},
        currency: 'BRL'
      });
    }

    // Clique em botões de compra (Hero, CTA, etc.)
    document.querySelectorAll('.btn-cta, .js-buy, [data-block-type="button"]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        if (typeof window.fbq === 'function') {
          window.fbq('track', '${pEvt}', {
            content_name: ${JSON.stringify(pName)},
            value: ${pVal},
            currency: 'BRL'
          });
        }
      });
    });

    // Contato em WhatsApp e Redes
    document.querySelectorAll('a[href*="whatsapp.com"], a[href*="wa.me"], a[href*="instagram.com"], a[href*="t.me"]').forEach(function(channel) {
      channel.addEventListener('click', function() {
        if (typeof window.fbq === 'function') {
          window.fbq('track', 'Contact', { channel: channel.hostname });
        }
      });
    });

    // Scroll Depth
    var scrollFired = {};
    window.addEventListener('scroll', function() {
      var h = document.documentElement, b = document.body;
      var pct = Math.round(((h.scrollTop || b.scrollTop) / ((h.scrollHeight || b.scrollHeight) - h.clientHeight)) * 100);
      [25, 50, 75, 90].forEach(function(m) {
        if (pct >= m && !scrollFired[m]) {
          scrollFired[m] = true;
          if (typeof window.fbq === 'function') window.fbq('trackCustom', 'ScrollDepth', { depth: m + '%' });
        }
      });
    });
  })();
  <\/script>`;
  }

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
    body { background:#ffffff; color:#0f172a; line-height:1.5; padding:40px 24px 28px 24px; min-height:100vh; display:flex; flex-direction:column; align-items:center; }
    .funnel-container { width:100%; max-width:820px; margin:0 auto; }
    .badge-top { display:inline-block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:#205bff; background:#eff6ff; padding:6px 14px; border-radius:999px; margin-bottom:14px; text-align:center; }
    .heading-hero { font-size:38px; font-weight:800; color:#0f172a; text-align:center; line-height:1.2; letter-spacing:-0.03em; margin-bottom:10px; }
    .subheading-hero { font-size:15.5px; color:#64748b; text-align:center; margin-bottom:28px; }
    
    /* Bloco Temporizador */
    .timer-wrap { display:flex; flex-direction:column; align-items:center; margin-bottom:32px; }
    .timer-label { font-size:12px; font-weight:600; color:#64748b; margin-bottom:10px; }
    .timer-boxes { display:flex; align-items:center; gap:8px; justify-content:center; }
    .timer-box { background:#0f172a; color:#ffffff; padding:10px 14px; border-radius:10px; min-width:72px; text-align:center; }
    .timer-box strong { display:block; font-size:24px; font-weight:800; font-family:'Space Grotesk', sans-serif; line-height:1.1; margin-bottom:2px; }
    .timer-box span { font-size:9.5px; text-transform:uppercase; color:#94a3b8; font-weight:700; letter-spacing:0.04em; }
    .timer-sep { font-size:20px; font-weight:700; color:#0f172a; line-height:1; }

    /* Card da Oferta */
    .offer-card { background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 8px 30px rgba(0,0,0,0.05); padding:24px; display:grid; grid-template-columns:1.05fr 1.25fr; gap:24px; align-items:center; margin-bottom:24px; }
    .offer-image-box { border-radius:14px; overflow:hidden; background:#06152d; aspect-ratio:4/3; display:flex; align-items:center; justify-content:center; }
    .offer-image-box img { width:100%; height:100%; object-fit:cover; display:block; }
    .offer-content { display:flex; flex-direction:column; gap:10px; }
    .offer-title { font-size:24px; font-weight:800; color:#0f172a; margin-bottom:2px; }
    .offer-desc { font-size:13.5px; color:#64748b; margin-bottom:4px; }
    .offer-price-row { display:flex; align-items:baseline; gap:8px; margin:4px 0; }
    .offer-price { font-size:32px; font-weight:800; color:#205bff; }
    .offer-old-price { font-size:14px; color:#94a3b8; text-decoration:line-through; }
    .offer-benefits { list-style:none; display:flex; flex-direction:column; gap:6px; margin:8px 0; }
    .offer-benefits li { font-size:13px; font-weight:500; color:#334155; display:flex; align-items:center; gap:8px; }
    .offer-benefits li svg { width:16px; height:16px; color:#205bff; flex-shrink:0; }

    /* Botão CTA Principal */
    .btn-cta { display:inline-flex; align-items:center; justify-content:center; gap:8px; width:100%; background:#205bff; color:#ffffff; font-size:16px; font-weight:600; text-decoration:none; padding:16px 24px; border-radius:8px; border:none; cursor:pointer; box-shadow:0 4px 14px rgba(32,91,255,0.3); transition:all 0.2s ease; }
    .btn-cta:hover { background:#174ce0; transform:translateY(-1px); box-shadow:0 6px 20px rgba(32,91,255,0.4); }

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
        <div class="timer-box"><strong class="t-hours">00</strong><span>HORAS</span></div>
        <span class="timer-sep">:</span>
        <div class="timer-box"><strong class="t-mins">14</strong><span>MINUTOS</span></div>
        <span class="timer-sep">:</span>
        <div class="timer-box"><strong class="t-secs">17</strong><span>SEGUNDOS</span></div>
      </div>
    </div>

    <!-- Bloco Oferta Especial -->
    <div class="offer-card" data-block-id="blk_offer_1" data-block-type="offer">
      <div class="offer-image-box">
        <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=700&auto=format&fit=crop&q=80" alt="Booster 200X Ouro">
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
  ${getDefaultTrackingRuntime()}
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
        .elementor-hover-outline { outline: 2px dashed #205bff !important; cursor: pointer !important; }
        .elementor-active-selected {
          outline: 2px solid #205bff !important;
          position: relative !important;
        }
        .__elementor_handle__ {
          position: absolute;
          width: 7px;
          height: 7px;
          background: #ffffff;
          border: 2px solid #205bff;
          border-radius: 2px;
          pointer-events: none;
          z-index: 999;
        }
        .__elementor_handle__.tl { top: -4px; left: -4px; }
        .__elementor_handle__.tr { top: -4px; right: -4px; }
        .__elementor_handle__.bl { bottom: -4px; left: -4px; }
        .__elementor_handle__.br { bottom: -4px; right: -4px; }
        .__elementor_floating_bar__ {
          position: absolute;
          top: -36px;
          right: 0px;
          background: #ffffff;
          border: 1px solid #e2e8f0;
          border-radius: 6px;
          box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
          display: flex;
          align-items: center;
          gap: 2px;
          padding: 2px 4px;
          z-index: 1000;
          cursor: default;
        }
        .__elem_btn__ {
          border: none;
          background: transparent;
          color: #64748b;
          cursor: pointer;
          padding: 4px;
          border-radius: 4px;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.15s ease;
        }
        .__elem_btn__:hover {
          background: #f1f5f9;
          color: #0f172a;
        }
        .__elem_btn__.__elem_delete__:hover {
          background: #fee2e2;
          color: #ef4444;
        }
      \`;
      document.head.appendChild(styleTag);

      function removeFloatingBar() {
        var existing = document.querySelector('.__elementor_floating_bar__');
        if (existing) existing.remove();
        document.querySelectorAll('.__elementor_handle__').forEach(function(h) { h.remove(); });
      }

      function attachFloatingBar(block) {
        removeFloatingBar();
        if (!block) return;

        ['tl', 'tr', 'bl', 'br'].forEach(function(pos) {
          var h = document.createElement('div');
          h.className = '__elementor_handle__ ' + pos;
          block.appendChild(h);
        });

        var bar = document.createElement('div');
        bar.className = '__elementor_floating_bar__';
        bar.innerHTML = \`
          <button type="button" class="__elem_btn__" title="Mover">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="5 9 2 12 5 15"/><polyline points="9 5 12 2 15 5"/><polyline points="15 19 12 22 9 19"/><polyline points="19 9 22 12 19 15"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="12" y1="2" x2="12" y2="22"/></svg>
          </button>
          <button type="button" class="__elem_btn__" id="__btn_dup__" title="Duplicar">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          </button>
          <button type="button" class="__elem_btn__ __elem_delete__" id="__btn_del__" title="Excluir">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="#ef4444" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          </button>
        \`;
        block.appendChild(bar);

        bar.querySelector('#__btn_dup__').addEventListener('click', function(ev) {
          ev.stopPropagation();
          ev.preventDefault();
          window.parent.postMessage({ type: 'elementor:duplicate' }, '*');
        });

        bar.querySelector('#__btn_del__').addEventListener('click', function(ev) {
          ev.stopPropagation();
          ev.preventDefault();
          window.parent.postMessage({ type: 'elementor:delete' }, '*');
        });
      }

      function findBlockParent(el) {
        while (el && el !== document.body) {
          if (el.getAttribute && el.getAttribute('data-block-id')) return el;
          el = el.parentElement;
        }
        return null;
      }

      function selectElement(block) {
        if (currentSelected) {
          currentSelected.classList.remove('elementor-active-selected');
        }
        currentSelected = block;
        if (!block) {
          removeFloatingBar();
          window.parent.postMessage({ type: 'elementor:unselect' }, '*');
          return;
        }

        currentSelected.classList.remove('elementor-hover-outline');
        currentSelected.classList.add('elementor-active-selected');
        attachFloatingBar(currentSelected);

        var blockId = block.getAttribute('data-block-id');
        var blockType = block.getAttribute('data-block-type');
        var textContent = block.innerText || '';
        var tag = block.tagName.toLowerCase();
        var comp = window.getComputedStyle(block);

        window.parent.postMessage({
          type: 'elementor:select',
          blockId: blockId,
          blockType: blockType,
          tag: tag,
          text: textContent.trim().substring(0, 80),
          destType: block.getAttribute('data-dest-type') || '',
          offerId: block.getAttribute('data-offer-id') || '',
          href: block.getAttribute('href') || '',
          fontSize: comp.fontSize,
          fontWeight: comp.fontWeight,
          textAlign: comp.textAlign,
          backgroundColor: comp.backgroundColor,
          color: comp.color,
          paddingLeft: comp.paddingLeft,
          paddingTop: comp.paddingTop,
          borderRadius: comp.borderRadius
        }, '*');
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
        // Ignore clicks on floating bar buttons
        if (e.target && e.target.closest && e.target.closest('.__elementor_floating_bar__')) return;

        var block = findBlockParent(e.target);
        if (block) {
          e.preventDefault();
          e.stopPropagation();
          selectElement(block);
        } else {
          selectElement(null);
        }
      }, true);

      // Auto-selecionar o botão principal (ou primeiro elemento) para corresponder à referência visual
      window.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
          var defaultBtn = document.querySelector('[data-block-id="blk_btn_1"]') || document.querySelector('[data-block-type="button"]');
          if (defaultBtn && activeMode === 'edit') {
            document.querySelectorAll('.elementor-hover-outline').forEach(function(el) { el.classList.remove('elementor-hover-outline'); });
            selectElement(defaultBtn);
            window.scrollTo({ top: 0, behavior: 'instant' });
          }
        }, 120);
      });

      // Listener para comandos do painel pai
      window.addEventListener('message', function(event) {
        if (!event.data || !event.data.action) return;
        
        if (event.data.action === 'setMode') {
          activeMode = event.data.mode;
          if (activeMode === 'test') {
            document.querySelectorAll('.elementor-hover-outline, .elementor-active-selected').forEach(function(el) {
              el.classList.remove('elementor-hover-outline', 'elementor-active-selected');
            });
            removeFloatingBar();
            currentSelected = null;
          }
        }

        if (event.data.action === 'selectBlock') {
          var b = document.querySelector('[data-block-id="' + event.data.blockId + '"]');
          if (b) {
            selectElement(b);
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
    carregarDominiosBuilder();
    setupEventListeners();
    setBuilderZoom(75);
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

  // ══ CARREGAR DOMÍNIOS CONECTADOS DO WORKSPACE ══
  function carregarDominiosBuilder() {
    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/domains.php?workspace_id=' + wsId)
      .then(function (data) {
        var domains = (data.domains || []);
        state.availableDomains = domains;

        var selTopbar = document.getElementById('builder-funnel-domain-select');
        var selModal = document.getElementById('modal-funnel-domain');

        var optionsHtml = '<option value="">(Padrão da Plataforma)</option>' +
          domains.map(function(d) {
            var isPrimary = d.is_primary ? ' ★ (Principal)' : '';
            return '<option value="' + d.id + '" data-domain="' + escapeHtml(d.domain) + '">' + escapeHtml(d.domain) + isPrimary + '</option>';
          }).join('');

        if (selTopbar) {
          selTopbar.innerHTML = optionsHtml;
          if (state.customDomainId) selTopbar.value = String(state.customDomainId);
        }
        if (selModal) {
          selModal.innerHTML = optionsHtml;
          if (state.customDomainId) selModal.value = String(state.customDomainId);
        }

        // Se o funil atual não tem domínio definido e há um primário ativo
        if (!state.customDomainId && domains.length > 0) {
          var primary = domains.find(function(d) { return d.is_primary && d.status === 'active'; }) || domains[0];
          if (primary && primary.status === 'active') {
            state.customDomainId = primary.id;
            state.customDomainName = primary.domain;
            if (selTopbar) selTopbar.value = String(primary.id);
            if (selModal) selModal.value = String(primary.id);
            atualizarDisplayLinkPublico();
          }
        }
      })
      .catch(function (e) {
        console.warn('Erro ao carregar domínios para builder:', e);
      });
  }

  function trocarDominioBuilder(domId) {
    if (!domId) {
      state.customDomainId = null;
      state.customDomainName = '';
    } else {
      state.customDomainId = parseInt(domId, 10);
      var found = state.availableDomains.find(function(d) { return d.id === state.customDomainId; });
      state.customDomainName = found ? found.domain : '';
    }
    atualizarDisplayLinkPublico();
    marcarModificado();
  }

  function atualizarSlugBuilder(val) {
    var clean = sanitizeSlug(val);
    state.slug = clean;
    atualizarDisplayLinkPublico();
    marcarModificado();
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
    atualizarDisplayLinkPublico();

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

  // ══ ALTERNAR ABAS DA SIDEBAR (ELEMENTOS, ESTILO, CÓDIGO, RASTREAMENTO) ══
  function switchSidebarTab(tabName) {
    state.activeTab = tabName;
    ['elements', 'style', 'code', 'tracking'].forEach(function(t) {
      var btn = document.getElementById('tab-btn-' + t);
      var pane = document.getElementById('pane-' + t);
      if (btn) btn.classList.toggle('active', tabName === t);
      if (pane) pane.style.display = (tabName === t) ? 'block' : 'none';
    });

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

  // ══ AUXILIARES DE CORES E MEDIDAS ══
  function rgbToHex(rgb) {
    if (!rgb || rgb === 'transparent' || rgb === 'rgba(0, 0, 0, 0)') return '#205BFF';
    if (rgb.indexOf('#') === 0) return rgb.toUpperCase();
    var match = rgb.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
    if (!match) return '#205BFF';
    var r = parseInt(match[1], 10).toString(16).padStart(2, '0');
    var g = parseInt(match[2], 10).toString(16).padStart(2, '0');
    var b = parseInt(match[3], 10).toString(16).padStart(2, '0');
    return ('#' + r + g + b).toUpperCase();
  }

  function parsePx(val) {
    if (!val) return 16;
    var n = parseInt(val, 10);
    return isNaN(n) ? 16 : n;
  }

  function getIconSvgParaTipo(type) {
    var map = {
      heading: '<span style="font-family:serif; font-size:16px; font-weight:700;">T</span>',
      text: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="15" y2="18"/></svg>',
      image: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
      video: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>',
      button: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>',
      container: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="3 3"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
      html: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
      timer: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6"/><path d="m22 6-3-3"/><path d="M10 2h4"/></svg>',
      offer: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
      spacer: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>'
    };
    return map[type] || map.button;
  }

  function formatarNomeTipo(type) {
    var map = {
      heading: 'Título',
      text: 'Texto',
      image: 'Imagem',
      video: 'Vídeo',
      button: 'Botão',
      container: 'Container',
      html: 'HTML',
      timer: 'Temporizador',
      offer: 'Oferta',
      spacer: 'Espaçador'
    };
    return map[type] || 'Elemento';
  }

  // ══ SELEÇÃO E INSPEÇÃO DE ELEMENTO NO PAINEL LATERAL ══
  function selecionarElementoPeloId(data) {
    state.selectedBlockId = data.blockId;
    var type = data.blockType || 'button';
    var friendlyName = formatarNomeTipo(type);

    // Atualiza cabeçalho do Inspetor Direito
    var rightTitle = document.getElementById('inspector-right-title');
    var rightIcon = document.getElementById('inspector-right-icon');
    if (rightTitle) rightTitle.textContent = friendlyName;
    if (rightIcon) rightIcon.innerHTML = getIconSvgParaTipo(type);

    // Sincroniza controles da aba Estilo
    if (data.fontSize) {
      var fSize = parsePx(data.fontSize);
      var fsInput = document.getElementById('insp-font-size');
      if (fsInput) fsInput.value = fSize;
    }

    if (data.fontWeight) {
      var fwSelect = document.getElementById('insp-font-weight');
      if (fwSelect) {
        var fw = String(data.fontWeight);
        if (fw === 'bold' || fw === '700') fw = '700';
        else if (fw === 'normal' || fw === '400') fw = '400';
        else if (fw === '600') fw = '600';
        else if (fw === '800') fw = '800';
        else if (fw === '500') fw = '500';
        fwSelect.value = fw;
      }
    }

    if (data.textAlign) {
      var align = data.textAlign;
      ['left', 'center', 'right', 'justify'].forEach(function(a) {
        var btn = document.getElementById('insp-align-' + a);
        if (btn) btn.classList.toggle('active', align === a);
      });
    }

    if (data.backgroundColor && data.backgroundColor !== 'transparent' && data.backgroundColor !== 'rgba(0, 0, 0, 0)') {
      var bgHex = rgbToHex(data.backgroundColor);
      var bgInput = document.getElementById('insp-bg-color-hex');
      var bgSwatch = document.getElementById('insp-bg-swatch');
      var bgPicker = document.getElementById('insp-bg-color-picker');
      if (bgInput) bgInput.value = bgHex;
      if (bgSwatch) bgSwatch.style.background = bgHex;
      if (bgPicker) bgPicker.value = bgHex;
    }

    if (data.color) {
      var colHex = rgbToHex(data.color);
      var colInput = document.getElementById('insp-text-color-hex');
      var colSwatch = document.getElementById('insp-text-swatch');
      var colPicker = document.getElementById('insp-text-color-picker');
      if (colInput) colInput.value = colHex;
      if (colSwatch) colSwatch.style.background = colHex;
      if (colPicker) colPicker.value = colHex;
    }

    if (data.paddingLeft !== undefined) {
      var padH = parsePx(data.paddingLeft);
      var padHInput = document.getElementById('insp-pad-h');
      if (padHInput) padHInput.value = padH;
    }

    if (data.paddingTop !== undefined) {
      var padV = parsePx(data.paddingTop);
      var padVInput = document.getElementById('insp-pad-v');
      if (padVInput) padVInput.value = padV;
    }

    if (data.borderRadius !== undefined) {
      var bRadius = parsePx(data.borderRadius);
      var bRadInput = document.getElementById('insp-border-radius');
      if (bRadInput) bRadInput.value = bRadius;
    }

    // Preenche campos dinâmicos na aba Conteúdo
    var fieldsBox = document.getElementById('inspector-fields-container');
    if (fieldsBox) {
      var fieldsHtml = '';
      if (type === 'button') {
        var offersOptions = state.availableOffers.map(function(o) {
          var sel = String(o.id) === String(data.offerId) ? 'selected' : '';
          return `<option value="${o.id}" ${sel}>${escapeHtml(o.title)} (R$ ${Number(o.price).toFixed(2)})</option>`;
        }).join('');

        fieldsHtml = `
          <div class="elementor-prop-row">
            <label class="elementor-prop-label">Texto do Botão</label>
            <input type="text" class="elementor-prop-input" id="prop-btn-text" value="${escapeHtml(data.text || 'Quero aproveitar →')}" oninput="atualizarPropriedadeBloco('button', 'text', this.value)">
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
            <label class="elementor-prop-label">Preencher com Oferta</label>
            <select class="elementor-prop-select" onchange="vincularOfertaAoBloco(this.value)">
              <option value="">Selecione...</option>
              ${offersOpts}
            </select>
          </div>
          <div class="elementor-prop-row">
            <label class="elementor-prop-label">Título da Oferta</label>
            <input type="text" class="elementor-prop-input" id="prop-offer-title" value="Booster 200X Ouro" oninput="atualizarPropriedadeBloco('offer', 'title', this.value)">
          </div>
          <div class="elementor-prop-row">
            <label class="elementor-prop-label">Preço</label>
            <input type="text" class="elementor-prop-input" id="prop-offer-price" value="R$ 120,00" oninput="atualizarPropriedadeBloco('offer', 'price', this.value)">
          </div>
        `;
      } else {
        fieldsHtml = `
          <p style="font-size:12px; color:#64748b;">Ajuste as propriedades finas nas abas Estilo e Avançado.</p>
        `;
      }
      fieldsBox.innerHTML = fieldsHtml;
    }
  }

  function desmarcarElemento() {
    state.selectedBlockId = null;
    var rightTitle = document.getElementById('inspector-right-title');
    if (rightTitle) rightTitle.textContent = 'Elemento';

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
      if (text) text.textContent = statusTxt || 'Rascunho';
    }
  }

  // ══ CONTROLES DO INSPETOR DIREITO ══
  function switchInspectorTab(tabName) {
    ['content', 'style', 'advanced'].forEach(function(t) {
      var btn = document.getElementById('tab-inspector-' + t);
      var pane = document.getElementById('insp-pane-' + t);
      if (btn) btn.classList.toggle('active', tabName === t);
      if (pane) pane.style.display = (tabName === t) ? 'flex' : 'none';
    });
  }

  function setInspectorAlign(align) {
    ['left', 'center', 'right', 'justify'].forEach(function(a) {
      var btn = document.getElementById('insp-align-' + a);
      if (btn) btn.classList.toggle('active', align === a);
    });
    aplicarEstiloRapido('textAlign', align);
  }

  function aplicarEstiloCor(prop, hexVal) {
    var val = (hexVal || '').trim();
    if (!val) return;
    if (val.charAt(0) !== '#') val = '#' + val;
    val = val.toUpperCase();

    if (prop === 'backgroundColor') {
      var swatch = document.getElementById('insp-bg-swatch');
      var picker = document.getElementById('insp-bg-color-picker');
      if (swatch) swatch.style.background = val;
      if (picker) picker.value = val;
    } else if (prop === 'color') {
      var tSwatch = document.getElementById('insp-text-swatch');
      var tPicker = document.getElementById('insp-text-color-picker');
      if (tSwatch) tSwatch.style.background = val;
      if (tPicker) tPicker.value = val;
    }

    aplicarEstiloRapido(prop, val);
  }

  function atualizarCorHex(prop, val) {
    if (prop === 'backgroundColor') {
      var hexInp = document.getElementById('insp-bg-color-hex');
      var swatch = document.getElementById('insp-bg-swatch');
      if (hexInp) hexInp.value = val.toUpperCase();
      if (swatch) swatch.style.background = val;
    } else if (prop === 'color') {
      var tHexInp = document.getElementById('insp-text-color-hex');
      var tSwatch = document.getElementById('insp-text-swatch');
      if (tHexInp) tHexInp.value = val.toUpperCase();
      if (tSwatch) tSwatch.style.background = val;
    }
    aplicarEstiloRapido(prop, val);
  }

  function aplicarPaddingSeparado() {
    var padHInput = document.getElementById('insp-pad-h');
    var padVInput = document.getElementById('insp-pad-v');
    var padH = padHInput ? parseInt(padHInput.value, 10) : 24;
    var padV = padVInput ? parseInt(padVInput.value, 10) : 16;
    if (isNaN(padH)) padH = 24;
    if (isNaN(padV)) padV = 16;
    aplicarEstiloRapido('padding', padV + 'px ' + padH + 'px');
  }

  function toggleSombraInspector() {
    if (!state.selectedBlockId) return;
    var parser = new DOMParser();
    var doc = parser.parseFromString(state.currentHtml, 'text/html');
    var el = doc.querySelector('[data-block-id="' + state.selectedBlockId + '"]');
    if (!el) return;
    var hasShadow = el.style.boxShadow && el.style.boxShadow !== 'none';
    aplicarEstiloRapido('boxShadow', hasShadow ? 'none' : '0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1)');
    window.showToast && window.showToast(hasShadow ? 'Sombra removida.' : 'Sombra aplicada.', 'info');
  }

  function toggleAccordionSecoes() {
    var content = document.getElementById('elementor-accordion-content');
    var chevron = document.getElementById('accordion-chevron');
    if (!content) return;
    content.classList.toggle('hidden');
    if (chevron) chevron.parentElement.parentElement.classList.toggle('collapsed');
  }

  function inserirSecaoPronta(tipo) {
    var newId = 'blk_sec_' + tipo + '_' + Date.now().toString(36);
    var snippet = '';

    if (tipo === 'hero') {
      snippet = `\n<!-- Seção Hero Pronta -->\n<div data-block-id="${newId}" data-block-type="container" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; padding:36px 24px; text-align:center; margin:24px 0;">\n  <span class="badge-top">DESTAQUE EXCLUSIVO</span>\n  <h2 style="font-size:32px; font-weight:800; color:#0f172a; margin:12px 0;">Transforme Seus Resultados Hoje</h2>\n  <p style="font-size:15px; color:#64748b; max-width:540px; margin:0 auto 20px auto;">Acesso imediato com garantia incondicional e suporte dedicado.</p>\n  <a href="#checkout" class="btn-cta" style="display:inline-block; max-width:280px;">Começar Agora →</a>\n</div>`;
    } else if (tipo === 'testimonials') {
      snippet = `\n<!-- Seção Depoimentos Pronta -->\n<div data-block-id="${newId}" data-block-type="container" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; padding:28px 24px; margin:24px 0;">\n  <h3 style="font-size:20px; font-weight:700; text-align:center; margin-bottom:16px;">O Que Nossos Clientes Dizem</h3>\n  <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">\n    <div style="background:#f8fafc; padding:16px; border-radius:12px; border:1px solid #e2e8f0;">\n      <div style="color:#f59e0b; margin-bottom:6px;">★★★★★</div>\n      <p style="font-size:13px; color:#334155;">"Excelente oportunidade, entrega rápida e suporte muito atencioso!"</p>\n      <strong style="display:block; font-size:12px; margin-top:8px; color:#0f172a;">— Carlos Eduardo</strong>\n    </div>\n    <div style="background:#f8fafc; padding:16px; border-radius:12px; border:1px solid #e2e8f0;">\n      <div style="color:#f59e0b; margin-bottom:6px;">★★★★★</div>\n      <p style="font-size:13px; color:#334155;">"Fácil de comprar via Pix, tudo aprovado na hora sem burocracia."</p>\n      <strong style="display:block; font-size:12px; margin-top:8px; color:#0f172a;">— Mariana Silva</strong>\n    </div>\n  </div>\n</div>`;
    } else if (tipo === 'faq') {
      snippet = `\n<!-- Seção Perguntas Frequentes Pronta -->\n<div data-block-id="${newId}" data-block-type="container" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; padding:28px 24px; margin:24px 0;">\n  <h3 style="font-size:20px; font-weight:700; text-align:center; margin-bottom:18px;">Perguntas Frequentes</h3>\n  <div style="display:flex; flex-direction:column; gap:12px;">\n    <div style="border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px;">\n      <strong style="font-size:13.5px; color:#0f172a;">Como funciona a entrega?</strong>\n      <p style="font-size:12.5px; color:#64748b; margin-top:4px;">O envio é imediato e 100% automático logo após a confirmação do pagamento Pix.</p>\n    </div>\n    <div style="border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px;">\n      <strong style="font-size:13.5px; color:#0f172a;">O pagamento é seguro?</strong>\n      <p style="font-size:12.5px; color:#64748b; margin-top:4px;">Sim, utilizamos criptografia de ponta a ponta e processamento bancário homologado.</p>\n    </div>\n  </div>\n</div>`;
    }

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
    window.showToast && window.showToast('Seção adicionada!', 'success');
  }

  function sincronizarTituloPagina(val) {
    state.name = val || 'Oferta especial';
    var crumbTitle = document.getElementById('builder-crumb-page-title');
    var sidebarTitle = document.getElementById('sidebar-page-item-title');
    if (crumbTitle) crumbTitle.textContent = state.name;
    if (sidebarTitle) sidebarTitle.textContent = state.name;
    marcarModificado();
  }

  function focarNomePagina() {
    var novoNome = prompt('Digite o nome da página:', state.name || 'Oferta especial');
    if (novoNome !== null && novoNome.trim() !== '') {
      sincronizarTituloPagina(novoNome.trim());
    }
  }

  function abrirPreviaNovaAba() {
    var url = getPublicUrl();
    window.open(url, '_blank');
  }

  function abrirEditorCodigoModal() {
    switchSidebarTab('code');
    window.showToast && window.showToast('Editor de código ativado.', 'info');
  }

  // ══ SALVAR RASCUNHO (NÃO AFETA A VERSÃO NO AR) ══
  function salvarFunilRascunho() {
    var nameInput = document.getElementById('funnel-name');
    var name = (nameInput ? nameInput.value : state.name) || 'Página';
    var slugInput = document.getElementById('funnel-slug');
    var slug = sanitizeSlug(slugInput ? slugInput.value : state.slug) || 'pagina';
    state.slug = slug;

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
        custom_domain_id: state.customDomainId,
        template_slug: state.templateSlug || 'default',
        settings_config: {
          tracking: {
            meta_pixel_id: state.metaPixelId || '',
            cta_track_event: state.ctaTrackEvent || 'InitiateCheckout'
          }
        },
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
        atualizarDisplayLinkPublico();
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

  // ══ PUBLICAR PÁGINA (ESTILO DE HOSPEDAGEM — PERGUNTA QUAL DOMÍNIO VINCULAR) ══
  var publishSelectedDomainId = null;
  var publishSelectedDomainName = '';

  function publicarFunilCompleto() {
    abrirModalPublicarHospedagem();
  }

  function abrirModalPublicarHospedagem() {
    var modal = document.getElementById('modal-publicar-hospedagem');
    if (!modal) {
      executarPublicacaoFunil();
      return;
    }

    publishSelectedDomainId = state.customDomainId;
    publishSelectedDomainName = state.customDomainName;

    if (!publishSelectedDomainId && state.availableDomains && state.availableDomains.length > 0) {
      var act = state.availableDomains.find(function(d) { return d.status === 'active'; }) || state.availableDomains[0];
      if (act) {
        publishSelectedDomainId = act.id;
        publishSelectedDomainName = act.domain;
      }
    }

    var slugInput = document.getElementById('publish-funnel-slug');
    if (slugInput) slugInput.value = state.slug || 'minha-campanha';

    renderizarHostingCards('publish-hosting-cards-grid', publishSelectedDomainId, state.slug);
    atualizarPublishUrlPreview();

    if (typeof modal.showModal === 'function') modal.showModal();
    else modal.style.display = 'block';
  }

  function fecharModalPublicarHospedagem() {
    var modal = document.getElementById('modal-publicar-hospedagem');
    if (!modal) return;
    if (typeof modal.close === 'function') modal.close();
    else modal.style.display = 'none';
  }

  function onPublishSlugInput(val) {
    atualizarPublishUrlPreview();
  }

  function atualizarPublishUrlPreview() {
    var slugInput = document.getElementById('publish-funnel-slug');
    var rawSlug = slugInput ? slugInput.value : state.slug;
    var safeSlug = sanitizeSlug(rawSlug) || 'minha-campanha';

    var domainText = publishSelectedDomainName;
    if (!domainText && publishSelectedDomainId && state.availableDomains) {
      var d = state.availableDomains.find(function(x) { return String(x.id) === String(publishSelectedDomainId); });
      if (d) domainText = d.domain;
    }
    if (!domainText) domainText = window.location.host;

    var previewEl = document.getElementById('publish-funnel-url-preview');
    if (previewEl) {
      previewEl.textContent = 'https://' + domainText + '/f/' + safeSlug;
    }

    renderizarHostingCards('publish-hosting-cards-grid', publishSelectedDomainId, safeSlug);
  }

  function confirmarPublicacaoHospedagem(event) {
    if (event && event.preventDefault) event.preventDefault();

    var slugInput = document.getElementById('publish-funnel-slug');
    var rawSlug = slugInput ? slugInput.value : state.slug;
    var safeSlug = sanitizeSlug(rawSlug) || 'pagina';

    state.slug = safeSlug;
    state.customDomainId = publishSelectedDomainId ? parseInt(publishSelectedDomainId, 10) : null;
    state.customDomainName = publishSelectedDomainName || '';

    // Sincroniza campos do topo
    var topSlug = document.getElementById('funnel-slug');
    if (topSlug) topSlug.value = state.slug;

    var topDom = document.getElementById('builder-funnel-domain-select');
    if (topDom) topDom.value = state.customDomainId ? String(state.customDomainId) : '';

    fecharModalPublicarHospedagem();
    executarPublicacaoFunil();
  }

  function executarPublicacaoFunil() {
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
        custom_domain_id: state.customDomainId,
        template_slug: state.templateSlug || 'default',
        settings_config: {
          tracking: {
            meta_pixel_id: state.metaPixelId || '',
            cta_track_event: state.ctaTrackEvent || 'InitiateCheckout'
          }
        },
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
        atualizarDisplayLinkPublico();
        
        var pubUrl = data.public_url || getPublicUrl();
        
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
          var tplBadge = f.template_slug === 'pressel-premium' ? '<span style="font-size:10px; background:#fef08a; color:#854d0e; padding:1px 6px; border-radius:4px; font-weight:700; margin-left:4px;">Pressel</span>' : '';
          var domainBadge = f.domain ? '<span style="font-size:10.5px; color:#3b82f6; display:block; margin-top:2px;">🌐 ' + escapeHtml(f.domain) + '</span>' : '';
          var pubUrl = f.public_url || (window.location.origin + '/f/' + f.slug);

          return `
            <div class="elementor-page-item ${isCurrent ? 'active' : ''}" onclick="abrirPaginaElementor(${f.id})">
              <div class="elementor-page-item-top">
                <span class="elementor-page-item-title">${escapeHtml(f.name || f.slug)}${tplBadge}</span>
                <span class="badge ${badgeClass}" style="font-size:10.5px;"><span class="badge-dot-indicator"></span> ${badgeTxt}</span>
              </div>
              <span class="elementor-page-item-slug">/f/${escapeHtml(f.slug)}</span>
              ${domainBadge}
              <div class="elementor-page-item-actions" onclick="event.stopPropagation()">
                <button type="button" class="btn-secondary" style="min-height:26px; padding:2px 8px; font-size:11px;" onclick="copiarValor('${pubUrl}')">Copiar Link</button>
                <a href="${pubUrl}" target="_blank" class="btn-secondary" style="min-height:26px; padding:2px 8px; font-size:11px; text-decoration:none;">Abrir</a>
                <button type="button" class="btn-secondary" style="min-height:26px; padding:2px 8px; font-size:11px;" onclick="abrirModalHospedagemParaPagina(${f.id})" title="Alterar domínio de hospedagem desta página">🌐 Hospedagem</button>
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
        state.templateSlug = f.template_slug || 'default';
        state.customDomainId = f.custom_domain_id || null;
        state.customDomainName = f.domain || '';
        state.currentHtml = f.draft_content || f.html_content || getDefaultTemplate();

        if (f.settings_config) {
          try {
            var cfg = typeof f.settings_config === 'string' ? JSON.parse(f.settings_config) : f.settings_config;
            if (cfg && cfg.tracking) {
              state.metaPixelId = cfg.tracking.meta_pixel_id || '';
              state.ctaTrackEvent = cfg.tracking.cta_track_event || 'InitiateCheckout';
            }
          } catch(e) {}
        }

        var nameInput = document.getElementById('funnel-name');
        if (nameInput) nameInput.value = state.name;

        var slugInput = document.getElementById('funnel-slug');
        if (slugInput) slugInput.value = state.slug;

        var domSelect = document.getElementById('builder-funnel-domain-select');
        if (domSelect) domSelect.value = state.customDomainId ? String(state.customDomainId) : '';

        var pixelInput = document.getElementById('builder-pixel-id-input');
        if (pixelInput) pixelInput.value = state.metaPixelId;

        var pixelEventSel = document.getElementById('builder-pixel-event-select');
        if (pixelEventSel) pixelEventSel.value = state.ctaTrackEvent || 'InitiateCheckout';

        if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
        renderPreview(true);
        marcarSalvo(f.status === 'published' ? 'Página no ar' : 'Rascunho salvo');
        atualizarDisplayLinkPublico();
        togglePagesDrawer();
      });
  }

  function novoFunilElementor() {
    abrirTelaSelecaoModelos('drawer');
  }

  function excluirPagina(id) {
    if (!confirm('Deseja realmente excluir esta página?')) return;
    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php?id=' + id + '&workspace_id=' + wsId, { method: 'DELETE' })
      .then(function(data) {
        window.showToast && window.showToast('Página removida com sucesso.', 'success');
        if (state.funnelId === id) carregarFunilInicial();
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
          var f = funnels.find(function(x) { return x.slug === 'oferta-especial'; }) || funnels[0];
          state.funnelId = f.id;
          state.name = f.name || 'Oferta especial';
          state.slug = f.slug || 'oferta-especial';
          state.templateSlug = f.template_slug || 'default';
          state.customDomainId = f.custom_domain_id || null;
          state.customDomainName = f.domain || '';
          state.currentHtml = f.draft_content || f.html_content || getDefaultTemplate();

          if (f.settings_config) {
            try {
              var cfg = typeof f.settings_config === 'string' ? JSON.parse(f.settings_config) : f.settings_config;
              if (cfg && cfg.tracking) {
                state.metaPixelId = cfg.tracking.meta_pixel_id || '';
                state.ctaTrackEvent = cfg.tracking.cta_track_event || 'InitiateCheckout';
              }
            } catch(e) {}
          }
          
          var nameInput = document.getElementById('funnel-name');
          if (nameInput) nameInput.value = state.name;

          var slugInput = document.getElementById('funnel-slug');
          if (slugInput) slugInput.value = state.slug;

          var domSelect = document.getElementById('builder-funnel-domain-select');
          if (domSelect) domSelect.value = state.customDomainId ? String(state.customDomainId) : '';

          var pixelInput = document.getElementById('builder-pixel-id-input');
          if (pixelInput) pixelInput.value = state.metaPixelId;

          var pixelEventSel = document.getElementById('builder-pixel-event-select');
          if (pixelEventSel) pixelEventSel.value = state.ctaTrackEvent || 'InitiateCheckout';

          marcarSalvo(f.status === 'published' ? 'Página no ar' : 'Rascunho');
        } else {
          state.currentHtml = getDefaultTemplate();
          marcarSalvo('Rascunho');
        }

        if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
        renderPreview(true);
        atualizarDisplayLinkPublico();
      })
      .catch(function(e) {
        state.currentHtml = getDefaultTemplate();
        if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
        renderPreview(true);
        atualizarDisplayLinkPublico();
      });
  }

  // ══ COMPONENTE VISUAL DE HOSPEDAGEM (ESTILO HOSPEDAGEM / VERCEL / HOSTINGER) ══
  function renderizarHostingCards(containerId, currentSelectedId, slug) {
    var container = document.getElementById(containerId);
    if (!container) return;

    var domains = state.availableDomains || [];
    var currentHost = window.location.host || 'app.upscale.com.br';
    var safeSlug = slug ? sanitizeSlug(slug) : (state.slug || 'minha-campanha');
    var isDefaultPlatformSelected = (!currentSelectedId || currentSelectedId === '' || currentSelectedId === '0');

    var html = '';

    // 1. Cards de cada domínio próprio cadastrado
    domains.forEach(function(d) {
      var isSelected = (String(d.id) === String(currentSelectedId));
      var isOk = (d.status === 'active');
      var fullUrl = 'https://' + escapeHtml(d.domain) + '/f/' + escapeHtml(safeSlug);

      html += `
        <div class="hosting-card ${isSelected ? 'selected' : ''}" onclick="selecionarHostingCard('${containerId}', '${d.id}', '${escapeHtml(d.domain)}')">
          <div class="hosting-card-head">
            <div class="hosting-card-radio-row">
              <div class="hosting-radio-dot"></div>
              <div>
                <div class="hosting-card-domain-name">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#175CFF" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                  <span>${escapeHtml(d.domain)}</span>
                  ${d.is_primary ? '<span class="badge badge-primary" style="font-size:10px; padding:1px 5px;">Principal</span>' : ''}
                </div>
                <div class="hosting-card-domain-desc">Domínio próprio verificado • Servidor <code>179.198.96.35</code></div>
              </div>
            </div>
            <div class="hosting-card-badges">
              <span class="badge ${isOk ? 'badge-success' : 'badge-warning'}" style="font-size:11px;">
                <span class="badge-dot-indicator"></span> ${isOk ? 'DNS Apontado' : 'Pendente DNS'}
              </span>
              <span class="badge ${isOk ? 'badge-success' : 'badge-neutral'}" style="font-size:11px;">
                ${isOk ? '🔒 SSL Ativo' : '⏳ Aguardando'}
              </span>
            </div>
          </div>
          <div class="hosting-card-url-box">
            <span>Link: ${fullUrl}</span>
            <span style="font-size:10.5px; opacity:0.8;">${isSelected ? '✓ Selecionado' : 'Clique para selecionar'}</span>
          </div>
        </div>
      `;
    });

    // 2. Opção de Hospedagem Padrão da Plataforma
    var defaultUrl = 'https://' + currentHost + '/f/' + escapeHtml(safeSlug);
    html += `
      <div class="hosting-card ${isDefaultPlatformSelected ? 'selected' : ''}" onclick="selecionarHostingCard('${containerId}', '', '')">
        <div class="hosting-card-head">
          <div class="hosting-card-radio-row">
            <div class="hosting-radio-dot"></div>
            <div>
              <div class="hosting-card-domain-name">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#64748b" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                <span>Hospedagem Padrão da Plataforma</span>
              </div>
              <div class="hosting-card-domain-desc">Subdomínio padrão do sistema • Sem necessidade de configurar DNS</div>
            </div>
          </div>
          <div class="hosting-card-badges">
            <span class="badge badge-primary" style="font-size:11px;">⚡ Instantâneo</span>
            <span class="badge badge-neutral" style="font-size:11px;">🔒 HTTPS Seguro</span>
          </div>
        </div>
        <div class="hosting-card-url-box">
          <span>Link: ${defaultUrl}</span>
          <span style="font-size:10.5px; opacity:0.8;">${isDefaultPlatformSelected ? '✓ Selecionado' : 'Clique para selecionar'}</span>
        </div>
      </div>
    `;

    // 3. Botão para conectar novo domínio
    html += `
      <button type="button" class="btn-hosting-add-inline" onclick="irParaAdicionarDominio()">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>+ Conectar Novo Domínio Personalizado (Apontar Tipo A para 179.198.96.35)</span>
      </button>
    `;

    container.innerHTML = html;
  }

  function selecionarHostingCard(containerId, domId, domName) {
    if (containerId === 'modal-hosting-cards-grid') {
      var hidId = document.getElementById('modal-funnel-domain-id');
      var hidName = document.getElementById('modal-funnel-domain-name');
      var selDom = document.getElementById('modal-funnel-domain');
      if (hidId) hidId.value = domId || '';
      if (hidName) hidName.value = domName || '';
      if (selDom) selDom.value = domId || '';
      atualizarModalUrlPreview();
    } else if (containerId === 'publish-hosting-cards-grid') {
      publishSelectedDomainId = domId || null;
      publishSelectedDomainName = domName || '';
      atualizarPublishUrlPreview();
    }
  }

  function irParaAdicionarDominio() {
    fecharModalNovoFunil();
    fecharModalPublicarHospedagem();
    if (window.selecionarSubabaDominios) {
      window.selecionarSubabaDominios('dominios');
    }
    var inp = document.getElementById('domain-input');
    if (inp) {
      inp.focus();
      inp.scrollIntoView({ behavior: 'smooth' });
    }
    window.showToast && window.showToast('Digite seu novo domínio e clique em "Conectar domínio".', 'info');
  }

  function abrirModalHospedagemParaPagina(id) {
    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php?workspace_id=' + wsId)
      .then(function(data) {
        var f = (data.funnels || []).find(function(x) { return x.id === id; });
        if (!f) return;
        abrirPaginaElementor(id);
        setTimeout(function() {
          abrirModalPublicarHospedagem();
        }, 180);
      });
  }

  // ══ MODAL DE CRIAÇÃO COM ESCOLHA DE TEMPLATE, SLUG E HOSPEDAGEM ══
  var selectedTemplateSlug = 'pressel-premium';

  function abrirModalNovoFunil() {
    selectedTemplateSlug = 'pressel-premium';
    var modal = document.getElementById('modal-novo-funil-template');
    if (!modal) return;

    selecionarCardTemplate('pressel-premium');
    var rnd = Math.floor(Math.random() * 900 + 100);
    var nameInput = document.getElementById('modal-funnel-name');
    var slugInput = document.getElementById('modal-funnel-slug');
    var pixInput = document.getElementById('modal-funnel-pixel');
    var hidId = document.getElementById('modal-funnel-domain-id');
    var hidName = document.getElementById('modal-funnel-domain-name');

    if (nameInput) nameInput.value = 'Ranger V6 Ação Especial';
    if (slugInput) {
      slugInput.value = 'ranger-v6-' + rnd;
      delete slugInput.dataset.manualEdit;
    }
    if (pixInput) pixInput.value = state.metaPixelId || '';

    // Escolhe o domínio inicial padrão: o ativo do workspace ou o primário
    var initialDomId = state.customDomainId;
    var initialDomName = state.customDomainName;
    if (!initialDomId && state.availableDomains && state.availableDomains.length > 0) {
      var act = state.availableDomains.find(function(d) { return d.status === 'active'; }) || state.availableDomains[0];
      if (act) {
        initialDomId = act.id;
        initialDomName = act.domain;
      }
    }
    if (hidId) hidId.value = initialDomId ? String(initialDomId) : '';
    if (hidName) hidName.value = initialDomName || '';

    var currentSlug = slugInput ? slugInput.value : ('ranger-v6-' + rnd);
    renderizarHostingCards('modal-hosting-cards-grid', initialDomId, currentSlug);
    atualizarModalUrlPreview();

    if (typeof modal.showModal === 'function') modal.showModal();
    else modal.style.display = 'block';
  }

  function fecharModalNovoFunil() {
    var modal = document.getElementById('modal-novo-funil-template');
    if (!modal) return;
    if (typeof modal.close === 'function') modal.close();
    else modal.style.display = 'none';
  }

  function selecionarCardTemplate(tplSlug) {
    selectedTemplateSlug = tplSlug;
    document.querySelectorAll('.template-card').forEach(function(card) {
      card.classList.toggle('active', card.getAttribute('data-template') === tplSlug);
    });

    var nameInput = document.getElementById('modal-funnel-name');
    var slugInput = document.getElementById('modal-funnel-slug');
    var rnd = Math.floor(Math.random() * 900 + 100);

    if (slugInput && !slugInput.dataset.manualEdit) {
      if (tplSlug === 'pressel-premium') {
        if (nameInput) nameInput.value = 'Ranger V6 Ação Especial';
        slugInput.value = 'ranger-v6-' + rnd;
      } else if (tplSlug === 'default') {
        if (nameInput) nameInput.value = 'Oferta Booster Especial';
        slugInput.value = 'oferta-booster-' + rnd;
      } else {
        if (nameInput) nameInput.value = 'Página Personalizada';
        slugInput.value = 'pagina-' + rnd;
      }
      atualizarModalUrlPreview();
    }
  }

  function onModalFunnelNameInput(val) {
    var slugInput = document.getElementById('modal-funnel-slug');
    if (slugInput && !slugInput.dataset.manualEdit) {
      slugInput.value = sanitizeSlug(val);
    }
    atualizarModalUrlPreview();
  }

  function onModalFunnelSlugInput(val) {
    var slugInput = document.getElementById('modal-funnel-slug');
    if (slugInput) slugInput.dataset.manualEdit = '1';
    atualizarModalUrlPreview();
  }

  function onModalFunnelDomainChange() {
    atualizarModalUrlPreview();
  }

  function atualizarModalUrlPreview() {
    var slug = sanitizeSlug((document.getElementById('modal-funnel-slug') || {}).value || 'minha-pagina');
    var hidId = (document.getElementById('modal-funnel-domain-id') || {}).value;
    var hidName = (document.getElementById('modal-funnel-domain-name') || {}).value;

    var domainText = hidName;
    if (!domainText && hidId && state.availableDomains) {
      var d = state.availableDomains.find(function(x) { return String(x.id) === String(hidId); });
      if (d) domainText = d.domain;
    }
    if (!domainText) domainText = window.location.host || 'app.upscale.com.br';

    var previewEl = document.getElementById('modal-funnel-url-preview');
    if (previewEl) {
      previewEl.textContent = 'https://' + domainText + '/f/' + slug;
    }

    renderizarHostingCards('modal-hosting-cards-grid', hidId, slug);
  }

  function criarNovoFunilConfirmar(event) {
    if (event && event.preventDefault) event.preventDefault();

    var name = (document.getElementById('modal-funnel-name') || {}).value || 'Nova Página';
    var rawSlug = (document.getElementById('modal-funnel-slug') || {}).value || 'pagina';
    var slug = sanitizeSlug(rawSlug) || ('pagina-' + Date.now().toString(36));
    var hidId = (document.getElementById('modal-funnel-domain-id') || {}).value;
    var domId = hidId ? parseInt(hidId, 10) : null;
    var domName = (document.getElementById('modal-funnel-domain-name') || {}).value || '';
    if (!domName && domId && state.availableDomains) {
      var d = state.availableDomains.find(function(x) { return String(x.id) === String(domId); });
      if (d) domName = d.domain;
    }
    var pixelId = ((document.getElementById('modal-funnel-pixel') || {}).value || '').trim();

    var initialHtml = '';
    if (selectedTemplateSlug === 'pressel-premium') {
      if (window.PresselPremium) {
        var cfg = window.PresselPremium.getDefaultConfig();
        cfg.settings.seo.title = name;
        if (pixelId) cfg.settings.tracking.meta_pixel_id = pixelId;
        if (typeof window.PresselPremium.renderHtml === 'function') {
          initialHtml = window.PresselPremium.renderHtml(cfg.blocks || {}, cfg.settings || {}, { is_editor: false });
        } else if (typeof window.PresselPremium.generateHtml === 'function') {
          initialHtml = window.PresselPremium.generateHtml(cfg);
        } else {
          initialHtml = getDefaultTemplate();
        }
      } else {
        initialHtml = getDefaultTemplate();
      }
    } else if (selectedTemplateSlug === 'blank') {
      initialHtml = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${escapeHtml(name)}</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:sans-serif; }
    body { background:#0b0e12; color:#ffffff; padding:40px 16px; min-height:100vh; display:flex; flex-direction:column; align-items:center; }
    .container { max-width:640px; width:100%; text-align:center; padding:40px 20px; }
    h1 { font-size:32px; margin-bottom:12px; }
    p { color:#94a3b8; margin-bottom:24px; font-size:16px; }
    .btn-cta { display:inline-block; background:#175CFF; color:#fff; text-decoration:none; padding:14px 28px; border-radius:10px; font-weight:700; }
  </style>
</head>
<body>
  <div class="container" data-block-id="blk_main" data-block-type="container">
    <h1 data-block-id="blk_title" data-block-type="heading">${escapeHtml(name)}</h1>
    <p data-block-id="blk_desc" data-block-type="text">Comece a personalizar sua nova página no construtor visual.</p>
    <a href="#participar" class="btn-cta js-buy" data-block-id="blk_btn" data-block-type="button">Participar Agora</a>
  </div>
  ${getDefaultTrackingRuntime(pixelId, 'InitiateCheckout', name, 0)}
</body>
</html>`;
    } else {
      initialHtml = getDefaultTemplate();
    }

    var wsId = window.currentWsId || 2;
    window.apiFetch('../api/admin/funnels.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'save',
        workspace_id: wsId,
        name: name,
        slug: slug,
        custom_domain_id: domId,
        template_slug: selectedTemplateSlug,
        settings_config: {
          tracking: {
            meta_pixel_id: pixelId,
            cta_track_event: 'InitiateCheckout'
          }
        },
        draft_content: initialHtml,
        html_content: initialHtml
      })
    })
    .then(function(data) {
      if (data.status === 'success') {
        state.funnelId = data.funnel_id;
        state.name = name;
        state.slug = data.slug || slug;
        state.templateSlug = selectedTemplateSlug;
        state.customDomainId = domId;
        state.customDomainName = domName;
        state.metaPixelId = pixelId;
        state.currentHtml = initialHtml;

        var nameInput = document.getElementById('funnel-name');
        if (nameInput) nameInput.value = state.name;

        var slugInput = document.getElementById('funnel-slug');
        if (slugInput) slugInput.value = state.slug;

        var domTopbar = document.getElementById('builder-funnel-domain-select');
        if (domTopbar && domId) domTopbar.value = String(domId);

        var pixInput = document.getElementById('builder-pixel-id-input');
        if (pixInput) pixInput.value = state.metaPixelId;

        if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);
        renderPreview(true);
        marcarSalvo('Rascunho criado');
        atualizarDisplayLinkPublico();
        fecharModalNovoFunil();
        carregarListaGaveta();
        window.showToast && window.showToast('Página criada com sucesso! Slug: /f/' + state.slug, 'success');
      } else {
        window.showToast && window.showToast(data.message || 'Erro ao criar página.', 'error');
      }
    })
    .catch(function(e) {
      console.error(e);
      window.showToast && window.showToast('Erro de comunicação ao criar página.', 'error');
    });
  }

  // ══ RASTREAMENTO & META PIXEL ══
  function atualizarPixelIdTracking(val) {
    state.metaPixelId = (val || '').trim();
    marcarModificado();
  }

  function atualizarPixelEventTracking(val) {
    state.ctaTrackEvent = val || 'InitiateCheckout';
    marcarModificado();
  }

  // ══ AÇÕES DE CÓDIGO HTML (FORMATAR E COPIAR) ══
  function copiarCodigoHtml() {
    var code = state.codeMirror ? state.codeMirror.getValue() : state.currentHtml;
    window.copiarValor && window.copiarValor(code);
  }

  function formatarCodigoHtml() {
    if (!state.codeMirror) return;
    var val = state.codeMirror.getValue();
    state.codeMirror.setValue(val);
    window.showToast && window.showToast('Código formatado com sucesso.', 'success');
  }

  function copiarLinkPublicoAtual() {
    var pubUrl = getPublicUrl();
    window.copiarValor && window.copiarValor(pubUrl);
  }

  // ══════════════════════════════════════════════════════════════════
  // 12. TELA DEDICADA DE SELEÇÃO DE MODELOS DE PÁGINA (DESIGN OFICIAL)
  // ══════════════════════════════════════════════════════════════════
  var selectedTemplatePickerId = 'pressel-premium-campanhas';
  var templatePickerOrigin = 'editor';

  var TEMPLATE_PICKER_DEFS = {
    'pressel-premium-campanhas': {
      id: 'pressel-premium-campanhas',
      name: 'Pressel Premium — Campanhas',
      templateSlug: 'pressel-premium',
      defaultSlugPrefix: 'ranger-v6',
      cardId: 'tp-card-pressel-premium-campanhas'
    },
    'booster-padrao': {
      id: 'booster-padrao',
      name: 'Página Booster Padrão',
      templateSlug: 'default',
      defaultSlugPrefix: 'booster',
      cardId: 'tp-card-booster-padrao'
    },
    'pagina-em-branco': {
      id: 'pagina-em-branco',
      name: 'Página em Branco',
      templateSlug: 'blank',
      defaultSlugPrefix: 'pagina',
      cardId: 'tp-card-pagina-em-branco'
    }
  };

  var currentPreviewModelId = null;
  var lastFocusedElement = null;

  function abrirTelaSelecaoModelos(origem) {
    templatePickerOrigin = origem || 'editor';
    selecionarModeloPicker('pressel-premium-campanhas');

    // Fechar prévia caso estivesse aberta
    fecharPreviaModal();

    // Ativar modo de tela cheia dentro da área principal
    document.body.classList.add('in-template-picker');

    // Ocultar todas as outras view-panes e exibir a tela dedicada
    document.querySelectorAll('.view-pane').forEach(function(p) {
      p.classList.remove('active');
    });

    var pickerPane = document.getElementById('view-template-picker');
    if (pickerPane) {
      pickerPane.classList.add('active');
      window.scrollTo({ top: 0, behavior: 'instant' });
    }

    // Manter navegação de 'Domínios e funis' destacada na sidebar
    var navBtnSites = document.getElementById('nav-btn-sites');
    if (navBtnSites) {
      document.querySelectorAll('.nav-item-btn').forEach(function(btn) { btn.classList.remove('active'); });
      navBtnSites.classList.add('active');
    }

    // Fechar drawer caso estivesse aberto
    var drawer = document.getElementById('builder-pages-drawer');
    var backdrop = document.getElementById('builder-drawer-backdrop');
    if (drawer && drawer.classList.contains('open')) {
      drawer.classList.remove('open');
      if (backdrop) backdrop.classList.remove('open');
    }
  }

  function voltarOrigemTemplatePicker() {
    fecharPreviaModal();
    document.body.classList.remove('in-template-picker');

    var pickerPane = document.getElementById('view-template-picker');
    if (pickerPane) pickerPane.classList.remove('active');

    if (templatePickerOrigin === 'drawer') {
      if (window.navigate) window.navigate('sites');
      var funisTab = document.getElementById('sites-subtab-funis');
      if (funisTab && window.selecionarSubabaDominios) {
        window.selecionarSubabaDominios('funis');
      }
      setTimeout(function() {
        togglePagesDrawer(true);
      }, 50);
    } else if (templatePickerOrigin === 'dash') {
      if (window.navigate) window.navigate('dash');
    } else {
      if (window.navigate) window.navigate('sites');
      if (window.selecionarSubabaDominios) {
        window.selecionarSubabaDominios('funis');
      }
    }
  }

  function selecionarModeloPicker(modelId) {
    if (!TEMPLATE_PICKER_DEFS[modelId]) modelId = 'pressel-premium-campanhas';
    selectedTemplatePickerId = modelId;

    var def = TEMPLATE_PICKER_DEFS[modelId];

    // Atualizar classes e botões nos 3 cards
    ['pressel-premium-campanhas', 'booster-padrao', 'pagina-em-branco'].forEach(function(id) {
      var cardDef = TEMPLATE_PICKER_DEFS[id];
      var cardEl = document.getElementById(cardDef.cardId);
      if (!cardEl) return;

      var isSelected = (id === modelId);
      cardEl.classList.toggle('selected', isSelected);

      var btn = cardEl.querySelector('.tp-btn-select');
      if (btn) {
        if (isSelected) {
          btn.classList.add('selected');
          btn.textContent = '✓ Selecionado';
        } else {
          btn.classList.remove('selected');
          btn.textContent = 'Selecionar';
        }
      }
    });

    // Atualizar nome na barra inferior fixa
    var bottomName = document.getElementById('tp-bottom-selected-name');
    if (bottomName) {
      bottomName.textContent = def.name;
    }
  }

  function getTemplateHtmlForModel(modelId, sanitizeForPreview) {
    var html = '';
    if (modelId === 'pressel-premium-campanhas' || modelId === 'pressel-premium') {
      if (window.PresselPremium) {
        var cfg = window.PresselPremium.getDefaultConfig();
        if (cfg && cfg.settings && cfg.settings.seo) {
          cfg.settings.seo.title = 'Ranger V6 Brayam Braz';
        }
        if (sanitizeForPreview && cfg && cfg.settings && cfg.settings.tracking) {
          cfg.settings.tracking.meta_pixel_id = '';
        }
        if (typeof window.PresselPremium.renderHtml === 'function') {
          html = window.PresselPremium.renderHtml(cfg.blocks || {}, cfg.settings || {}, { is_editor: false });
        } else if (typeof window.PresselPremium.generateHtml === 'function') {
          html = window.PresselPremium.generateHtml(cfg);
        } else {
          html = getDefaultTemplate();
        }
      } else {
        html = getDefaultTemplate();
      }
    } else if (modelId === 'pagina-em-branco' || modelId === 'blank') {
      html = `<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Página em Branco — upScale</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background:#F8FAFC; color:#0F172A; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:40px 20px; text-align:center; }
    .blank-wrap { max-width:540px; width:100%; text-align:center; background:#ffffff; border:1px dashed #CBD5E1; border-radius:16px; padding:48px 32px; box-shadow:0 4px 16px rgba(0,0,0,0.03); }
    .blank-icon { width:52px; height:52px; border-radius:12px; background:#EFF6FF; color:#205BFF; display:inline-flex; align-items:center; justify-content:center; margin:0 auto 16px auto; font-weight:800; font-family:monospace; font-size:20px; }
    h1 { font-size:22px; font-weight:800; color:#0F172A; margin-bottom:8px; }
    p { font-size:14px; color:#64748B; margin-bottom:24px; line-height:1.5; }
    .btn-action { display:inline-flex; align-items:center; justify-content:center; gap:8px; background:#205BFF; color:#ffffff; font-weight:700; font-size:14px; padding:12px 24px; border-radius:8px; text-decoration:none; }
  </style>
</head>
<body>
  <div class="blank-wrap" data-block-id="blk_blank_box" data-block-type="container">
    <div class="blank-icon">&lt;/&gt;</div>
    <h1 data-block-id="blk_title" data-block-type="heading">Página em branco — pronta para personalizar</h1>
    <p data-block-id="blk_desc" data-block-type="text">Insira títulos, textos, cards de oferta, cronômetro e depoimentos no editor visual.</p>
    <a href="#comecar" class="btn-action js-buy" data-block-id="blk_cta" data-block-type="button">Personalizar no Editor</a>
  </div>
</body>
</html>`;
    } else {
      // booster-padrao ou default
      html = getDefaultTemplate();
    }

    if (sanitizeForPreview) {
      // Neutralizar scripts de checkout e tracking externo no modo preview ampliado
      html = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, function(match) {
        if (/connect\.facebook\.net|fbevents\.js|gtag|clarity/i.test(match)) {
          return '<!-- tracking disabled in preview -->';
        }
        return match;
      });

      // Injetar desativador de pixels e links/botões para que a prévia nunca gere conversões ou navegações
      var safeGuardScript = `
        <script>
          window.fbq = function() {};
          window.gtag = function() {};
          document.addEventListener('click', function(e) {
            var el = e.target.closest('a, button, [onclick], input[type="submit"]');
            if (el) {
              e.preventDefault();
              e.stopPropagation();
            }
          }, true);
          document.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
          }, true);
        <\/script>
      `;
      if (html.indexOf('</body>') !== -1) {
        html = html.replace('</body>', safeGuardScript + '</body>');
      } else {
        html += safeGuardScript;
      }
    }

    return html;
  }

  function ampliarPreviaModal(modelId, event) {
    if (event && event.stopPropagation) event.stopPropagation();

    // Guardar elemento acionador para restaurar foco ao fechar
    lastFocusedElement = (event && event.currentTarget) ? event.currentTarget : document.activeElement;
    currentPreviewModelId = modelId;

    var def = TEMPLATE_PICKER_DEFS[modelId] || TEMPLATE_PICKER_DEFS['pressel-premium-campanhas'];
    var titleEl = document.getElementById('tp-modal-preview-name');
    if (titleEl) {
      titleEl.textContent = 'Prévia — ' + def.name;
    }

    var modal = document.getElementById('modal-template-preview-large');
    var loadingEl = document.getElementById('tp-modal-loading');
    var errorEl = document.getElementById('tp-modal-error');
    var iframe = document.getElementById('tp-modal-preview-iframe');

    if (errorEl) errorEl.style.display = 'none';
    if (loadingEl) loadingEl.style.display = 'flex';

    if (modal) {
      modal.classList.add('open');
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');
    }
    document.body.classList.add('tp-modal-open');

    setPreviaModalDevice('desktop');

    try {
      var html = getTemplateHtmlForModel(modelId, true);
      if (!html || html.length < 30) {
        throw new Error('Conteúdo do template não encontrado ou vazio');
      }

      if (iframe) {
        iframe.onload = function() {
          if (loadingEl) loadingEl.style.display = 'none';
        };
        iframe.srcdoc = html;
      }
    } catch (err) {
      console.error('[Prévia do Modelo] Falha ao renderizar:', err);
      if (loadingEl) loadingEl.style.display = 'none';
      if (errorEl) errorEl.style.display = 'flex';
    }

    // Foco acessível no botão de fechar do modal
    setTimeout(function() {
      if (modal) {
        var closeBtn = modal.querySelector('.tp-modal-close-btn');
        if (closeBtn) closeBtn.focus();
      }
    }, 80);
  }

  function fecharPreviaModal() {
    var modal = document.getElementById('modal-template-preview-large');
    if (modal) {
      modal.classList.remove('open');
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
    }
    document.body.classList.remove('tp-modal-open');

    var iframe = document.getElementById('tp-modal-preview-iframe');
    if (iframe) {
      iframe.srcdoc = 'about:blank';
    }

    var loadingEl = document.getElementById('tp-modal-loading');
    if (loadingEl) loadingEl.style.display = 'none';

    var errorEl = document.getElementById('tp-modal-error');
    if (errorEl) errorEl.style.display = 'none';

    // Restaurar foco no botão que abriu a prévia
    if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
      try {
        lastFocusedElement.focus();
      } catch (e) {}
    }
  }

  function recarregarPreviaAtual() {
    if (currentPreviewModelId) {
      ampliarPreviaModal(currentPreviewModelId);
    }
  }

  function setPreviaModalDevice(device) {
    var wrap = document.getElementById('tp-modal-iframe-wrap');
    var btnDesktop = document.getElementById('tp-preview-dev-desktop');
    var btnMobile = document.getElementById('tp-preview-dev-mobile');

    if (device === 'mobile') {
      if (wrap) wrap.classList.add('device-mobile');
      if (btnMobile) btnMobile.classList.add('active');
      if (btnDesktop) btnDesktop.classList.remove('active');
    } else {
      if (wrap) wrap.classList.remove('device-mobile');
      if (btnDesktop) btnDesktop.classList.add('active');
      if (btnMobile) btnMobile.classList.remove('active');
    }
  }

  function confirmarUsoModelo() {
    var btn = document.getElementById('btn-tp-confirmar');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="status-dot" style="background:#fff; animation:pulse 0.8s infinite;"></span><span>Criando página…</span>';
    }

    var modelId = selectedTemplatePickerId || 'pressel-premium-campanhas';
    var def = TEMPLATE_PICKER_DEFS[modelId] || TEMPLATE_PICKER_DEFS['pressel-premium-campanhas'];
    
    var rndSuffix = Math.random().toString(36).substring(2, 7);
    var generatedSlug = def.defaultSlugPrefix + '-' + rndSuffix;
    var generatedName = def.name;

    var initialHtml = getTemplateHtmlForModel(modelId, false);
    var wsId = window.currentWsId || 2;

    window.apiFetch('../api/admin/funnels.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'save',
        workspace_id: wsId,
        name: generatedName,
        slug: generatedSlug,
        custom_domain_id: null,
        template_slug: def.templateSlug,
        settings_config: {
          tracking: {
            meta_pixel_id: '',
            cta_track_event: 'InitiateCheckout'
          }
        },
        draft_content: initialHtml,
        html_content: initialHtml
      })
    })
    .then(function(data) {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<span>Usar este modelo</span><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
      }

      if (data.status === 'success') {
        state.funnelId = data.funnel_id;
        state.name = generatedName;
        state.slug = data.slug || generatedSlug;
        state.templateSlug = def.templateSlug;
        state.customDomainId = null;
        state.customDomainName = '';
        state.metaPixelId = '';
        state.currentHtml = initialHtml;

        // Atualizar inputs no editor
        var nameInput = document.getElementById('funnel-name');
        if (nameInput) nameInput.value = state.name;

        var slugInput = document.getElementById('funnel-slug');
        if (slugInput) slugInput.value = state.slug;

        var domTopbar = document.getElementById('builder-funnel-domain-select');
        if (domTopbar) domTopbar.value = '';

        var pixInput = document.getElementById('builder-pixel-id-input');
        if (pixInput) pixInput.value = '';

        if (state.codeMirror) state.codeMirror.setValue(state.currentHtml);

        // Voltar para a visualização do editor
        fecharPreviaModal();
        document.body.classList.remove('in-template-picker');

        var pickerPane = document.getElementById('view-template-picker');
        if (pickerPane) pickerPane.classList.remove('active');

        if (window.navigate) {
          window.navigate('sites');
        } else {
          document.querySelectorAll('.view-pane').forEach(function(p) { p.classList.remove('active'); });
          var sitesView = document.getElementById('view-sites');
          if (sitesView) sitesView.classList.add('active');
        }

        if (window.selecionarSubabaDominios) {
          window.selecionarSubabaDominios('funis');
        }

        renderPreview(true);
        marcarSalvo('Rascunho criado');
        atualizarDisplayLinkPublico();
        carregarListaGaveta();

        window.showToast && window.showToast('Página "' + generatedName + '" criada com sucesso a partir do modelo selecionado!', 'success');
      } else {
        window.showToast && window.showToast(data.message || 'Erro ao criar página com modelo.', 'error');
      }
    })
    .catch(function(e) {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<span>Usar este modelo</span><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
      }
      console.error(e);
      window.showToast && window.showToast('Erro de comunicação ao criar página.', 'error');
    });
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

      if (event.data.type === 'elementor:duplicate') {
        duplicarElementoSelecionado();
      }

      if (event.data.type === 'elementor:delete') {
        excluirElementoSelecionado();
      }
    });

    // Fechar prévia ampliada ao clicar no backdrop
    var prevBackdrop = document.querySelector('.tp-modal-backdrop');
    if (prevBackdrop) {
      prevBackdrop.addEventListener('click', function() {
        fecharPreviaModal();
      });
    }

    // Atalhos Ctrl+Z, Ctrl+Y, Esc e Focus Trap no modal de prévia
    document.addEventListener('keydown', function(e) {
      var modal = document.getElementById('modal-template-preview-large');
      var isModalOpen = modal && modal.classList.contains('open');

      if (e.key === 'Escape') {
        if (isModalOpen) {
          e.preventDefault();
          fecharPreviaModal();
          return;
        }
      }

      if (e.key === 'Tab' && isModalOpen) {
        var focusables = modal.querySelectorAll('button:not([disabled]), [tabindex]:not([tabindex="-1"])');
        if (focusables.length > 0) {
          var first = focusables[0];
          var last = focusables[focusables.length - 1];
          if (e.shiftKey) {
            if (document.activeElement === first) {
              e.preventDefault();
              last.focus();
            }
          } else {
            if (document.activeElement === last) {
              e.preventDefault();
              first.focus();
            }
          }
        }
      }

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
  window.trocarDominioBuilder = trocarDominioBuilder;
  window.atualizarSlugBuilder = atualizarSlugBuilder;
  window.abrirModalNovoFunil = abrirModalNovoFunil;
  window.fecharModalNovoFunil = fecharModalNovoFunil;
  window.selecionarCardTemplate = selecionarCardTemplate;
  window.onModalFunnelNameInput = onModalFunnelNameInput;
  window.onModalFunnelSlugInput = onModalFunnelSlugInput;
  window.onModalFunnelDomainChange = onModalFunnelDomainChange;
  window.criarNovoFunilConfirmar = criarNovoFunilConfirmar;
  window.atualizarPixelIdTracking = atualizarPixelIdTracking;
  window.atualizarPixelEventTracking = atualizarPixelEventTracking;
  window.abrirModalPublicarHospedagem = abrirModalPublicarHospedagem;
  window.fecharModalPublicarHospedagem = fecharModalPublicarHospedagem;
  window.confirmarPublicacaoHospedagem = confirmarPublicacaoHospedagem;
  window.selecionarHostingCard = selecionarHostingCard;
  window.onPublishSlugInput = onPublishSlugInput;
  window.irParaAdicionarDominio = irParaAdicionarDominio;
  window.abrirModalHospedagemParaPagina = abrirModalHospedagemParaPagina;
  window.renderizarHostingCards = renderizarHostingCards;

  // Novos controles do Inspetor Direito e Seções
  window.switchInspectorTab = switchInspectorTab;
  window.setInspectorAlign = setInspectorAlign;
  window.aplicarEstiloCor = aplicarEstiloCor;
  window.atualizarCorHex = atualizarCorHex;
  window.aplicarPaddingSeparado = aplicarPaddingSeparado;
  window.toggleSombraInspector = toggleSombraInspector;
  window.toggleAccordionSecoes = toggleAccordionSecoes;
  window.inserirSecaoPronta = inserirSecaoPronta;
  window.sincronizarTituloPagina = sincronizarTituloPagina;
  window.focarNomePagina = focarNomePagina;
  window.abrirPreviaNovaAba = abrirPreviaNovaAba;
  window.abrirEditorCodigoModal = abrirEditorCodigoModal;

  // Tela dedicada de seleção de modelos
  window.abrirTelaSelecaoModelos = abrirTelaSelecaoModelos;
  window.voltarOrigemTemplatePicker = voltarOrigemTemplatePicker;
  window.selecionarModeloPicker = selecionarModeloPicker;
  window.ampliarPreviaModal = ampliarPreviaModal;
  window.fecharPreviaModal = fecharPreviaModal;
  window.setPreviaModalDevice = setPreviaModalDevice;
  window.confirmarUsoModelo = confirmarUsoModelo;
  window.recarregarPreviaAtual = recarregarPreviaAtual;

})(window, document);

