/**
 * ══════════════════════════════════════════════════════════════════
 * PRESSEL PREMIUM — CAMPANHAS (ENGINE & EDITOR VISUAL)
 * Suporte a edição estruturada, re-renderização em tempo real,
 * upload de imagens/vídeos, ordenação dinâmica e persistência.
 * ══════════════════════════════════════════════════════════════════
 */

(function (window, document) {
  'use strict';

  var PresselPremium = {
    SLUG: 'pressel-premium',
    NAME: 'Pressel Premium — Campanhas',

    getDefaultConfig: function () {
      return {
        settings: {
          theme: {
            bg: '#0b0e12',
            surface: '#141922',
            line: '#242c38',
            fg: '#eef1f5',
            muted: '#a1adbd',
            accent: '#f5a524',
            accent_ink: '#1a1206',
            pix: '#32bcad',
            font_title: 'Barlow Condensed',
            font_body: 'Barlow',
            content_width: '560px',
            hero_width: '720px'
          },
          seo: {
            title: 'Ranger V6 Brayam Braz',
            description: 'Concorra a uma Ranger XLT V6 0km com cotas de R$ 0,35. Títulos premiados com iPhone 18 Pro Max e Pix de R$ 5.000 e R$ 1.000.',
            og_image: 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=1200&auto=format&fit=crop&q=80',
            og_image_alt: 'Ford Ranger prata 0km estacionada ao pôr do sol'
          },
          tracking: {
            meta_pixel_id: '',
            cta_track_event: 'InitiateCheckout'
          }
        },
        blocks: {
          top: {
            enabled: true,
            organizer_name: 'Brayam Braz',
            instagram_handle: '@brayambraz',
            instagram_url: 'https://www.instagram.com/brayambraz',
            avatar_initials: 'BB',
            avatar_image: '',
            status_text: 'Ação no ar',
            show_instagram: true,
            show_status: true
          },
          hero: {
            enabled: true,
            image_desktop: 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=1200&auto=format&fit=crop&q=80',
            image_mobile: '',
            image_alt: 'Ford Ranger prata 0km estacionada ao pôr do sol',
            object_position_desktop: '50% 60%',
            object_position_mobile: '50% 50%',
            gradient_intensity: 100,
            eyebrow: 'Ação Brayam Braz',
            title: 'Ranger XLT',
            title_highlight: 'V6 0km',
            cash_alternative: 'ou <b>R$ 250 mil</b> no Pix',
            price_label: 'Cota por apenas',
            currency_symbol: 'R$',
            price_value: '0,35',
            cta_text: 'Quero participar',
            cta_url: 'https://brayambraz.com.br/campanha/ranger-v6-0km-ou-250k',
            cta_target_blank: false,
            note: 'Escolha suas cotas e pague no Pix na página oficial da ação.'
          },
          marquee: {
            enabled: true,
            speed_seconds: 28,
            bg_color: '#f5a524',
            text_color: '#1a1206',
            items: [
              'Ranger XLT V6 0km',
              'iPhone 18 Pro Max',
              'Pix de R$ 5.000',
              'Pix de R$ 1.000',
              'Cota R$ 0,35'
            ]
          },
          prizes: {
            enabled: true,
            eyebrow: 'Títulos premiados',
            title: 'Tem prêmio antes do sorteio',
            description: 'Além da Ranger, a ação tem números premiados. Se um deles cair nas suas cotas, o prêmio é seu.',
            items: [
              { tag: 'iPhone', tag_class: 'ip', title: 'iPhone 18 Pro Max', description: 'Lançamento da Apple', image: '' },
              { tag: 'Pix', tag_class: 'px', title: 'R$ 5.000 no Pix', description: 'Direto na sua conta', image: '' },
              { tag: 'Pix', tag_class: 'px', title: 'R$ 1.000 no Pix', description: 'Direto na sua conta', image: '' }
            ]
          },
          deliveries: {
            enabled: true,
            eyebrow: 'Seja um dos nossos ganhadores',
            title: 'Quem ganha, aparece',
            description: 'Entregas reais de ações anteriores. Dá o play e confere.',
            show_cta: true,
            cta_text: 'O próximo pode ser você',
            cta_url: '',
            items: [
              {
                video_url: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                poster_url: 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=600&auto=format&fit=crop&q=80',
                title: 'Caminhonete na prancha',
                subtitle: 'Entrega feita até a porta do ganhador',
                badge: 'Entregue'
              },
              {
                video_url: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                poster_url: 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600&auto=format&fit=crop&q=80',
                title: 'Carro entregue',
                subtitle: 'Ganhador recebendo a chave',
                badge: 'Entregue'
              },
              {
                video_url: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
                poster_url: 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=600&auto=format&fit=crop&q=80',
                title: 'PS5 entregue',
                subtitle: 'Perobal/PR',
                badge: 'Entregue'
              }
            ]
          },
          steps: {
            enabled: true,
            eyebrow: 'Leva menos de 1 minuto',
            title: 'Como participar',
            show_cta: true,
            cta_text: 'Garantir minhas cotas',
            cta_url: '',
            items: [
              { title: 'Toque em "Quero participar"', description: 'Você vai para a página oficial da ação.' },
              { title: 'Escolha quantas cotas quer', description: 'Cada cota custa R$ 0,35. Quanto mais cotas, mais chances.' },
              { title: 'Pague no Pix', description: 'Pagamento confirmado, seus números saem na hora.' }
            ]
          },
          channels: {
            enabled: true,
            eyebrow: 'Canais oficiais',
            title: 'Acompanhe de perto',
            description: 'Resultados, entregas e novas ações saem primeiro na comunidade.',
            items: [
              { type: 'whatsapp', title: 'Comunidade no WhatsApp', subtitle: 'Entre e receba os avisos', url: 'https://braz.digital/cWQG' },
              { type: 'instagram', title: 'Instagram @brayambraz', subtitle: 'Bastidores e entregas', url: 'https://www.instagram.com/brayambraz' }
            ]
          },
          faq: {
            enabled: true,
            eyebrow: 'Dúvidas',
            title: 'Perguntas frequentes',
            items: [
              { question: 'Quanto custa cada cota?', answer: 'Cada cota custa R$ 0,35. Você escolhe a quantidade na página oficial da ação.' },
              { question: 'Como faço o pagamento?', answer: 'O pagamento é feito no Pix, direto na página oficial. Assim que ele é confirmado, seus números aparecem.' },
              { question: 'O que são os títulos premiados?', answer: 'São números sorteados com antecedência que valem prêmios: iPhone 18 Pro Max, Pix de R$ 5.000 e Pix de R$ 1.000. Se um deles estiver entre as suas cotas, você ganha o prêmio.' },
              { question: 'Onde vejo a data do sorteio e o regulamento?', answer: 'Data, regulamento e todas as condições ficam na página oficial da ação, no botão "Quero participar".' },
              { question: 'Como sei que a entrega é real?', answer: 'Todas as entregas são gravadas e publicadas no Instagram @brayambraz. Você pode conferir algumas delas nos vídeos acima.' }
            ]
          },
          footer: {
            enabled: true,
            responsible_name: 'Brayam Braz',
            sub_text: 'página oficial da ação',
            disclaimer: 'Regras, datas, pagamento e definição dos contemplados são informados na plataforma da ação. Leia o regulamento antes de participar. Proibido para menores de 18 anos.',
            external_disclaimer: 'Este site não é afiliado, associado ou endossado pelo Facebook ou pela Meta Platforms.'
          },
          sticky_cta: {
            enabled: true,
            label: 'Cota por',
            price: 'R$ 0,35',
            button_text: 'Quero participar',
            button_url: ''
          }
        }
      };
    },

    escapeHtml: function (str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    },

    sanitizeUrl: function (url) {
      if (!url) return '';
      url = String(url).trim();
      if (/^(https?:\/\/|\/|#)/i.test(url)) return url;
      return '#' + url.replace(/[^a-zA-Z0-9_-]/g, '');
    },

    /**
     * Renderizador do HTML completo no cliente (Preview e Publicação)
     */
    renderHtml: function (blocks, settings, options) {
      options = options || {};
      var s = settings || {};
      var b = blocks || {};
      var t = s.theme || {};
      var seo = s.seo || {};
      var trk = s.tracking || {};

      var bg = t.bg || '#0b0e12';
      var surface = t.surface || '#141922';
      var line = t.line || '#242c38';
      var fg = t.fg || '#eef1f5';
      var muted = t.muted || '#a1adbd';
      var accent = t.accent || '#f5a524';
      var accentInk = t.accent_ink || '#1a1206';
      var pix = t.pix || '#32bcad';
      var contentWidth = t.content_width || '560px';
      var heroWidth = t.hero_width || '720px';

      var top = b.top || {};
      var hero = b.hero || {};
      var marquee = b.marquee || {};
      var prizes = b.prizes || {};
      var deliveries = b.deliveries || {};
      var steps = b.steps || {};
      var channels = b.channels || {};
      var faq = b.faq || {};
      var footer = b.footer || {};
      var sticky = b.sticky_cta || {};

      var heroCtaUrl = this.sanitizeUrl(hero.cta_url || '#');
      var deliveriesCtaUrl = deliveries.cta_url ? this.sanitizeUrl(deliveries.cta_url) : heroCtaUrl;
      var stepsCtaUrl = steps.cta_url ? this.sanitizeUrl(steps.cta_url) : heroCtaUrl;
      var stickyCtaUrl = sticky.button_url ? this.sanitizeUrl(sticky.button_url) : heroCtaUrl;

      var pixelId = (trk.meta_pixel_id || '').trim();
      var pixelEvent = trk.cta_track_event === 'Click' ? 'Click' : 'InitiateCheckout';

      var esc = this.escapeHtml;
      var self = this;

      var pixelSnippet = (pixelId && !options.is_editor) ? `
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
  fbq('init', '${esc(pixelId)}');
  fbq('track', 'PageView');
  <\/script>
  <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=${encodeURIComponent(pixelId)}&ev=PageView&noscript=1"/></noscript>
  <!-- End Meta Pixel Code -->` : '';

      // Seção Topo
      var topHtml = top.enabled ? `
<header class="top" data-block-id="blk_top" data-block-type="top">
  <div class="wrap">
    ${top.show_instagram ? `
    <a class="ig" href="${esc(self.sanitizeUrl(top.instagram_url || 'https://instagram.com'))}" target="_blank" rel="noopener noreferrer">
      <span class="av" aria-hidden="true">${top.avatar_image ? `<img src="${esc(self.sanitizeUrl(top.avatar_image))}" alt="${esc(top.organizer_name)}">` : esc(top.avatar_initials || 'BB')}</span>
      ${esc(top.instagram_handle || '@instagram')}
      <span class="sr-only"> (abre em nova aba)</span>
    </a>` : ''}
    ${top.show_status ? `
    <span class="live"><span class="live-dot" aria-hidden="true"></span>${esc(top.status_text || 'Ação no ar')}</span>` : ''}
  </div>
</header>` : '';

      // Seção Hero
      var heroHtml = hero.enabled ? `
  <div class="hero" data-block-id="blk_hero" data-block-type="hero">
    <div class="hero-img">
      <img src="${esc(self.sanitizeUrl(hero.image_desktop || ''))}" alt="${esc(hero.image_alt || '')}" width="1116" height="1114" fetchpriority="high">
    </div>
    <div class="wrap hero-copy">
      ${hero.eyebrow ? `<span class="eyebrow">${esc(hero.eyebrow)}</span>` : ''}
      <h1>${esc(hero.title || '')}${hero.title_highlight ? ` <em>${esc(hero.title_highlight)}</em>` : ''}</h1>
      ${hero.cash_alternative ? `<div class="or">${hero.cash_alternative}</div>` : ''}
      <div class="price">
        <small>${esc(hero.price_label || 'Cota por apenas')}</small>
        <strong><span>${esc(hero.currency_symbol || 'R$')}</span>${esc(hero.price_value || '0,35')}</strong>
      </div>
      <a class="btn js-buy" href="${heroCtaUrl}" ${hero.cta_target_blank ? 'target="_blank" rel="noopener noreferrer"' : ''}>
        ${esc(hero.cta_text || 'Quero participar')}
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
      </a>
      ${hero.note ? `<p class="note">${esc(hero.note)}</p>` : ''}
    </div>
  </div>` : '';

      // Seção Faixa Animada (Marquee)
      var marqueeItemsHtml = (marquee.items || []).map(function (it) {
        return `<span>${esc(it)}</span><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5Z"/></svg>`;
      }).join('');

      var marqueeHtml = (marquee.enabled && marquee.items && marquee.items.length) ? `
  <div class="marquee" id="prize-marquee" aria-hidden="true" data-block-id="blk_marquee" data-block-type="marquee">
    <div class="marquee-track">
      <div class="marquee-group">${marqueeItemsHtml}</div>
      <div class="marquee-group">${marqueeItemsHtml}</div>
    </div>
  </div>
  <button id="motion-control" class="motion-control" type="button" aria-controls="prize-marquee" aria-pressed="false" hidden>Pausar animação da faixa</button>` : '';

      // Seção Prêmios
      var prizesListHtml = (prizes.items || []).map(function (p) {
        return `
        <article class="prize">
          <span class="tag ${esc(p.tag_class || 'px')}">${esc(p.tag || 'Pix')}</span>
          <div>
            <h3>${esc(p.title || '')}</h3>
            <p>${esc(p.description || '')}</p>
          </div>
        </article>`;
      }).join('');

      var prizesHtml = (prizes.enabled && prizes.items && prizes.items.length) ? `
  <section aria-labelledby="premios-titulo" data-block-id="blk_prizes" data-block-type="prizes">
    <div class="wrap">
      <div class="sec-head">
        ${prizes.eyebrow ? `<span class="eyebrow">${esc(prizes.eyebrow)}</span>` : ''}
        <h2 id="premios-titulo">${esc(prizes.title || 'Títulos premiados')}</h2>
        ${prizes.description ? `<p>${esc(prizes.description)}</p>` : ''}
      </div>
      <div class="prizes">${prizesListHtml}</div>
    </div>
  </section>` : '';

      // Seção Entregas
      var deliveriesListHtml = (deliveries.items || []).map(function (del, idx) {
        var vidId = idx + 1;
        return `
        <figure class="clip">
          <div class="vid">
            ${del.badge ? `<span class="badge">${esc(del.badge)}</span>` : ''}
            <video src="${esc(self.sanitizeUrl(del.video_url || ''))}" poster="${esc(self.sanitizeUrl(del.poster_url || ''))}" controls preload="none" playsinline aria-label="Vídeo: ${esc(del.title || '')}" aria-describedby="video-status-${vidId}">
              Seu navegador não reproduz este vídeo. <a href="${esc(self.sanitizeUrl(del.video_url || ''))}">Abrir vídeo</a>.
            </video>
            <button class="play" type="button" hidden aria-label="Assistir ${esc(del.title || 'entrega')}">
              <span>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m8 5 11 7-11 7Z" fill="currentColor" stroke="none"/></svg>
              </span>
            </button>
          </div>
          <figcaption>
            <b>${esc(del.title || '')}</b>
            ${del.subtitle ? `<small>${esc(del.subtitle)}</small>` : ''}
          </figcaption>
          <p class="video-status" id="video-status-${vidId}" role="status" aria-live="polite"></p>
        </figure>`;
      }).join('');

      var deliveriesHtml = (deliveries.enabled && deliveries.items && deliveries.items.length) ? `
  <section id="entregas" aria-labelledby="entregas-titulo" data-block-id="blk_deliveries" data-block-type="deliveries">
    <div class="wrap">
      <div class="sec-head">
        ${deliveries.eyebrow ? `<span class="eyebrow">${esc(deliveries.eyebrow)}</span>` : ''}
        <h2 id="entregas-titulo">${esc(deliveries.title || 'Quem ganha, aparece')}</h2>
        ${deliveries.description ? `<p>${esc(deliveries.description)}</p>` : ''}
      </div>
      <div class="reel" tabindex="0" role="region" aria-label="Vídeos de entregas anteriores">
        ${deliveriesListHtml}
      </div>
      <p class="hint">Arraste para o lado para ver mais entregas <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></p>
      ${deliveries.show_cta ? `
      <div class="section-cta">
        <a class="btn js-buy" href="${deliveriesCtaUrl}">
          ${esc(deliveries.cta_text || 'O próximo pode ser você')}
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
        </a>
      </div>` : ''}
    </div>
  </section>` : '';

      // Seção Como Participar (Steps)
      var stepsListHtml = (steps.items || []).map(function (step) {
        return `
        <li>
          <div>
            <strong>${esc(step.title || '')}</strong>
            <span>${esc(step.description || '')}</span>
          </div>
        </li>`;
      }).join('');

      var stepsHtml = (steps.enabled && steps.items && steps.items.length) ? `
  <section aria-labelledby="passos-titulo" data-block-id="blk_steps" data-block-type="steps">
    <div class="wrap">
      <div class="sec-head">
        ${steps.eyebrow ? `<span class="eyebrow">${esc(steps.eyebrow)}</span>` : ''}
        <h2 id="passos-titulo">${esc(steps.title || 'Como participar')}</h2>
      </div>
      <ol class="steps">${stepsListHtml}</ol>
      ${steps.show_cta ? `
      <a class="btn js-buy" href="${stepsCtaUrl}">
        ${esc(steps.cta_text || 'Garantir minhas cotas')}
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
      </a>` : ''}
    </div>
  </section>` : '';

      // Seção Canais
      var channelsListHtml = (channels.items || []).map(function (ch) {
        var isWa = (ch.type === 'whatsapp');
        return `
        <a class="ghost" href="${esc(self.sanitizeUrl(ch.url || '#'))}" target="_blank" rel="noopener noreferrer">
          <span class="ico ${isWa ? 'wa' : 'igc'}">
            ${isWa ? `
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 20.5l1.4-4.8a8.5 8.5 0 1 1 16.1-4.1Z"/><path d="M8.5 7.5c0 4.5 3.5 8 8 8l.7-2-2.8-1.4-1.1 1.1a7 7 0 0 1-2.6-2.6l1.1-1.1-1.4-2.8Z"/></svg>` : `
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>`}
          </span>
          <span class="channel-copy">
            <b>${esc(ch.title || '')}</b>
            <small>${esc(ch.subtitle || '')}</small>
            <span class="sr-only"> (abre em nova aba)</span>
          </span>
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>
        </a>`;
      }).join('');

      var channelsHtml = (channels.enabled && channels.items && channels.items.length) ? `
  <section aria-labelledby="canais-titulo" data-block-id="blk_channels" data-block-type="channels">
    <div class="wrap">
      <div class="sec-head">
        ${channels.eyebrow ? `<span class="eyebrow">${esc(channels.eyebrow)}</span>` : ''}
        <h2 id="canais-titulo">${esc(channels.title || 'Canais oficiais')}</h2>
        ${channels.description ? `<p>${esc(channels.description)}</p>` : ''}
      </div>
      <div class="channels">${channelsListHtml}</div>
    </div>
  </section>` : '';

      // Seção FAQ
      var faqListHtml = (faq.items || []).map(function (item) {
        return `
      <details>
        <summary>
          ${esc(item.question || '')}
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M12 5v14"/></svg>
        </summary>
        <p>${esc(item.answer || '')}</p>
      </details>`;
      }).join('');

      var faqHtml = (faq.enabled && faq.items && faq.items.length) ? `
  <section aria-labelledby="faq-titulo" data-block-id="blk_faq" data-block-type="faq">
    <div class="wrap">
      <div class="sec-head">
        ${faq.eyebrow ? `<span class="eyebrow">${esc(faq.eyebrow)}</span>` : ''}
        <h2 id="faq-titulo">${esc(faq.title || 'Perguntas frequentes')}</h2>
      </div>
      ${faqListHtml}
    </div>
  </section>` : '';

      // Seção Rodapé
      var footerHtml = footer.enabled ? `
<div class="wrap" data-block-id="blk_footer" data-block-type="footer">
  <footer>
    <p><strong>${esc(footer.responsible_name || '')}</strong> | ${esc(footer.sub_text || 'página oficial da ação')}</p>
    ${footer.disclaimer ? `<p>${esc(footer.disclaimer)}</p>` : ''}
    ${footer.external_disclaimer ? `<p>${esc(footer.external_disclaimer)}</p>` : ''}
  </footer>
</div>` : '';

      // Seção Sticky CTA
      var stickyHtml = sticky.enabled ? `
<aside class="sticky" id="sticky" aria-label="Participar da ação" aria-hidden="true" inert data-block-id="blk_sticky" data-block-type="sticky_cta">
  <div class="wrap">
    <div class="lbl">
      <small>${esc(sticky.label || 'Cota por')}</small>
      <b>${esc(sticky.price || 'R$ 0,35')}</b>
    </div>
    <a class="btn js-buy" href="${stickyCtaUrl}" tabindex="-1">
      ${esc(sticky.button_text || 'Quero participar')}
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </a>
  </div>
</aside>` : '';

      return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>${esc(seo.title || 'Campanha Exclusiva')}</title>
  <meta name="description" content="${esc(seo.description || '')}">
  <meta name="theme-color" content="${bg}">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:title" content="${esc(seo.title || '')}">
  <meta property="og:description" content="${esc(seo.description || '')}">
  ${seo.og_image ? `<meta property="og:image" content="${esc(self.sanitizeUrl(seo.og_image))}">${seo.og_image_alt ? `<meta property="og:image:alt" content="${esc(seo.og_image_alt)}">` : ''}` : ''}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,600;0,800;0,900;1,900&family=Barlow:wght@400;500;600&display=swap">
  ${pixelSnippet}
  <style>
:root {
  color-scheme: dark;
  --bg: ${bg};
  --surface: ${surface};
  --line: ${line};
  --fg: ${fg};
  --muted: ${muted};
  --accent: ${accent};
  --accent-ink: ${accentInk};
  --pix: ${pix};
  --display: "Barlow Condensed", "Arial Narrow", Impact, sans-serif;
  --body: "Barlow", system-ui, -apple-system, "Segoe UI", sans-serif;
  --sticky-space: 100px;
}
*, *::before, *::after { box-sizing: border-box; }
[hidden] { display: none !important; }
html { scroll-behavior: smooth; scroll-padding-bottom: calc(var(--sticky-space) + 20px); }
body { margin: 0; background: var(--bg); color: var(--fg); font: 400 1rem/1.5 var(--body); -webkit-font-smoothing: antialiased; }
body.has-sticky { padding-bottom: var(--sticky-space); }
a { color: inherit; }
button { font: inherit; }
img, video { display: block; max-width: 100%; }
button, a, summary { -webkit-tap-highlight-color: transparent; touch-action: manipulation; }
a, button, summary { scroll-margin-block: 24px calc(var(--sticky-space) + 24px); }
:focus-visible { outline: 3px solid var(--fg); outline-offset: 4px; }
.wrap { width: 100%; max-width: ${contentWidth}; margin-inline: auto; padding-inline: 16px; }
h1, h2, h3 { margin: 0; font-family: var(--display); line-height: 1; text-transform: uppercase; text-wrap: balance; }
.eyebrow { color: var(--accent); font: 600 .85rem/1.4 var(--display); letter-spacing: .14em; text-transform: uppercase; }
section { padding-block: 56px; }
section + section { border-top: 1px solid var(--line); }
.icon { display: block; flex: 0 0 auto; width: 24px; height: 24px; }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; border: 0; }
.skip-link { position: fixed; left: 16px; top: 16px; z-index: 100; padding: 12px; background: var(--fg); color: var(--bg); transform: translateY(-200%); }
.skip-link:focus { transform: none; }
.top { position: absolute; top: env(safe-area-inset-top, 0px); inset-inline: 0; z-index: 5; }
.top .wrap { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px; padding-block: 14px; }
.ig, .live { display: inline-flex; align-items: center; gap: 7px; border: 1px solid #ffffff24; border-radius: 999px; background: #0b0e12d9; }
.ig { min-height: 44px; padding: 6px 12px 6px 6px; text-decoration: none; font-size: .875rem; font-weight: 600; }
.av { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 50%; color: var(--accent-ink); background: var(--accent); font: 900 .9rem var(--display); overflow: hidden; }
.av img { width: 100%; height: 100%; object-fit: cover; }
.live { padding: 7px 10px; font: 800 .8rem var(--display); letter-spacing: .08em; text-transform: uppercase; }
.live-dot { width: 8px; height: 8px; border-radius: 50%; background: #ff4d4d; animation: pulse 1.6s infinite; }
@keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 #ff4d4d00; } 40% { box-shadow: 0 0 0 0 #ff4d4d99; } 80% { box-shadow: 0 0 0 8px #ff4d4d00; } }
.hero { position: relative; max-width: ${heroWidth}; margin-inline: auto; }
.hero-img { position: relative; aspect-ratio: 1; overflow: hidden; background: var(--surface); }
.hero-img img { width: 100%; height: 100%; object-fit: cover; object-position: ${esc(hero.object_position_desktop || '50% 60%')}; }
.hero-img::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg,#0b0e1259 0%,transparent 22%,transparent 55%,var(--bg) 100%); pointer-events: none; }
.hero-copy { position: relative; z-index: 2; display: grid; gap: 14px; margin-top: -100px; }
.hero h1 { font-size: clamp(3.1rem,15vw,5.6rem); font-weight: 900; letter-spacing: -.01em; }
.hero h1 em { display: block; color: var(--accent); font-style: normal; }
.or { font: 800 1.45rem/1.2 var(--display); text-transform: uppercase; }
.or b { color: var(--pix); }
.price { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; padding-block: 6px; }
.price small { font-size: 1rem; color: var(--muted); }
.price strong { font: 900 3.4rem/1 var(--display); }
.price strong span { margin-right: 3px; font-size: 1.6rem; }
.btn { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; min-height: 48px; padding: 18px 20px; border-radius: 14px; color: var(--accent-ink); background: var(--accent); text-align: center; text-decoration: none; font: 900 1.5rem/1.1 var(--display); letter-spacing: .03em; text-transform: uppercase; box-shadow: 0 10px 30px -10px #f5a52499; transition: transform .15s, filter .15s; border: none; cursor: pointer; }
.btn:active { transform: scale(.98); }
@media (hover: hover) { .btn:hover { filter: brightness(1.08); } }
.note { margin: 0; color: var(--muted); font-size: .9rem; text-align: center; }
.marquee { margin-top: 40px; overflow: hidden; color: ${esc(marquee.text_color || '#1a1206')}; background: ${esc(marquee.bg_color || '#f5a524')}; }
.marquee-track { display: flex; width: max-content; animation: roll ${parseInt(marquee.speed_seconds || 28, 10)}s linear infinite; }
.marquee-group { display: flex; flex: 0 0 auto; align-items: center; gap: 28px; padding: 12px 28px 12px 0; white-space: nowrap; font: 800 1.15rem/1.4 var(--display); text-transform: uppercase; }
.marquee .icon { width: 16px; height: 16px; }
@keyframes roll { to { transform: translateX(-50%); } }
.marquee:hover .marquee-track, .marquee.is-paused .marquee-track { animation-play-state: paused; }
.motion-control { display: block; margin: 8px auto 0; padding: 10px 14px; min-height: 44px; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); color: var(--muted); font-size: .8rem; cursor: pointer; }
.sec-head { display: grid; gap: 10px; margin-bottom: 24px; }
.sec-head h2 { font-size: clamp(2.2rem,9vw,3rem); font-weight: 900; }
.sec-head p { margin: 0; color: var(--muted); }
.prizes { display: grid; gap: 10px; }
.prize { display: flex; align-items: center; gap: 16px; padding: 16px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); }
.prize > div { min-width: 0; }
.tag { flex: none; min-width: 68px; padding: 8px; border-radius: 8px; text-align: center; text-transform: uppercase; font: 900 1.05rem var(--display); }
.tag.ip { color: var(--bg); background: var(--fg); }
.tag.px { color: #062522; background: var(--pix); }
.prize h3 { font-size: 1.7rem; font-weight: 800; overflow-wrap: anywhere; }
.prize p { margin: 4px 0 0; color: var(--muted); font-size: .92rem; }
.reel { display: grid; grid-auto-flow: column; grid-auto-columns: 78%; gap: 12px; overflow-x: auto; scroll-snap-type: x mandatory; margin-inline: -16px; padding: 4px 16px 12px; scrollbar-width: thin; scrollbar-color: var(--line) var(--bg); }
.clip { scroll-snap-align: start; min-width: 0; margin: 0; }
.vid { position: relative; aspect-ratio: 9/16; overflow: hidden; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); }
.vid video { width: 100%; height: 100%; object-fit: cover; }
.play { position: absolute; inset: 0; display: grid; place-items: center; border: 0; background: linear-gradient(180deg,transparent 50%,#0000008c); color: var(--bg); cursor: pointer; }
.play:focus-visible { outline-offset: -5px; }
.play span { display: grid; place-items: center; width: 64px; height: 64px; border-radius: 50%; background: #fffffff0; }
.play svg { width: 28px; height: 28px; }
.badge { position: absolute; z-index: 1; left: 10px; top: 10px; padding: 4px 8px; border-radius: 6px; color: var(--accent-ink); background: var(--accent); font: 800 .8rem/1.4 var(--display); letter-spacing: .1em; text-transform: uppercase; pointer-events: none; }
figcaption { margin-top: 10px; }
figcaption b { display: block; font: 800 1.25rem/1.2 var(--display); text-transform: uppercase; }
figcaption small { display: block; margin-top: 4px; color: var(--muted); font-size: .88rem; }
.video-status { margin: 8px 0 0; font-size: .85rem; color: var(--muted); }
.video-status:empty { display: none; }
.hint { display: flex; align-items: center; gap: 8px; font-size: .85rem; color: var(--muted); margin: 6px 0 24px; }
.section-cta { margin-top: 24px; }
.steps { list-style: none; margin: 0 0 28px; padding: 0; counter-reset: step; }
.steps li { counter-increment: step; display: grid; grid-template-columns: 40px 1fr; gap: 14px; padding-block: 14px; border-bottom: 1px dashed var(--line); }
.steps li:last-child { border-bottom: 0; }
.steps li::before { content: counter(step); color: var(--accent); font: 900 2.6rem/1 var(--display); }
.steps strong { display: block; font-size: 1.05rem; }
.steps span { color: var(--muted); font-size: .95rem; }
.channels { display: grid; gap: 10px; }
.ghost { display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); text-decoration: none; }
.ico { display: grid; flex: 0 0 44px; place-items: center; width: 44px; height: 44px; border-radius: 12px; color: #fff; }
.wa { background: #17813f; }
.igc { background: linear-gradient(45deg,#b56100,#c42157 55%,#753097); }
.channel-copy { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.ghost b { display: block; font-size: 1rem; }
.ghost small { color: var(--muted); }
.ghost > .icon { width: 20px; color: var(--muted); }
details { border-bottom: 1px solid var(--line); }
summary { display: flex; justify-content: space-between; align-items: center; gap: 16px; list-style: none; cursor: pointer; padding-block: 18px; font-weight: 600; font-size: 1.05rem; }
summary::-webkit-details-marker { display: none; }
summary .icon { color: var(--accent); transition: transform .2s; }
details[open] summary .icon { transform: rotate(45deg); }
details p { margin: 0 0 18px; color: var(--muted); }
footer { display: grid; gap: 10px; padding-block: 40px 24px; border-top: 1px solid var(--line); color: var(--muted); font-size: .82rem; }
footer p { margin: 0; }
footer strong { color: var(--fg); }
.sticky { position: fixed; inset-inline: 0; bottom: 0; z-index: 20; padding: 10px 0 calc(10px + env(safe-area-inset-bottom,0px)); border-top: 1px solid var(--line); background: #0b0e12f5; transform: translateY(110%); visibility: hidden; pointer-events: none; transition: transform .3s, visibility .3s; }
.sticky.on { visibility: visible; pointer-events: auto; transform: none; }
.sticky .wrap { display: flex; align-items: center; gap: 12px; }
.sticky .lbl { flex: none; line-height: 1.2; }
.sticky small { display: block; color: var(--muted); font-size: .75rem; }
.sticky b { font: 900 1.5rem/1 var(--display); }
.sticky .btn { font-size: 1.2rem; padding: 13px 14px; border-radius: 12px; }
@supports (backdrop-filter: blur(12px)) { .sticky { background: #0b0e12e8; backdrop-filter: blur(12px); } }
@media (min-width: 720px) {
  .hero-copy { margin-top: -120px; }
  .reel { grid-auto-flow: row; grid-template-columns: repeat(3,minmax(0,1fr)); grid-auto-columns: auto; overflow: visible; margin-inline: 0; padding-inline: 0; }
  .hint { display: none; }
}
@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
  *, *::before, *::after { animation: none !important; transition: none !important; }
  .motion-control { display: none; }
  .marquee-track { width: 100%; }
  .marquee-group { flex-wrap: wrap; justify-content: center; gap: 12px; width: 100%; padding: 12px; white-space: normal; }
  .marquee-group + .marquee-group { display: none; }
}
  </style>
</head>
<body class="${sticky.enabled ? 'has-sticky' : ''}">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

${topHtml}

<main id="conteudo" tabindex="-1">
  ${heroHtml}
  ${marqueeHtml}
  ${prizesHtml}
  ${deliveriesHtml}
  ${stepsHtml}
  ${channelsHtml}
  ${faqHtml}
</main>

${footerHtml}
${stickyHtml}

<script>
(() => {
  'use strict';

  // Repasse de parâmetros de campanha (UTMs e fbclid) preservando a navegação nativa
  const incoming = new URLSearchParams(window.location.search);
  document.querySelectorAll('.js-buy').forEach((link) => {
    try {
      const destination = new URL(link.href, window.location.href);
      incoming.forEach((value, key) => {
        if (/^utm_[a-z0-9_]+$/i.test(key) || key === 'fbclid') {
          destination.searchParams.set(key, value);
        }
      });
      link.href = destination.href;
    } catch (error) {
      console.warn('URL da campanha não pôde ser atualizada com UTMs.', error);
    }

    link.addEventListener('click', () => {
      try {
        if (typeof window.fbq === 'function') {
          window.fbq('track', '${pixelEvent}');
        }
      } catch (error) {
        console.warn('Tracking de clique não enviado.', error);
      }
    });
  });

  // Controle de vídeos
  const videos = Array.from(document.querySelectorAll('.vid video'));
  videos.forEach((video) => {
    const frame = video.closest('.vid');
    const button = frame ? frame.querySelector('.play') : null;
    const clip = video.closest('.clip');
    const status = clip ? clip.querySelector('.video-status') : null;
    if (!button) return;
    let pending = false;
    video.controls = false;
    button.hidden = false;

    const reportError = () => {
      if (status) status.textContent = 'Não foi possível carregar o vídeo. Tente novamente.';
      video.controls = false;
      button.hidden = false;
      button.disabled = false;
    };

    button.addEventListener('click', async () => {
      if (pending) return;
      pending = true;
      button.disabled = true;
      if (status) status.textContent = '';
      videos.forEach((other) => { if (other !== video) other.pause(); });
      try {
        if (video.error) video.load();
        video.controls = true;
        await video.play();
        button.hidden = true;
        if (document.activeElement === button) video.focus({ preventScroll: true });
      } catch (error) {
        if (error.name === 'AbortError') {
          button.hidden = false;
        } else {
          reportError();
        }
      } finally {
        pending = false;
        button.disabled = false;
      }
    });

    video.addEventListener('play', () => {
      videos.forEach((other) => { if (other !== video) other.pause(); });
      video.controls = true;
    });
    video.addEventListener('error', reportError);
    video.addEventListener('ended', () => {
      video.controls = true;
    });
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) videos.forEach((video) => video.pause());
  });

  // Controle de movimento da faixa
  const marquee = document.querySelector('.marquee');
  const motionButton = document.getElementById('motion-control');
  if (marquee && motionButton) {
    motionButton.hidden = false;
    motionButton.addEventListener('click', () => {
      const paused = marquee.classList.toggle('is-paused');
      motionButton.setAttribute('aria-pressed', String(paused));
      motionButton.textContent = paused ? 'Retomar animação da faixa' : 'Pausar animação da faixa';
    });
  }

  // CTA fixo sincronizado
  const sticky = document.getElementById('sticky');
  const first = document.querySelector('.hero .js-buy');
  if (sticky && first) {
    const stickyLink = sticky.querySelector('a');
    document.body.classList.add('has-sticky');

    const setSticky = (visible) => {
      sticky.classList.toggle('on', visible);
      sticky.setAttribute('aria-hidden', String(!visible));
      sticky.inert = !visible;
      if (stickyLink) stickyLink.tabIndex = visible ? 0 : -1;
      if (!visible && sticky.contains(document.activeElement)) {
        first.focus({ preventScroll: true });
      }
    };
    const measureSticky = () => {
      document.documentElement.style.setProperty('--sticky-space', Math.ceil(sticky.getBoundingClientRect().height) + 16 + 'px');
    };
    setSticky(false);
    measureSticky();
    if ('ResizeObserver' in window) new ResizeObserver(measureSticky).observe(sticky);
    else window.addEventListener('resize', measureSticky, { passive: true });

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(([entry]) => {
        setSticky(!entry.isIntersecting && entry.boundingClientRect.bottom <= 0);
      });
      observer.observe(first);
    } else {
      let scheduled = false;
      const update = () => {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(() => {
          setSticky(first.getBoundingClientRect().bottom <= 0);
          scheduled = false;
        });
      };
      window.addEventListener('scroll', update, { passive: true });
      window.addEventListener('resize', update, { passive: true });
      update();
    }
  }
})();
<\/script>
</body>
</html>`;
    }
  };

  window.PresselPremium = PresselPremium;
})(window, document);
