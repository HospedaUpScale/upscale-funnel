/**
 * ══════════════════════════════════════════════════════════════════
 * LANDING ENXUTA — CAMPANHAS (4 estilos de fonte, mesma base)
 *
 * Página curta para campanhas: topo com foto de fundo, 3 passos,
 * grupo VIP, dúvidas e rodapé com chamada final.
 *
 * Cada peça visível vem marcada com data-block-id / data-block-type,
 * nos tipos que o construtor entende:
 *   heading / text  → textos (o painel troca o texto inteiro do bloco,
 *                     por isso cada bloco tem só texto, sem tags dentro)
 *   button          → botões de compra (disparam InitiateCheckout)
 *   link            → links comuns: WhatsApp, regulamento, menu
 *   image           → o topo inteiro; a primeira <img> dentro dele é a
 *                     foto de fundo trocada pelo painel
 *   container       → seções, passos e cartões (duplicar / excluir)
 *
 * Fontes, cor de destaque e escurecimento da foto ficam em
 * <style id="lx-theme"> e são trocados pelo painel "Aparência da página"
 * (readTheme / applyTheme abaixo), sem mexer no resto da página.
 * ══════════════════════════════════════════════════════════════════
 */

(function (window) {
  'use strict';

  var VARIANTS = {
    impacto: {
      label: 'Impacto — Anton e Outfit',
      fontsUrl: 'https://fonts.googleapis.com/css2?family=Anton&family=Outfit:wght@400;600;700;800&display=swap',
      vars: {
        'font-display': "'Anton', Impact, 'Arial Narrow', sans-serif",
        'font-body': "'Outfit', system-ui, -apple-system, 'Segoe UI', sans-serif",
        'title-weight': '400',
        'title-style': 'normal',
        'title-size': 'clamp(48px, 8vw, 84px)',
        'title-leading': '.95',
        'title-tracking': '.005em',
        'title-case': 'uppercase',
        'h2-font': 'var(--font-body)',
        'h2-weight': '800',
        'h2-tracking': '0',
        'num-size': '56px'
      }
    },
    velocidade: {
      label: 'Velocidade — Exo 2 itálico',
      fontsUrl: 'https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,400;0,600;0,700;0,800;1,800&display=swap',
      vars: {
        'font-display': "'Exo 2', 'Arial Narrow', sans-serif",
        'font-body': "'Exo 2', system-ui, -apple-system, 'Segoe UI', sans-serif",
        'title-weight': '800',
        'title-style': 'italic',
        'title-size': 'clamp(40px, 6.6vw, 72px)',
        'title-leading': '1',
        'title-tracking': '-.01em',
        'title-case': 'uppercase',
        'h2-font': 'var(--font-body)',
        'h2-weight': '800',
        'h2-tracking': '-.01em',
        'num-size': '54px'
      }
    },
    elegante: {
      label: 'Elegante — DM Serif e DM Sans',
      fontsUrl: 'https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;600;700;800&display=swap',
      vars: {
        'font-display': "'DM Serif Display', Georgia, 'Times New Roman', serif",
        'font-body': "'DM Sans', system-ui, -apple-system, 'Segoe UI', sans-serif",
        'title-weight': '400',
        'title-style': 'normal',
        'title-size': 'clamp(46px, 7.4vw, 82px)',
        'title-leading': '1.02',
        'title-tracking': '-.01em',
        'title-case': 'none',
        'h2-font': 'var(--font-display)',
        'h2-weight': '400',
        'h2-tracking': '0',
        'num-size': '60px'
      }
    },
    moderna: {
      label: 'Moderna — Unbounded e Manrope',
      fontsUrl: 'https://fonts.googleapis.com/css2?family=Unbounded:wght@700;800&family=Manrope:wght@400;600;700;800&display=swap',
      vars: {
        'font-display': "'Unbounded', 'Arial Black', sans-serif",
        'font-body': "'Manrope', system-ui, -apple-system, 'Segoe UI', sans-serif",
        'title-weight': '800',
        'title-style': 'normal',
        'title-size': 'clamp(32px, 5.4vw, 60px)',
        'title-leading': '1.06',
        'title-tracking': '-.02em',
        'title-case': 'uppercase',
        'h2-font': 'var(--font-display)',
        'h2-weight': '700',
        'h2-tracking': '-.01em',
        'num-size': '44px'
      }
    }
  };

  var DEFAULT_THEME = {
    accent: '#FFD400',
    'accent-ink': '#0A0A0A',
    'hero-shade': '.62'
  };

  // Ordem fixa das variáveis no bloco #lx-theme (facilita ler o código no editor)
  var THEME_KEYS = ['accent', 'accent-ink', 'hero-shade', 'font-display', 'font-body', 'title-weight', 'title-style',
    'title-size', 'title-leading', 'title-tracking', 'title-case', 'h2-font', 'h2-weight', 'h2-tracking', 'num-size'];

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function themeCss(vars) {
    var lines = THEME_KEYS.filter(function (k) { return vars[k] !== undefined; })
      .map(function (k) { return '      --' + k + ': ' + vars[k] + ';'; });
    return '\n    :root {\n' + lines.join('\n') + '\n    }\n  ';
  }

  function parseThemeVars(css) {
    var vars = {};
    String(css || '').replace(/--([a-z0-9-]+)\s*:\s*([^;]+);/gi, function (_, k, v) {
      vars[k] = v.trim();
      return '';
    });
    return vars;
  }

  // Texto escuro ou claro sobre a cor de destaque, o que der mais contraste.
  function inkFor(hex) {
    var m = /^#([0-9a-f]{6})$/i.exec(hex);
    if (!m) return DEFAULT_THEME['accent-ink'];
    var n = parseInt(m[1], 16);
    var lin = [n >> 16, (n >> 8) & 255, n & 255].map(function (c) {
      c /= 255;
      return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    var L = 0.2126 * lin[0] + 0.7152 * lin[1] + 0.0722 * lin[2];
    var contrastDark = (L + 0.05) / (0.0033 + 0.05); // #0A0A0A
    var contrastLight = 1.05 / (L + 0.05);           // #FFFFFF
    return contrastDark >= contrastLight ? '#0A0A0A' : '#FFFFFF';
  }

  function getHtml(options) {
    options = options || {};
    var variantId = VARIANTS[options.variant] ? options.variant : 'impacto';
    var variant = VARIANTS[variantId];
    var title = escapeHtml(options.title || 'Ford Ranger 2026 · Zero KM');
    var vars = {};
    Object.keys(DEFAULT_THEME).forEach(function (k) { vars[k] = DEFAULT_THEME[k]; });
    Object.keys(variant.vars).forEach(function (k) { vars[k] = variant.vars[k]; });

    return `<!DOCTYPE html>
<html lang="pt-BR" data-lx-font="${variantId}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${title}</title>
  <meta name="description" content="Conheça a campanha e veja como participar.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link id="lx-fonts" rel="stylesheet" href="${variant.fontsUrl}">
  <style id="lx-theme">${themeCss(vars)}</style>
  <style>
    :root {
      --bg: #0A0A0A;
      --surface: #141414;
      --line: #232323;
      --fg: #F2F2F2;
      --muted: #B5B5B5;
    }
    * { box-sizing: border-box; }
    @media (prefers-reduced-motion: no-preference) { html { scroll-behavior: smooth; } }
    body { margin: 0; background: var(--bg); color: var(--fg); font-family: var(--font-body); font-size: 16px; line-height: 1.5; -webkit-font-smoothing: antialiased; }
    h1, h2, h3, p, ol { margin: 0; }
    img { max-width: 100%; }
    a { color: inherit; }
    a:focus-visible { outline: 2px solid #ffffff; outline-offset: 3px; border-radius: 4px; }
    .lx-wrap { max-width: 1120px; margin: 0 auto; padding-inline: clamp(16px, 4vw, 40px); }

    /* Botões */
    .lx-btn { display: inline-flex; align-items: center; justify-content: center; gap: 12px; min-height: 48px; padding: 0 22px; border-radius: 6px; background: var(--accent); color: var(--accent-ink); font-family: var(--font-body); font-weight: 800; font-size: 15px; letter-spacing: .04em; text-transform: uppercase; text-decoration: none; transition: filter .15s ease; }
    .lx-btn:hover { filter: brightness(1.08); }
    .lx-btn-outline { background: transparent; color: var(--accent); border: 1.5px solid var(--accent); min-height: 44px; font-size: 13px; letter-spacing: .06em; }
    .lx-btn-lg { min-height: 56px; padding: 0 30px; font-size: 17px; }
    .lx-btn-arrow::after { content: ""; width: 20px; height: 20px; flex: none; background: currentColor; -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12h14M13 6l6 6-6 6'/%3E%3C/svg%3E") center / contain no-repeat; mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12h14M13 6l6 6-6 6'/%3E%3C/svg%3E") center / contain no-repeat; }

    /* Cabeçalho */
    .lx-header { border-bottom: 1px solid var(--line); background: var(--bg); }
    .lx-header-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 24px; padding-block: 14px; }
    .lx-logo { display: inline-flex; gap: .3em; font-weight: 800; font-size: 20px; letter-spacing: .02em; color: #ffffff; text-decoration: none; text-transform: uppercase; }
    .lx-logo-b { color: var(--accent); }
    .lx-nav { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 24px; }
    .lx-nav-link { font-size: 15px; color: #D4D4D4; text-decoration: none; }
    .lx-nav-link:hover { color: #ffffff; }
    @media (max-width: 600px) { .lx-nav-link { display: none; } }

    /* Topo com foto de fundo */
    .lx-hero { position: relative; isolation: isolate; display: flex; align-items: center; justify-content: center; min-height: clamp(520px, 82vh, 720px); padding: clamp(72px, 10vw, 120px) clamp(16px, 4vw, 40px); text-align: center; background: var(--bg); }
    .lx-hero-img { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; max-width: none; object-fit: cover; object-position: center 45%; }
    .lx-hero-shade { position: absolute; inset: 0; z-index: -1; background: rgba(6, 6, 6, var(--hero-shade)); pointer-events: none; }
    .lx-hero-shade::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 180px; background: linear-gradient(to bottom, rgba(10, 10, 10, 0), var(--bg)); }
    .lx-hero-content { width: 100%; max-width: 780px; display: flex; flex-direction: column; align-items: center; gap: 22px; }
    .lx-eyebrow { display: inline-flex; align-items: center; justify-content: center; gap: 12px; color: var(--accent); font-weight: 700; font-size: 13px; letter-spacing: .14em; text-transform: uppercase; }
    .lx-eyebrow::before, .lx-eyebrow::after { content: ""; width: 28px; height: 3px; flex: none; background: currentColor; }
    .lx-title { font-family: var(--font-display); font-weight: var(--title-weight); font-style: var(--title-style); font-size: var(--title-size); line-height: var(--title-leading); letter-spacing: var(--title-tracking); text-transform: var(--title-case); }
    .lx-title > span { display: block; }
    .lx-title-1 { color: #ffffff; }
    .lx-title-2 { color: var(--accent); }
    .lx-price { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: center; gap: 4px 10px; font-size: 16px; color: #D4D4D4; }
    .lx-price strong { font-size: 32px; font-weight: 800; color: #ffffff; }
    .lx-hero-actions { display: flex; flex-direction: column; align-items: center; gap: 12px; }
    .lx-small-link { font-size: 13px; color: #CFCFCF; text-underline-offset: 3px; }

    /* Seções */
    .lx-section { border-top: 1px solid var(--line); padding-block: 64px; }
    .lx-h2 { font-family: var(--h2-font); font-weight: var(--h2-weight); letter-spacing: var(--h2-tracking); font-size: clamp(26px, 3vw, 32px); line-height: 1.2; color: #ffffff; }
    .lx-section .lx-h2 { margin-bottom: 32px; }

    /* Como participar */
    .lx-steps { list-style: none; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 32px; }
    .lx-step { display: flex; align-items: flex-start; gap: 18px; padding: 8px; margin: -8px; } /* borda clicável para selecionar o passo inteiro no editor */
    .lx-step-num { font-family: var(--font-display); font-weight: var(--title-weight); font-style: var(--title-style); font-size: var(--num-size); line-height: 1; color: var(--accent); -webkit-text-fill-color: transparent; -webkit-text-stroke: 1.5px currentColor; }
    .lx-step-body { display: flex; flex-direction: column; gap: 8px; padding-top: 4px; }
    .lx-step-icon { width: 30px; height: 30px; color: var(--accent); }
    .lx-step h3 { font-size: 18px; font-weight: 700; color: #ffffff; }
    .lx-step p { font-size: 15px; color: var(--muted); }

    /* Grupo VIP */
    .lx-vip-section { padding-block: 0 64px; }
    .lx-vip { display: flex; flex-wrap: wrap; align-items: center; gap: 20px 24px; background: var(--surface); border: 1px solid var(--line); border-radius: 10px; padding: clamp(22px, 4vw, 36px); }
    .lx-vip-icon { width: 52px; height: 52px; flex: none; color: var(--accent); }
    .lx-vip-text { flex: 1 1 300px; min-width: 0; display: flex; flex-direction: column; gap: 6px; }
    .lx-vip-title { font-size: clamp(22px, 2.6vw, 28px); }
    .lx-vip-title .lx-accent { color: var(--accent); }
    .lx-vip-text p { font-size: 15px; color: var(--muted); }

    /* Dúvidas */
    .lx-faq { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
    .lx-faq-item { display: flex; flex-direction: column; gap: 10px; background: var(--surface); border: 1px solid var(--line); border-radius: 8px; padding: 24px; }
    .lx-faq-item h3 { font-size: 17px; font-weight: 700; color: #ffffff; }
    .lx-faq-item p { font-size: 15px; color: var(--muted); }

    /* Rodapé */
    .lx-footer { border-top: 1px solid var(--line); background: #070707; }
    .lx-footer-cta { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 20px; padding-block: 48px 40px; }
    .lx-footer-cta .lx-h2 { font-size: clamp(24px, 2.8vw, 30px); }
    .lx-footer-cta p { margin-top: 4px; font-size: 15px; color: var(--muted); }
    .lx-footer-bottom { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; border-top: 1px solid var(--line); padding-block: 24px 32px; }
    .lx-footer-links { display: flex; flex-wrap: wrap; gap: 8px 20px; }
    .lx-footer-links a { font-size: 14px; color: #C4C4C4; text-decoration: none; }
    .lx-footer-links a:hover { color: #ffffff; text-decoration: underline; }
    .lx-footer-note { flex-basis: 100%; font-size: 12px; color: #8F8F8F; }
  </style>
</head>
<body>

  <header class="lx-header">
    <div class="lx-wrap lx-header-row">
      <a href="#topo" class="lx-logo" aria-label="Voltar ao início">
        <span class="lx-logo-a" data-block-id="blk_logo_1" data-block-type="text">Sua</span>
        <span class="lx-logo-b" data-block-id="blk_logo_2" data-block-type="text">Marca</span>
      </a>
      <nav class="lx-nav" aria-label="Principal">
        <a href="#como" class="lx-nav-link" data-block-id="blk_nav_como" data-block-type="link">Como participar</a>
        <a href="#duvidas" class="lx-nav-link" data-block-id="blk_nav_duvidas" data-block-type="link">Dúvidas</a>
        <a href="https://example.com/sua-campanha" class="lx-btn lx-btn-outline js-buy" data-block-id="blk_btn_nav" data-block-type="button" data-dest-type="external">Participar</a>
      </nav>
    </div>
  </header>

  <main>
    <section id="topo" class="lx-hero" data-block-id="blk_hero_bg" data-block-type="image">
      <img class="lx-hero-img" src="https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=1920&auto=format&fit=crop&q=80" alt="Ford Ranger 0km, prêmio da campanha" fetchpriority="high">
      <div class="lx-hero-shade" aria-hidden="true"></div>
      <div class="lx-hero-content">
        <p class="lx-eyebrow" data-block-id="blk_eyebrow" data-block-type="text">Campanha em destaque</p>
        <h1 class="lx-title">
          <span class="lx-title-1" data-block-id="blk_title_1" data-block-type="heading">Ford Ranger</span>
          <span class="lx-title-2" data-block-id="blk_title_2" data-block-type="heading">2026 · Zero KM</span>
        </h1>
        <p class="lx-price">
          <span data-block-id="blk_price_label" data-block-type="text">Cota a partir de</span>
          <strong data-block-id="blk_price" data-block-type="text">R$ 0,00</strong>
        </p>
        <div class="lx-hero-actions">
          <a href="https://example.com/sua-campanha" class="lx-btn lx-btn-lg lx-btn-arrow js-buy" data-block-id="blk_btn_1" data-block-type="button" data-dest-type="external">Quero participar</a>
          <a href="#" class="lx-small-link" data-block-id="blk_hero_rules" data-block-type="link">Consulte as condições e o regulamento</a>
        </div>
      </div>
    </section>

    <section id="como" class="lx-section" data-block-id="blk_steps" data-block-type="container">
      <div class="lx-wrap">
        <h2 class="lx-h2" data-block-id="blk_steps_title" data-block-type="heading">Participar é simples.</h2>
        <ol class="lx-steps">
          <li class="lx-step" data-block-id="blk_step1" data-block-type="container">
            <span class="lx-step-num" aria-hidden="true" data-block-id="blk_step1_num" data-block-type="text">01</span>
            <div class="lx-step-body">
              <svg class="lx-step-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v3a2 2 0 0 0 0 4v3H3v-3a2 2 0 0 0 0-4z"></path><path d="M15 7v10" stroke-dasharray="2 2"></path></svg>
              <h3 data-block-id="blk_step1_title" data-block-type="heading">Escolha suas cotas</h3>
              <p data-block-id="blk_step1_text" data-block-type="text">Selecione a quantidade desejada.</p>
            </div>
          </li>
          <li class="lx-step" data-block-id="blk_step2" data-block-type="container">
            <span class="lx-step-num" aria-hidden="true" data-block-id="blk_step2_num" data-block-type="text">02</span>
            <div class="lx-step-body">
              <svg class="lx-step-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><path d="M14 14h3v3h-3z"></path><path d="M21 14v7h-4"></path></svg>
              <h3 data-block-id="blk_step2_title" data-block-type="heading">Pague via Pix</h3>
              <p data-block-id="blk_step2_text" data-block-type="text">Siga as instruções do checkout.</p>
            </div>
          </li>
          <li class="lx-step" data-block-id="blk_step3" data-block-type="container">
            <span class="lx-step-num" aria-hidden="true" data-block-id="blk_step3_num" data-block-type="text">03</span>
            <div class="lx-step-body">
              <svg class="lx-step-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path><path d="M8 3v4"></path><path d="M16 3v4"></path></svg>
              <h3 data-block-id="blk_step3_title" data-block-type="heading">Acompanhe a campanha</h3>
              <p data-block-id="blk_step3_text" data-block-type="text">Confira datas e resultados oficiais.</p>
            </div>
          </li>
        </ol>
      </div>
    </section>

    <section class="lx-vip-section" aria-label="Comunidade VIP" data-block-id="blk_vip" data-block-type="container">
      <div class="lx-wrap">
        <div class="lx-vip">
          <svg class="lx-vip-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.6L3 20.5l1.4-5.3A8.5 8.5 0 1 1 21 11.5z"></path><path d="M8.5 11.5h.01"></path><path d="M12 11.5h.01"></path><path d="M15.5 11.5h.01"></path></svg>
          <div class="lx-vip-text">
            <h2 class="lx-h2 lx-vip-title">
              <span data-block-id="blk_vip_title_1" data-block-type="heading">Fique por dentro.</span>
              <span class="lx-accent" data-block-id="blk_vip_title_2" data-block-type="heading">Entre na comunidade VIP.</span>
            </h2>
            <p data-block-id="blk_vip_text" data-block-type="text">Receba novidades e acompanhe as atualizações da campanha pelo WhatsApp.</p>
          </div>
          <a href="https://chat.whatsapp.com/" class="lx-btn" target="_blank" rel="noopener noreferrer" data-block-id="blk_vip_link" data-block-type="link">Entrar na comunidade</a>
        </div>
      </div>
    </section>

    <section id="duvidas" class="lx-section" data-block-id="blk_faq" data-block-type="container">
      <div class="lx-wrap">
        <h2 class="lx-h2" data-block-id="blk_faq_title" data-block-type="heading">Dúvidas frequentes</h2>
        <div class="lx-faq">
          <div class="lx-faq-item" data-block-id="blk_faq_1" data-block-type="container">
            <h3 data-block-id="blk_faq_1_q" data-block-type="heading">Como participar da campanha?</h3>
            <p data-block-id="blk_faq_1_a" data-block-type="text">Escolha a quantidade de cotas, pague via Pix e acompanhe as atualizações até o resultado.</p>
          </div>
          <div class="lx-faq-item" data-block-id="blk_faq_2" data-block-type="container">
            <h3 data-block-id="blk_faq_2_q" data-block-type="heading">Onde acompanho o resultado?</h3>
            <p data-block-id="blk_faq_2_a" data-block-type="text">[Informe onde e quando o resultado será divulgado.]</p>
          </div>
          <div class="lx-faq-item" data-block-id="blk_faq_3" data-block-type="container">
            <h3 data-block-id="blk_faq_3_q" data-block-type="heading">Como consulto minhas cotas?</h3>
            <p data-block-id="blk_faq_3_a" data-block-type="text">[Explique como o participante consulta as cotas compradas.]</p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="lx-footer">
    <div class="lx-wrap">
      <div class="lx-footer-cta">
        <div>
          <h2 class="lx-h2" data-block-id="blk_footer_title" data-block-type="heading">Pronto para participar?</h2>
          <p data-block-id="blk_footer_text" data-block-type="text">Garanta já a sua chance de levar a Ford Ranger 2026.</p>
        </div>
        <a href="https://example.com/sua-campanha" class="lx-btn lx-btn-lg lx-btn-arrow js-buy" data-block-id="blk_btn_footer" data-block-type="button" data-dest-type="external">Quero participar</a>
      </div>
      <div class="lx-footer-bottom">
        <span class="lx-logo">
          <span class="lx-logo-a" data-block-id="blk_footer_logo_1" data-block-type="text">Sua</span>
          <span class="lx-logo-b" data-block-id="blk_footer_logo_2" data-block-type="text">Marca</span>
        </span>
        <nav class="lx-footer-links" aria-label="Rodapé">
          <a href="#" data-block-id="blk_link_rules" data-block-type="link">Regulamento</a>
          <a href="#" data-block-id="blk_link_privacy" data-block-type="link">Privacidade</a>
          <a href="#" data-block-id="blk_link_contact" data-block-type="link">Contato</a>
        </nav>
        <p class="lx-footer-note" data-block-id="blk_footer_note" data-block-type="text">Imagens ilustrativas.</p>
      </div>
    </div>
  </footer>

</body>
</html>`;
  }

  /**
   * Lê a aparência de uma página criada com este modelo.
   * Retorna null quando a página não tem <style id="lx-theme"> (outros modelos).
   */
  function readTheme(html) {
    if (!html || html.indexOf('lx-theme') === -1) return null;
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var style = doc.getElementById('lx-theme');
    if (!style) return null;
    var vars = parseThemeVars(style.textContent);
    var font = doc.documentElement.getAttribute('data-lx-font');
    var shade = parseFloat(vars['hero-shade']);
    return {
      font: VARIANTS[font] ? font : 'impacto',
      accent: /^#[0-9a-f]{6}$/i.test(vars.accent || '') ? vars.accent.toUpperCase() : DEFAULT_THEME.accent,
      shade: isNaN(shade) ? 62 : Math.round(shade * 100)
    };
  }

  /**
   * Aplica uma mudança de aparência e devolve o HTML novo (ou null se nada mudou).
   * campo: 'font' (id de VARIANTS), 'accent' (#RRGGBB) ou 'shade' (0–90).
   */
  function applyTheme(html, campo, valor) {
    if (!html || html.indexOf('lx-theme') === -1) return null;
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var style = doc.getElementById('lx-theme');
    if (!style) return null;
    var vars = parseThemeVars(style.textContent);

    if (campo === 'accent') {
      var hex = String(valor || '').trim();
      if (!/^#[0-9a-f]{6}$/i.test(hex)) return null;
      vars.accent = hex.toUpperCase();
      vars['accent-ink'] = inkFor(hex);
    } else if (campo === 'shade') {
      var pct = Math.max(0, Math.min(90, parseInt(valor, 10)));
      if (isNaN(pct)) return null;
      vars['hero-shade'] = String(pct / 100);
    } else if (campo === 'font') {
      var variant = VARIANTS[valor];
      if (!variant) return null;
      Object.keys(variant.vars).forEach(function (k) { vars[k] = variant.vars[k]; });
      var link = doc.getElementById('lx-fonts');
      if (link) link.setAttribute('href', variant.fontsUrl);
      doc.documentElement.setAttribute('data-lx-font', valor);
    } else {
      return null;
    }

    style.textContent = themeCss(vars);
    var out = '<!DOCTYPE html>\n' + doc.documentElement.outerHTML;
    return out === html ? null : out;
  }

  window.LandingEnxuta = {
    ID: 'landing-enxuta',
    VARIANTS: VARIANTS,
    getHtml: getHtml,
    readTheme: readTheme,
    applyTheme: applyTheme
  };
})(window);
