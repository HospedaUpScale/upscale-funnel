(function (window) {
  "use strict";
  var css =
    "*{box-sizing:border-box}body{margin:0;font-family:system-ui,sans-serif;background:#071b23;color:#ecfff7}.flow-card{max-width:720px;margin:0 auto;padding:64px 28px;text-align:center;min-height:85vh;display:flex;flex-direction:column;align-items:center;justify-content:center}h1{font-size:clamp(32px,6vw,56px);line-height:1.1;margin:16px 0}p{font-size:18px;line-height:1.7;color:#b9d8cc}.eyebrow{font-size:12px;letter-spacing:3px;color:#7ce6bc;text-transform:uppercase}.cta{display:inline-block;background:#9ef1bb;color:#08231b;border:0;border-radius:12px;padding:18px 30px;font-size:17px;font-weight:750;text-decoration:none;cursor:pointer;margin:12px}.secondary{color:#bed8cd;font-size:14px}.scratch{position:relative;width:min(360px,90%);height:180px;margin:24px auto;background:#d7f7db;color:#173d25;border-radius:18px;overflow:hidden;display:grid;place-items:center}.scratch strong{font-size:26px}.scratch canvas{position:absolute;inset:0;width:100%;height:100%;touch-action:none}.wheel{width:240px;height:240px;border-radius:50%;background:conic-gradient(#9ef1bb 0deg 60deg,#1a5c4c 60deg 120deg,#e5c571 120deg 180deg,#347f68 180deg 240deg,#9ef1bb 240deg 300deg,#e5c571 300deg);border:8px solid #ecfff7;box-shadow:0 15px 60px #0006;transition:transform 2.2s cubic-bezier(.12,.8,.2,1);margin:28px auto;display:grid;place-items:center}.wheel span{background:#071b23;color:white;border-radius:50%;padding:22px;font-weight:bold}.offer-price{font-size:48px;font-weight:800;color:#9ef1bb}.notice{font-size:12px;max-width:500px}.flow-card ul{text-align:left;line-height:2;color:#b9d8cc}";
  function stage(id, name, body) {
    return {
      id: id,
      name: name,
      html: '<main class="flow-card">' + body + "</main>",
      css: css,
    };
  }
  function welcome(title, subtitle) {
    return stage(
      "inicio",
      "Apresentação",
      '<span class="eyebrow">Uma oportunidade especial</span><h1>' +
        title +
        "</h1><p>" +
        subtitle +
        '</p><a href="#" class="cta" data-flow-next>Começar agora →</a><p class="notice">Edite os textos, condições e imagens para a sua campanha.</p>',
    );
  }
  function checkout() {
    return stage(
      "oferta",
      "Oferta e checkout",
      '<span class="eyebrow">Sua oferta</span><h1>Aproveite esta condição especial</h1><p>Descreva aqui o produto, a quantidade e os benefícios.</p><div class="offer-price">R$ 90,00</div><ul><li>Primeiro benefício da sua oferta</li><li>Segundo benefício da sua oferta</li></ul><a class="cta" href="" data-checkout-link>Configurar link de checkout</a><p class="notice">Substitua o endereço deste botão pelo seu checkout de pagamento.</p><a href="#" class="secondary" data-flow-next>Continuar sem pagamento (demonstração)</a>',
    );
  }
  function thanks() {
    return stage(
      "conclusao",
      "Conclusão",
      '<span class="eyebrow">Obrigado pela visita</span><h1>Você chegou à última etapa!</h1><p>Personalize as instruções de acompanhamento e atendimento.</p><a class="cta" href="#" data-flow-goto="inicio">Voltar ao início</a>',
    );
  }
  var catalog = [
    {
      id: "landing",
      icon: "✦",
      name: "Landing page",
      description: "Apresentação e oferta em uma página.",
      steps: [
        stage(
          "inicio",
          "Página principal",
          '<span class="eyebrow">Sua marca aqui</span><h1>Transforme visitantes em clientes</h1><p>Apresente sua oferta com uma página que transmite confiança.</p><a class="cta" href="https://example.com" target="_blank" rel="noopener">Conhecer a oferta</a><h2>Por que escolher esta oferta?</h2><p>Edite os benefícios, adicione imagens e depoimentos usando os blocos.</p>',
        ),
      ],
    },
    {
      id: "funil",
      icon: "↗",
      name: "Funil de vendas",
      description: "Apresentação → oferta e checkout → conclusão.",
      steps: [
        welcome(
          "Sua próxima conquista começa aqui",
          "Conheça os benefícios e escolha a melhor oferta para você.",
        ),
        checkout(),
        thanks(),
      ],
    },
    {
      id: "raspadinha",
      icon: "▧",
      name: "Raspadinha",
      description: "Apresentação → raspar → resultado → oferta → conclusão.",
      steps: [
        welcome(
          "Descubra uma oferta especial",
          "Raspe o cartão na próxima etapa para revelar sua condição promocional.",
        ),
        stage(
          "raspar",
          "Raspadinha",
          '<span class="eyebrow">Raspe para revelar</span><h1>Sua surpresa está aqui</h1><p>Passe o dedo ou o mouse sobre o cartão.</p><div class="scratch" data-scratch><strong>Oferta especial!</strong><canvas aria-label="Área para raspar"></canvas></div><a href="#" class="cta" data-flow-next>Ver meu resultado →</a>',
        ),
        stage(
          "resultado",
          "Resultado",
          '<span class="eyebrow">Condição revelada</span><h1>Uma oferta especial para você</h1><p>Personalize o resultado e as regras da promoção. Esta demonstração não atribui prêmios aleatórios.</p><a href="#" class="cta" data-flow-next>Conhecer minha oferta →</a>',
        ),
        checkout(),
        thanks(),
      ],
    },
    {
      id: "roleta",
      icon: "◉",
      name: "Roleta",
      description: "Apresentação → girar → resultado → oferta → conclusão.",
      steps: [
        welcome(
          "Gire e conheça sua oferta",
          "Uma experiência interativa para apresentar sua condição promocional.",
        ),
        stage(
          "girar",
          "Roleta",
          '<span class="eyebrow">Experiência promocional</span><h1>Vamos girar?</h1><div class="wheel" data-wheel><span>Sua oferta</span></div><button class="cta" data-spin>Girar roleta</button><a href="#" class="secondary" data-flow-next>Continuar sem animação →</a>',
        ),
        stage(
          "resultado",
          "Resultado",
          '<span class="eyebrow">Sua condição especial</span><h1>Veja o que preparamos para você</h1><p>Edite o resultado, o regulamento e as condições. A animação é demonstrativa e não realiza sorteios.</p><a href="#" class="cta" data-flow-next>Ver oferta →</a>',
        ),
        checkout(),
        thanks(),
      ],
    },
    {
      id: "blank",
      icon: "＋",
      name: "Página em branco",
      description: "Monte sua página com blocos e adicione etapas.",
      steps: [
        stage(
          "inicio",
          "Página principal",
          "<h1>Sua nova página</h1><p>Clique para editar ou arraste blocos para começar.</p>",
        ),
      ],
    },
    {
      id: "html",
      icon: "〈/〉",
      name: "HTML pronto",
      description: "Cole seu código completo, visualize e publique.",
      steps: [
        {
          id: "inicio",
          name: "Página HTML",
          raw: true,
          html: '<!doctype html>\n<html lang="pt-BR">\n<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Minha página</title></head>\n<body><h1>Cole seu HTML aqui</h1></body>\n</html>',
          css: "",
        },
      ],
    },
  ];
  window.veTemplates = {
    catalog: catalog,
    create: function (id) {
      var t =
        catalog.find(function (x) {
          return x.id === id;
        }) || catalog[0];
      return { template: id, steps: JSON.parse(JSON.stringify(t.steps)) };
    },
  };
})(window);
