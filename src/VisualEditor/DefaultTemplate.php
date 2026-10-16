<?php

namespace App\VisualEditor;

/**
 * Modelo padrao de landing page para o editor visual (GrapesJS).
 *
 * Retorna um Project Data compativel com GrapesJS getProjectData/loadProjectData,
 * com componentes do nosso dominio (secao, titulo, botao, oferta, FAQ, timer...)
 * ja estilizados em CSS proprio e usando variaveis.
 *
 * Importante: ao criar uma pagina a partir do modelo, a copia e independente.
 * Editar essa pagina nao modifica o modelo (o Hub chama load com id=0 so uma vez,
 * e passa a salvar como novo funnel com o JSON clonado).
 */
class DefaultTemplate
{
    public static function projectData(): array
    {
        return [
            'assets' => [],
            'pages'  => [[
                'frames' => [[
                    'component' => self::rootComponent(),
                ]],
            ]],
            'styles' => self::styles(),
            'symbols' => [],
        ];
    }

    private static function rootComponent(): array
    {
        return [
            'type' => 'wrapper',
            'components' => [
                // HERO
                [
                    'type' => 'ups-section',
                    'attributes' => ['data-section' => 'hero'],
                    'components' => [
                        ['type' => 'ups-heading', 'content' => 'Sua nova landing page'],
                        ['type' => 'ups-text',    'content' => 'Edite textos, imagens e cores direto no painel. Este e um conteudo demonstrativo.'],
                        [
                            'type' => 'ups-button',
                            'attributes' => ['href' => '#oferta', 'data-cta' => 'hero'],
                            'content' => 'Quero participar',
                        ],
                    ],
                ],

                // IMAGEM
                [
                    'type' => 'ups-section',
                    'components' => [
                        [
                            'type' => 'ups-image',
                            'attributes' => [
                                'src' => '/assets/editor-placeholder.svg',
                                'alt' => 'Imagem demonstrativa',
                            ],
                        ],
                    ],
                ],

                // BENEFICIOS
                [
                    'type' => 'ups-section',
                    'components' => [
                        ['type' => 'ups-heading', 'content' => 'Por que participar'],
                        [
                            'type' => 'ups-benefits',
                            'attributes' => [
                                'data-items' => json_encode([
                                    ['icon' => '🎯', 'title' => 'Rapido', 'text' => 'Checkout em 1 clique via Pix.'],
                                    ['icon' => '🛡️', 'title' => 'Seguro', 'text' => 'Dados protegidos ponta a ponta.'],
                                    ['icon' => '⚡',  'title' => 'Instantaneo', 'text' => 'Cotas liberadas automaticamente.'],
                                ], JSON_UNESCAPED_UNICODE),
                            ],
                        ],
                    ],
                ],

                // VIDEO
                [
                    'type' => 'ups-section',
                    'components' => [
                        ['type' => 'ups-heading', 'content' => 'Veja como funciona'],
                        [
                            'type' => 'ups-video',
                            'attributes' => ['src' => 'https://www.youtube.com/embed/dQw4w9WgXcQ'],
                        ],
                    ],
                ],

                // DEPOIMENTOS
                [
                    'type' => 'ups-section',
                    'components' => [
                        ['type' => 'ups-heading', 'content' => 'O que estao dizendo'],
                        [
                            'type' => 'ups-testimonials',
                            'attributes' => [
                                'data-items' => json_encode([
                                    ['name' => 'Depoimento de exemplo A', 'text' => 'Texto de depoimento demonstrativo. Edite este bloco com um depoimento real do seu cliente.'],
                                    ['name' => 'Depoimento de exemplo B', 'text' => 'Texto de depoimento demonstrativo. Edite este bloco com um depoimento real do seu cliente.'],
                                ], JSON_UNESCAPED_UNICODE),
                            ],
                        ],
                    ],
                ],

                // OFERTA
                [
                    'type' => 'ups-section',
                    'attributes' => ['id' => 'oferta', 'data-section' => 'offer'],
                    'components' => [
                        [
                            'type' => 'ups-offer',
                            'attributes' => [
                                'data-title' => 'Oferta exemplo',
                                'data-price' => 'R$ 90,00',
                                'data-cta'   => 'Garantir agora',
                                'data-url'   => '#checkout',
                            ],
                        ],
                    ],
                ],

                // TIMER
                [
                    'type' => 'ups-section',
                    'components' => [
                        ['type' => 'ups-heading', 'content' => 'Oferta por tempo limitado'],
                        [
                            'type' => 'ups-timer',
                            'attributes' => ['data-minutes' => '15'],
                        ],
                    ],
                ],

                // FAQ
                [
                    'type' => 'ups-section',
                    'components' => [
                        ['type' => 'ups-heading', 'content' => 'Perguntas frequentes'],
                        [
                            'type' => 'ups-faq',
                            'attributes' => [
                                'data-items' => json_encode([
                                    ['q' => 'Como funciona o pagamento?', 'a' => 'Via Pix instantaneo. As cotas sao liberadas na hora apos a confirmacao.'],
                                    ['q' => 'Posso editar depois?', 'a' => 'Sim, voce pode voltar ao editor visual a qualquer momento.'],
                                ], JSON_UNESCAPED_UNICODE),
                            ],
                        ],
                    ],
                ],

                // RODAPE
                [
                    'type' => 'ups-footer',
                    'attributes' => [
                        'data-text' => 'Pagina criada no editor visual upScale.',
                    ],
                ],
            ],
        ];
    }

