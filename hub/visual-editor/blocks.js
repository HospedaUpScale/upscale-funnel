/**
 * Blocos arrastáveis (lateral esquerda do editor).
 * Cada bloco cria uma instância de um `ups-*` component registrado em components.js.
 */
(function (window) {
  'use strict';

  function register(editor) {
    var bm = editor.BlockManager;

    bm.add('ups-section', {
      label: 'Seção', category: 'Estrutura',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><rect x="3" y="4" width="18" height="4" rx="1"/><rect x="3" y="10" width="18" height="4" rx="1"/><rect x="3" y="16" width="18" height="4" rx="1"/></svg>',
      content: { type: 'ups-section', components: [{ type: 'ups-text', content: 'Edite esta seção.' }] },
    });

    bm.add('ups-heading', {
      label: 'Título', category: 'Texto',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><path d="M5 5v14h3V13h8v6h3V5h-3v5H8V5z"/></svg>',
      content: { type: 'ups-heading', content: 'Novo título' },
    });

    bm.add('ups-text', {
      label: 'Texto', category: 'Texto',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><path d="M4 5h16v2H4zM4 10h16v2H4zM4 15h12v2H4z"/></svg>',
      content: { type: 'ups-text', content: 'Digite seu texto aqui.' },
    });

    bm.add('ups-image', {
      label: 'Imagem', category: 'Mídia',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><path d="M21 5H3a2 2 0 00-2 2v10a2 2 0 002 2h18a2 2 0 002-2V7a2 2 0 00-2-2zM5 17l3.5-4.5 2.5 3 3.5-4.5L19 17H5z"/></svg>',
      content: { type: 'ups-image', attributes: { src: '/assets/photo-ops-floor.jpg', alt: '' } },
    });

    bm.add('ups-button', {
      label: 'Botão', category: 'Interação',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><rect x="3" y="8" width="18" height="8" rx="4"/></svg>',
      content: { type: 'ups-button', content: 'Quero participar', attributes: { href: '#' } },
    });

    bm.add('ups-video', {
      label: 'Vídeo', category: 'Mídia',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><path d="M4 4h16v16H4z" opacity=".2"/><path d="M4 4h16v16H4zm6 4v8l6-4z"/></svg>',
      content: { type: 'ups-video', attributes: { 'data-src': 'https://www.youtube.com/embed/dQw4w9WgXcQ' } },
    });

    bm.add('ups-benefits', {
      label: 'Benefícios', category: 'Conteúdo',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><circle cx="6" cy="6" r="2"/><circle cx="12" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="18" cy="12" r="2"/></svg>',
      content: {
        type: 'ups-benefits',
        attributes: {
          'data-items': JSON.stringify([
            { icon: '🎯', title: 'Rápido',     text: 'Processo em 1 clique.' },
            { icon: '🛡️', title: 'Seguro',     text: 'Dados protegidos.' },
            { icon: '⚡',  title: 'Instantâneo', text: 'Entrega imediata.' },
          ]),
        },
      },
    });

    bm.add('ups-testimonials', {
      label: 'Depoimentos', category: 'Conteúdo',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><path d="M7 10h3V7H7zm0 7h3v-3H7zm7-7h3V7h-3zm0 7h3v-3h-3z"/></svg>',
      content: {
        type: 'ups-testimonials',
        attributes: {
          'data-items': JSON.stringify([
            { name: 'Depoimento de exemplo A', text: 'Edite este bloco com um depoimento real.' },
            { name: 'Depoimento de exemplo B', text: 'Edite este bloco com um depoimento real.' },
          ]),
        },
      },
    });

    bm.add('ups-offer', {
      label: 'Oferta', category: 'Conversão',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zm0 7L2 14l10 5 10-5-10-5z"/></svg>',
      content: {
        type: 'ups-offer',
        attributes: { 'data-title': 'Oferta Especial', 'data-price': 'R$ 97,00', 'data-cta': 'Comprar agora', 'data-url': '#' },
      },
    });

    bm.add('ups-faq', {
      label: 'FAQ', category: 'Conteúdo',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-2h2zm2.1-7.75l-.9.92A2.5 2.5 0 0013 12H11v-.5a3.5 3.5 0 011.03-2.47l1.24-1.26a1.9 1.9 0 00-2.68-2.68L10 6.42l-1.42-1.42A3.9 3.9 0 1115.1 9.25z"/></svg>',
      content: {
        type: 'ups-faq',
        attributes: {
          'data-items': JSON.stringify([
            { q: 'Como funciona?', a: 'Explique aqui como seu produto funciona.' },
            { q: 'Posso cancelar?', a: 'Explique sua política de cancelamento.' },
          ]),
        },
      },
    });

    bm.add('ups-timer', {
      label: 'Temporizador', category: 'Conversão',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><circle cx="12" cy="13" r="8" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 9v4l3 2M9 3h6"/></svg>',
      content: { type: 'ups-timer', attributes: { 'data-minutes': '15', 'data-onend': 'hide' } },
    });

    bm.add('ups-footer', {
      label: 'Rodapé', category: 'Estrutura',
      media: '<svg viewBox="0 0 24 24" width="22" fill="currentColor"><rect x="3" y="16" width="18" height="4" rx="1"/><path d="M4 11h16M4 7h16" stroke="currentColor" stroke-width="2" fill="none"/></svg>',
      content: { type: 'ups-footer', attributes: { 'data-text': 'Rodapé da página' } },
    });
  }

  window.veBlocks = { register: register };
})(window);