    private static function styles(): array
    {
        // CSS "proprio e organizado" para a pagina publica. Variaveis para cores e
        // fontes, tipografia legivel, flexbox, bom em celular. Fica isolado do
        // painel administrativo (so se aplica dentro do documento publicado).
        $css = <<<CSS
:root {
  --ups-bg: #0b1120;
  --ups-fg: #f8fafc;
  --ups-muted: #94a3b8;
  --ups-primary: #1764ff;
  --ups-accent: #9fe870;
  --ups-max: 980px;
  --ups-radius: 14px;
  --ups-font: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
}
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { background: var(--ups-bg); color: var(--ups-fg); font-family: var(--ups-font); line-height: 1.5; -webkit-font-smoothing: antialiased; }
img, video, iframe { max-width: 100%; display: block; }
a { color: inherit; }

.ups-section { padding: 48px 20px; display: flex; flex-direction: column; align-items: center; }
.ups-section > * + * { margin-top: 16px; }
.ups-heading { font-size: clamp(28px, 5vw, 44px); font-weight: 800; letter-spacing: -0.01em; text-align: center; max-width: var(--ups-max); margin: 0 auto; }
.ups-text { color: var(--ups-muted); text-align: center; max-width: 640px; margin: 0 auto; font-size: 17px; }
.ups-button { display: inline-flex; align-items: center; justify-content: center; min-height: 52px; padding: 14px 24px; border-radius: var(--ups-radius); background: var(--ups-primary); color: #fff; text-decoration: none; font-weight: 700; font-size: 16px; letter-spacing: .01em; box-shadow: 0 12px 30px -10px color-mix(in srgb, var(--ups-primary) 60%, transparent); }
.ups-image img { width: 100%; max-width: var(--ups-max); margin: 0 auto; border-radius: var(--ups-radius); object-fit: cover; }

.ups-benefits { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; max-width: var(--ups-max); width: 100%; }
.ups-benefit { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: var(--ups-radius); padding: 20px; }
.ups-benefit .ic { font-size: 28px; margin-bottom: 6px; }
.ups-benefit h3 { margin: 4px 0 6px; font-size: 18px; }
.ups-benefit p  { margin: 0; color: var(--ups-muted); font-size: 14.5px; }

.ups-video iframe { width: 100%; max-width: var(--ups-max); aspect-ratio: 16 / 9; border: 0; border-radius: var(--ups-radius); }

.ups-testimonials { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; max-width: var(--ups-max); width: 100%; }
.ups-testimonial { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: var(--ups-radius); padding: 20px; }
.ups-testimonial .nm { font-weight: 700; margin-bottom: 6px; }
.ups-testimonial .tx { color: var(--ups-muted); font-size: 15px; }

.ups-offer { background: linear-gradient(135deg, color-mix(in srgb, var(--ups-primary) 15%, transparent), rgba(255,255,255,.03)); border: 1px solid rgba(255,255,255,.1); padding: 28px; border-radius: var(--ups-radius); max-width: var(--ups-max); width: 100%; text-align: center; }
.ups-offer .ti { font-size: 24px; font-weight: 800; }
.ups-offer .pr { font-size: 36px; font-weight: 900; color: var(--ups-accent); margin: 10px 0 16px; }

.ups-timer { display: flex; justify-content: center; gap: 10px; font-weight: 800; font-size: 28px; font-variant-numeric: tabular-nums; }
.ups-timer .b { background: rgba(255,255,255,.06); border-radius: 10px; padding: 10px 14px; min-width: 56px; text-align: center; }

.ups-faq { max-width: 720px; width: 100%; }
.ups-faq details { border-bottom: 1px solid rgba(255,255,255,.1); padding: 14px 0; }
.ups-faq summary { font-weight: 700; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center; }
.ups-faq summary::after { content: "+"; font-weight: 900; transition: transform .2s; }
.ups-faq details[open] summary::after { transform: rotate(45deg); }
.ups-faq .a { padding-top: 10px; color: var(--ups-muted); }

.ups-footer { padding: 28px 20px; text-align: center; color: var(--ups-muted); border-top: 1px solid rgba(255,255,255,.08); font-size: 13px; }

@media (max-width: 640px) {
  .ups-section { padding: 36px 16px; }
  .ups-button { width: 100%; }
}
CSS;
        return [[ 'selectors' => [], 'style' => (object)[] , 'atRuleType' => null, 'rawCss' => $css ]];
    }
}
