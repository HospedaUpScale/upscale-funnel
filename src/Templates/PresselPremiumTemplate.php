<?php

declare(strict_types=1);

namespace App\Templates;

class PresselPremiumTemplate
{
    public const SLUG = 'pressel-premium';
    public const NAME = 'Pressel Premium — Campanhas';
    public const DESCRIPTION = 'Página de alta conversão para rifas e ações com hero imersiva, vídeos de entregas, prêmios, canais e CTA fixo.';

    /**
     * Retorna a configuração padrão estruturada baseada no HTML de referência.
     */
    public static function getDefaultConfig(): array
    {
        return [
            'settings' => [
                'theme' => [
                    'bg' => '#0b0e12',
                    'surface' => '#141922',
                    'line' => '#242c38',
                    'fg' => '#eef1f5',
                    'muted' => '#a1adbd',
                    'accent' => '#f5a524',
                    'accent_ink' => '#1a1206',
                    'pix' => '#32bcad',
                    'font_title' => 'Barlow Condensed',
                    'font_body' => 'Barlow',
                    'content_width' => '560px',
                    'hero_width' => '720px',
                ],
                'seo' => [
                    'title' => 'Ranger V6 Brayam Braz',
                    'description' => 'Concorra a uma Ranger XLT V6 0km com cotas de R$ 0,35. Títulos premiados com iPhone 18 Pro Max e Pix de R$ 5.000 e R$ 1.000.',
                    'og_image' => 'assets/ranger.jpg',
                    'og_image_alt' => 'Ford Ranger prata 0km estacionada ao pôr do sol',
                ],
                'tracking' => [
                    'meta_pixel_id' => '',
                    'cta_track_event' => 'InitiateCheckout', // ou 'Click'
                ],
            ],
            'blocks' => [
                'top' => [
                    'enabled' => true,
                    'organizer_name' => 'Brayam Braz',
                    'instagram_handle' => '@brayambraz',
                    'instagram_url' => 'https://www.instagram.com/brayambraz',
                    'avatar_initials' => 'BB',
                    'avatar_image' => '',
                    'status_text' => 'Ação no ar',
                    'show_instagram' => true,
                    'show_status' => true,
                ],
                'hero' => [
                    'enabled' => true,
                    'image_desktop' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=1200&auto=format&fit=crop&q=80',
                    'image_mobile' => '',
                    'image_alt' => 'Ford Ranger prata 0km estacionada ao pôr do sol',
                    'object_position_desktop' => '50% 60%',
                    'object_position_mobile' => '50% 50%',
                    'gradient_intensity' => 100,
                    'eyebrow' => 'Ação Brayam Braz',
                    'title' => 'Ranger XLT',
                    'title_highlight' => 'V6 0km',
                    'cash_alternative' => 'ou <b>R$ 250 mil</b> no Pix',
                    'price_label' => 'Cota por apenas',
                    'currency_symbol' => 'R$',
                    'price_value' => '0,35',
                    'cta_text' => 'Quero participar',
                    'cta_url' => 'https://brayambraz.com.br/campanha/ranger-v6-0km-ou-250k',
                    'cta_target_blank' => false,
                    'note' => 'Escolha suas cotas e pague no Pix na página oficial da ação.',
                ],
                'marquee' => [
                    'enabled' => true,
                    'speed_seconds' => 28,
                    'bg_color' => '#f5a524',
                    'text_color' => '#1a1206',
                    'items' => [
                        'Ranger XLT V6 0km',
                        'iPhone 18 Pro Max',
                        'Pix de R$ 5.000',
                        'Pix de R$ 1.000',
                        'Cota R$ 0,35',
                    ],
                ],
                'prizes' => [
                    'enabled' => true,
                    'eyebrow' => 'Títulos premiados',
                    'title' => 'Tem prêmio antes do sorteio',
                    'description' => 'Além da Ranger, a ação tem números premiados. Se um deles cair nas suas cotas, o prêmio é seu.',
                    'items' => [
                        [
                            'tag' => 'iPhone',
                            'tag_class' => 'ip',
                            'title' => 'iPhone 18 Pro Max',
                            'description' => 'Lançamento da Apple',
                            'image' => '',
                        ],
                        [
                            'tag' => 'Pix',
                            'tag_class' => 'px',
                            'title' => 'R$ 5.000 no Pix',
                            'description' => 'Direto na sua conta',
                            'image' => '',
                        ],
                        [
                            'tag' => 'Pix',
                            'tag_class' => 'px',
                            'title' => 'R$ 1.000 no Pix',
                            'description' => 'Direto na sua conta',
                            'image' => '',
                        ],
                    ],
                ],
                'deliveries' => [
                    'enabled' => true,
                    'eyebrow' => 'Seja um dos nossos ganhadores',
                    'title' => 'Quem ganha, aparece',
                    'description' => 'Entregas reais de ações anteriores. Dá o play e confere.',
                    'show_cta' => true,
                    'cta_text' => 'O próximo pode ser você',
                    'cta_url' => '', // herda do hero se vazio
                    'items' => [
                        [
                            'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                            'poster_url' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=600&auto=format&fit=crop&q=80',
                            'title' => 'Caminhonete na prancha',
                            'subtitle' => 'Entrega feita até a porta do ganhador',
                            'badge' => 'Entregue',
                        ],
                        [
                            'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                            'poster_url' => 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600&auto=format&fit=crop&q=80',
                            'title' => 'Carro entregue',
                            'subtitle' => 'Ganhador recebendo a chave',
                            'badge' => 'Entregue',
                        ],
                        [
                            'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
                            'poster_url' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=600&auto=format&fit=crop&q=80',
                            'title' => 'PS5 entregue',
                            'subtitle' => 'Perobal/PR',
                            'badge' => 'Entregue',
                        ],
                    ],
                ],
                'steps' => [
                    'enabled' => true,
                    'eyebrow' => 'Leva menos de 1 minuto',
                    'title' => 'Como participar',
                    'show_cta' => true,
                    'cta_text' => 'Garantir minhas cotas',
                    'cta_url' => '',
                    'items' => [
                        [
                            'title' => 'Toque em "Quero participar"',
                            'description' => 'Você vai para a página oficial da ação.',
                        ],
                        [
                            'title' => 'Escolha quantas cotas quer',
                            'description' => 'Cada cota custa R$ 0,35. Quanto mais cotas, mais chances.',
                        ],
                        [
                            'title' => 'Pague no Pix',
                            'description' => 'Pagamento confirmado, seus números saem na hora.',
                        ],
                    ],
                ],
                'channels' => [
                    'enabled' => true,
                    'eyebrow' => 'Canais oficiais',
                    'title' => 'Acompanhe de perto',
                    'description' => 'Resultados, entregas e novas ações saem primeiro na comunidade.',
                    'items' => [
                        [
                            'type' => 'whatsapp',
                            'title' => 'Comunidade no WhatsApp',
                            'subtitle' => 'Entre e receba os avisos',
                            'url' => 'https://braz.digital/cWQG',
                        ],
                        [
                            'type' => 'instagram',
                            'title' => 'Instagram @brayambraz',
                            'subtitle' => 'Bastidores e entregas',
                            'url' => 'https://www.instagram.com/brayambraz',
                        ],
                    ],
                ],
                'faq' => [
                    'enabled' => true,
                    'eyebrow' => 'Dúvidas',
                    'title' => 'Perguntas frequentes',
                    'items' => [
                        [
                            'question' => 'Quanto custa cada cota?',
                            'answer' => 'Cada cota custa R$ 0,35. Você escolhe a quantidade na página oficial da ação.',
                        ],
                        [
                            'question' => 'Como faço o pagamento?',
                            'answer' => 'O pagamento é feito no Pix, direto na página oficial. Assim que ele é confirmado, seus números aparecem.',
                        ],
                        [
                            'question' => 'O que são os títulos premiados?',
                            'answer' => 'São números sorteados com antecedência que valem prêmios: iPhone 18 Pro Max, Pix de R$ 5.000 e Pix de R$ 1.000. Se um deles estiver entre as suas cotas, você ganha o prêmio.',
                        ],
                        [
                            'question' => 'Onde vejo a data do sorteio e o regulamento?',
                            'answer' => 'Data, regulamento e todas as condições ficam na página oficial da ação, no botão "Quero participar".',
                        ],
                        [
                            'question' => 'Como sei que a entrega é real?',
                            'answer' => 'Todas as entregas são gravadas e publicadas no Instagram @brayambraz. Você pode conferir algumas delas nos vídeos acima.',
                        ],
                    ],
                ],
                'footer' => [
                    'enabled' => true,
                    'responsible_name' => 'Brayam Braz',
                    'sub_text' => 'página oficial da ação',
                    'disclaimer' => 'Regras, datas, pagamento e definição dos contemplados são informados na plataforma da ação. Leia o regulamento antes de participar. Proibido para menores de 18 anos.',
                    'external_disclaimer' => 'Este site não é afiliado, associado ou endossado pelo Facebook ou pela Meta Platforms.',
                    'links' => [],
                ],
                'sticky_cta' => [
                    'enabled' => true,
                    'label' => 'Cota por',
                    'price' => 'R$ 0,35',
                    'button_text' => 'Quero participar',
                    'button_url' => '', // herda do hero se vazio
                ],
            ],
        ];
    }

    /**
     * Renderiza o HTML completo da página a partir dos blocos e configurações.
     */
    public static function render(array $blocksConfig, array $settingsConfig = [], array $options = []): string
    {
        $defaults = self::getDefaultConfig();
        $settings = array_replace_recursive($defaults['settings'], $settingsConfig);
        $blocks = array_replace_recursive($defaults['blocks'], $blocksConfig);

        $theme = $settings['theme'];
        $seo = $settings['seo'];
        $tracking = $settings['tracking'];

        $isEditor = !empty($options['is_editor']);
        $publicUrl = $options['public_url'] ?? '';

        // Variáveis de Tema
        $bg = htmlspecialchars($theme['bg'] ?? '#0b0e12', ENT_QUOTES, 'UTF-8');
        $surface = htmlspecialchars($theme['surface'] ?? '#141922', ENT_QUOTES, 'UTF-8');
        $line = htmlspecialchars($theme['line'] ?? '#242c38', ENT_QUOTES, 'UTF-8');
        $fg = htmlspecialchars($theme['fg'] ?? '#eef1f5', ENT_QUOTES, 'UTF-8');
        $muted = htmlspecialchars($theme['muted'] ?? '#a1adbd', ENT_QUOTES, 'UTF-8');
        $accent = htmlspecialchars($theme['accent'] ?? '#f5a524', ENT_QUOTES, 'UTF-8');
        $accentInk = htmlspecialchars($theme['accent_ink'] ?? '#1a1206', ENT_QUOTES, 'UTF-8');
        $pix = htmlspecialchars($theme['pix'] ?? '#32bcad', ENT_QUOTES, 'UTF-8');
        $contentWidth = htmlspecialchars($theme['content_width'] ?? '560px', ENT_QUOTES, 'UTF-8');
        $heroWidth = htmlspecialchars($theme['hero_width'] ?? '720px', ENT_QUOTES, 'UTF-8');

        // Blocos individuais
        $top = $blocks['top'] ?? [];
        $hero = $blocks['hero'] ?? [];
        $marquee = $blocks['marquee'] ?? [];
        $prizes = $blocks['prizes'] ?? [];
        $deliveries = $blocks['deliveries'] ?? [];
        $steps = $blocks['steps'] ?? [];
        $channels = $blocks['channels'] ?? [];
        $faq = $blocks['faq'] ?? [];
        $footer = $blocks['footer'] ?? [];
        $stickyCta = $blocks['sticky_cta'] ?? [];

        $heroCtaUrl = htmlspecialchars(self::sanitizeUrl($hero['cta_url'] ?? '#'), ENT_QUOTES, 'UTF-8');
        $deliveriesCtaUrl = !empty($deliveries['cta_url']) ? htmlspecialchars(self::sanitizeUrl($deliveries['cta_url']), ENT_QUOTES, 'UTF-8') : $heroCtaUrl;
        $stepsCtaUrl = !empty($steps['cta_url']) ? htmlspecialchars(self::sanitizeUrl($steps['cta_url']), ENT_QUOTES, 'UTF-8') : $heroCtaUrl;
        $stickyCtaUrl = !empty($stickyCta['button_url']) ? htmlspecialchars(self::sanitizeUrl($stickyCta['button_url']), ENT_QUOTES, 'UTF-8') : $heroCtaUrl;

        // Meta Pixel
        $pixelId = trim($tracking['meta_pixel_id'] ?? '');
        $pixelEvent = ($tracking['cta_track_event'] ?? 'InitiateCheckout') === 'Click' ? 'Click' : 'InitiateCheckout';

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= htmlspecialchars($seo['title'] ?? 'Campanha Exclusiva', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($seo['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <meta name="theme-color" content="<?= $bg ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:title" content="<?= htmlspecialchars($seo['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:description" content="<?= htmlspecialchars($seo['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <?php if (!empty($seo['og_image'])): ?>
  <meta property="og:image" content="<?= htmlspecialchars(self::sanitizeUrl($seo['og_image']), ENT_QUOTES, 'UTF-8') ?>">
  <?php if (!empty($seo['og_image_alt'])): ?>
  <meta property="og:image:alt" content="<?= htmlspecialchars($seo['og_image_alt'], ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <?php endif; ?>
  <?php if (!empty($publicUrl)): ?>
  <link rel="canonical" href="<?= htmlspecialchars(self::sanitizeUrl($publicUrl), ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:url" content="<?= htmlspecialchars(self::sanitizeUrl($publicUrl), ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <link rel="preload" href="/assets/fonts/barlow-condensed-900.woff" as="font" type="font/woff" crossorigin>
  <?php if (!empty($pixelId) && !$isEditor): ?>
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
  fbq('init', '<?= htmlspecialchars($pixelId, ENT_QUOTES, 'UTF-8') ?>');
  fbq('track', 'PageView');
  </script>
  <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= urlencode($pixelId) ?>&ev=PageView&noscript=1"/></noscript>
  <!-- End Meta Pixel Code -->
  <?php endif; ?>
  <style>
@font-face { font-family: "Barlow Condensed"; src: url("/assets/fonts/barlow-condensed-600.woff") format("woff"); font-weight: 600; font-style: normal; font-display: swap; }
@font-face { font-family: "Barlow Condensed"; src: url("/assets/fonts/barlow-condensed-800.woff") format("woff"); font-weight: 800; font-style: normal; font-display: swap; }
@font-face { font-family: "Barlow Condensed"; src: url("/assets/fonts/barlow-condensed-900.woff") format("woff"); font-weight: 900; font-style: normal; font-display: swap; }
@font-face { font-family: "Barlow"; src: url("/assets/fonts/barlow-400.woff") format("woff"); font-weight: 400; font-style: normal; font-display: swap; }
@font-face { font-family: "Barlow"; src: url("/assets/fonts/barlow-500.woff") format("woff"); font-weight: 500; font-style: normal; font-display: swap; }
@font-face { font-family: "Barlow"; src: url("/assets/fonts/barlow-600.woff") format("woff"); font-weight: 600; font-style: normal; font-display: swap; }
/* Fontes reserva com largura ajustada: enquanto a Barlow carrega, títulos e botões ocupam o mesmo espaço e não quebram linha. */
@font-face { font-family: "Barlow Condensed Reserva"; src: local("Arial Narrow"), local("ArialNarrow"), local("Arial"), local("Helvetica"), local("Roboto"); size-adjust: 71%; }
@font-face { font-family: "Barlow Reserva"; src: local("Arial"), local("Helvetica"), local("Roboto"); size-adjust: 93%; }
:root {
  color-scheme: dark;
  --bg: <?= $bg ?>;
  --surface: <?= $surface ?>;
  --line: <?= $line ?>;
  --fg: <?= $fg ?>;
  --muted: <?= $muted ?>;
  --accent: <?= $accent ?>;
  --accent-ink: <?= $accentInk ?>;
  --pix: <?= $pix ?>;
  --display: "Barlow Condensed", "Barlow Condensed Reserva", "Arial Narrow", sans-serif;
  --body: "Barlow", "Barlow Reserva", system-ui, -apple-system, "Segoe UI", sans-serif;
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
.wrap { width: 100%; max-width: <?= $contentWidth ?>; margin-inline: auto; padding-inline: 16px; }
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
.hero { position: relative; max-width: <?= $heroWidth ?>; margin-inline: auto; }
.hero-img { position: relative; aspect-ratio: 1; overflow: hidden; background: var(--surface); }
.hero-img img { width: 100%; height: 100%; object-fit: cover; object-position: <?= htmlspecialchars($hero['object_position_desktop'] ?? '50% 60%', ENT_QUOTES, 'UTF-8') ?>; }
.hero-img picture { display: contents; }
@media (max-width: 719px) { .hero-img img { object-position: <?= htmlspecialchars($hero['object_position_mobile'] ?? '50% 50%', ENT_QUOTES, 'UTF-8') ?>; } }
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
.band { position: relative; margin-top: 40px; }
.marquee { overflow: hidden; color: <?= htmlspecialchars($marquee['text_color'] ?? '#1a1206', ENT_QUOTES, 'UTF-8') ?>; background: <?= htmlspecialchars($marquee['bg_color'] ?? '#f5a524', ENT_QUOTES, 'UTF-8') ?>; }
.marquee-track { display: flex; width: max-content; animation: roll <?= (int)($marquee['speed_seconds'] ?? 28) ?>s linear infinite; }
.marquee-group { display: flex; flex: 0 0 auto; align-items: center; gap: 28px; padding: 12px 28px 12px 0; white-space: nowrap; font: 800 1.15rem/1.4 var(--display); text-transform: uppercase; }
.marquee .icon { width: 16px; height: 16px; }
@keyframes roll { to { transform: translateX(-50%); } }
.marquee:hover .marquee-track, .marquee.is-paused .marquee-track { animation-play-state: paused; }
.motion-control { position: absolute; right: 8px; top: 50%; z-index: 2; display: grid; place-items: center; width: 36px; height: 36px; padding: 0; transform: translateY(-50%); border: 0; border-radius: 50%; background: #0b0e12b3; color: #fff; cursor: pointer; }
.motion-control::before { content: ""; position: absolute; inset: -4px; }
.motion-control .icon { width: 16px; height: 16px; }
.band:has(.motion-control:not([hidden])) .marquee { -webkit-mask-image: linear-gradient(90deg,#000 calc(100% - 72px),transparent calc(100% - 20px)); mask-image: linear-gradient(90deg,#000 calc(100% - 72px),transparent calc(100% - 20px)); }
.motion-control .i-play { display: none; }
.motion-control[aria-pressed="true"] .i-play { display: block; }
.motion-control[aria-pressed="true"] .i-pause { display: none; }
.sec-head { display: grid; gap: 10px; margin-bottom: 24px; }
.sec-head h2 { font-size: clamp(2.2rem,9vw,3rem); font-weight: 900; }
.sec-head p { margin: 0; color: var(--muted); }
.prizes { display: grid; gap: 10px; }
.prize { display: flex; align-items: center; gap: 16px; padding: 16px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); }
.prize > div { min-width: 0; }
.tag { flex: 0 0 76px; width: 76px; padding: 8px 4px; border-radius: 8px; text-align: center; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font: 900 1rem var(--display); }
.tag.ip { color: var(--bg); background: var(--fg); }
.tag.px { color: #062522; background: var(--pix); }
.prize h3 { font-size: 1.7rem; font-weight: 800; overflow-wrap: anywhere; }
.prize p { margin: 4px 0 0; color: var(--muted); font-size: .92rem; }
.reel { display: grid; grid-auto-flow: column; grid-auto-columns: 78%; gap: 12px; overflow-x: auto; scroll-snap-type: x mandatory; scroll-padding-inline: 16px; margin-inline: -16px; padding: 4px 16px 12px; scrollbar-width: thin; scrollbar-color: var(--line) var(--bg); }
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
@media (min-width: 1024px) {
  .wrap { max-width: 1120px; padding-inline: 32px; }
  .top { position: absolute; }
  .hero { display: grid; grid-template-columns: minmax(0,1.05fr) minmax(0,1fr); align-items: center; gap: 56px; max-width: 1120px; padding: 88px 32px 40px; }
  .hero-img { aspect-ratio: 4/5; max-height: calc(100vh - 140px); border-radius: 24px; border: 1px solid var(--line); }
  .hero-img::after { background: linear-gradient(180deg,transparent 60%,#0b0e1280 100%); }
  .hero-copy { margin-top: 0; padding-inline: 0; gap: 16px; }
  .hero h1 { font-size: clamp(3.6rem,5.6vw,5.6rem); }
  main .btn { max-width: 440px; }
  .hero .note { text-align: left; max-width: 440px; }
  .sec-head { max-width: 680px; }
  .prizes { grid-template-columns: repeat(auto-fit,minmax(300px,1fr)); }
  .reel { grid-template-columns: repeat(auto-fill,minmax(220px,260px)); }
  .steps { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 16px; }
  .steps li { padding: 20px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); }
  .steps li:last-child { border-bottom: 1px solid var(--line); }
  .channels { grid-template-columns: repeat(2,minmax(0,1fr)); }
  [data-block-type="faq"] details { max-width: 760px; }
  footer p { max-width: 760px; }
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
<body class="<?= !empty($stickyCta['enabled']) ? 'has-sticky' : '' ?>">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

<?php if (!empty($top['enabled'])): ?>
<header class="top" data-block-id="blk_top" data-block-type="top">
  <div class="wrap">
    <?php if (!empty($top['show_instagram'])): ?>
    <a class="ig" href="<?= htmlspecialchars(self::sanitizeUrl($top['instagram_url'] ?? 'https://instagram.com'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
      <span class="av" aria-hidden="true">
        <?php if (!empty($top['avatar_image'])): ?>
        <img src="<?= htmlspecialchars(self::sanitizeUrl($top['avatar_image']), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($top['organizer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
        <?= htmlspecialchars($top['avatar_initials'] ?? 'BB', ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
      </span>
      <?= htmlspecialchars($top['instagram_handle'] ?? '@instagram', ENT_QUOTES, 'UTF-8') ?>
      <span class="sr-only"> (abre em nova aba)</span>
    </a>
    <?php endif; ?>
    <?php if (!empty($top['show_status'])): ?>
    <span class="live"><span class="live-dot" aria-hidden="true"></span><?= htmlspecialchars($top['status_text'] ?? 'Ação no ar', ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
  </div>
</header>
<?php endif; ?>

<main id="conteudo" tabindex="-1">
  <?php if (!empty($hero['enabled'])): ?>
  <div class="hero" data-block-id="blk_hero" data-block-type="hero">
    <div class="hero-img">
      <picture>
        <?php if (!empty($hero['image_mobile'])): ?>
        <source media="(max-width: 719px)" srcset="<?= htmlspecialchars(self::sanitizeUrl($hero['image_mobile']), ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <img src="<?= htmlspecialchars(self::sanitizeUrl($hero['image_desktop'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($hero['image_alt'] ?? '', ENT_QUOTES, 'UTF-8') ?>" width="1116" height="1114" fetchpriority="high">
      </picture>
    </div>
    <div class="wrap hero-copy">
      <?php if (!empty($hero['eyebrow'])): ?>
      <span class="eyebrow"><?= htmlspecialchars($hero['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
      <h1>
        <?= htmlspecialchars($hero['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        <?php if (!empty($hero['title_highlight'])): ?>
        <em><?= htmlspecialchars($hero['title_highlight'], ENT_QUOTES, 'UTF-8') ?></em>
        <?php endif; ?>
      </h1>
      <?php if (!empty($hero['cash_alternative'])): ?>
      <div class="or"><?= self::sanitizeRichText($hero['cash_alternative']) ?></div>
      <?php endif; ?>
      <div class="price">
        <small><?= htmlspecialchars($hero['price_label'] ?? 'Cota por apenas', ENT_QUOTES, 'UTF-8') ?></small>
        <strong><span><?= htmlspecialchars($hero['currency_symbol'] ?? 'R$', ENT_QUOTES, 'UTF-8') ?></span><?= htmlspecialchars($hero['price_value'] ?? '0,35', ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
      <a class="btn js-buy" href="<?= $heroCtaUrl ?>" <?= !empty($hero['cta_target_blank']) ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
        <?= htmlspecialchars($hero['cta_text'] ?? 'Quero participar', ENT_QUOTES, 'UTF-8') ?>
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
      </a>
      <?php if (!empty($hero['note'])): ?>
      <p class="note"><?= htmlspecialchars($hero['note'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($marquee['enabled']) && !empty($marquee['items'])): ?>
  <div class="band">
  <div class="marquee" id="prize-marquee" aria-hidden="true" data-block-id="blk_marquee" data-block-type="marquee">
    <div class="marquee-track">
      <div class="marquee-group">
        <?php foreach ($marquee['items'] as $item): ?>
        <span><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></span>
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5Z"/></svg>
        <?php endforeach; ?>
      </div>
      <div class="marquee-group">
        <?php foreach ($marquee['items'] as $item): ?>
        <span><?= htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8') ?></span>
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5Z"/></svg>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <button id="motion-control" class="motion-control" type="button" aria-controls="prize-marquee" aria-pressed="false" aria-label="Pausar animação da faixa" hidden>
    <svg class="icon i-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
    <svg class="icon i-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5Z"/></svg>
  </button>
  </div>
  <?php endif; ?>

  <?php if (!empty($prizes['enabled']) && !empty($prizes['items'])): ?>
  <section aria-labelledby="premios-titulo" data-block-id="blk_prizes" data-block-type="prizes">
    <div class="wrap">
      <div class="sec-head">
        <?php if (!empty($prizes['eyebrow'])): ?>
        <span class="eyebrow"><?= htmlspecialchars($prizes['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <h2 id="premios-titulo"><?= htmlspecialchars($prizes['title'] ?? 'Títulos premiados', ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($prizes['description'])): ?>
        <p><?= htmlspecialchars($prizes['description'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
      <div class="prizes">
        <?php foreach ($prizes['items'] as $prize): ?>
        <article class="prize">
          <span class="tag <?= htmlspecialchars($prize['tag_class'] ?? 'px', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($prize['tag'] ?? 'Pix', ENT_QUOTES, 'UTF-8') ?></span>
          <div>
            <h3><?= htmlspecialchars($prize['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($prize['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($deliveries['enabled']) && !empty($deliveries['items'])): ?>
  <section id="entregas" aria-labelledby="entregas-titulo" data-block-id="blk_deliveries" data-block-type="deliveries">
    <div class="wrap">
      <div class="sec-head">
        <?php if (!empty($deliveries['eyebrow'])): ?>
        <span class="eyebrow"><?= htmlspecialchars($deliveries['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <h2 id="entregas-titulo"><?= htmlspecialchars($deliveries['title'] ?? 'Quem ganha, aparece', ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($deliveries['description'])): ?>
        <p><?= htmlspecialchars($deliveries['description'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
      <div class="reel" tabindex="0" role="region" aria-label="Vídeos de entregas anteriores">
        <?php foreach ($deliveries['items'] as $idx => $del): ?>
        <?php $vidId = $idx + 1; ?>
        <figure class="clip">
          <div class="vid">
            <?php if (!empty($del['badge'])): ?>
            <span class="badge"><?= htmlspecialchars($del['badge'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <video src="<?= htmlspecialchars(self::sanitizeUrl($del['video_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" poster="<?= htmlspecialchars(self::sanitizeUrl($del['poster_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" controls preload="none" playsinline aria-label="Vídeo: <?= htmlspecialchars($del['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" aria-describedby="video-status-<?= $vidId ?>">
              Seu navegador não reproduz este vídeo. <a href="<?= htmlspecialchars(self::sanitizeUrl($del['video_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Abrir vídeo</a>.
            </video>
            <button class="play" type="button" hidden aria-label="Assistir <?= htmlspecialchars($del['title'] ?? 'entrega', ENT_QUOTES, 'UTF-8') ?>">
              <span>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m8 5 11 7-11 7Z" fill="currentColor" stroke="none"/></svg>
              </span>
            </button>
          </div>
          <figcaption>
            <b><?= htmlspecialchars($del['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></b>
            <?php if (!empty($del['subtitle'])): ?>
            <small><?= htmlspecialchars($del['subtitle'], ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
          </figcaption>
          <p class="video-status" id="video-status-<?= $vidId ?>" role="status" aria-live="polite"></p>
        </figure>
        <?php endforeach; ?>
      </div>
      <p class="hint">Arraste para o lado para ver mais entregas <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></p>
      <?php if (!empty($deliveries['show_cta'])): ?>
      <div class="section-cta">
        <a class="btn js-buy" href="<?= $deliveriesCtaUrl ?>">
          <?= htmlspecialchars($deliveries['cta_text'] ?? 'O próximo pode ser você', ENT_QUOTES, 'UTF-8') ?>
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($steps['enabled']) && !empty($steps['items'])): ?>
  <section aria-labelledby="passos-titulo" data-block-id="blk_steps" data-block-type="steps">
    <div class="wrap">
      <div class="sec-head">
        <?php if (!empty($steps['eyebrow'])): ?>
        <span class="eyebrow"><?= htmlspecialchars($steps['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <h2 id="passos-titulo"><?= htmlspecialchars($steps['title'] ?? 'Como participar', ENT_QUOTES, 'UTF-8') ?></h2>
      </div>
      <ol class="steps">
        <?php foreach ($steps['items'] as $step): ?>
        <li>
          <div>
            <strong><?= htmlspecialchars($step['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars($step['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php if (!empty($steps['show_cta'])): ?>
      <a class="btn js-buy" href="<?= $stepsCtaUrl ?>">
        <?= htmlspecialchars($steps['cta_text'] ?? 'Garantir minhas cotas', ENT_QUOTES, 'UTF-8') ?>
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
      </a>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($channels['enabled']) && !empty($channels['items'])): ?>
  <section aria-labelledby="canais-titulo" data-block-id="blk_channels" data-block-type="channels">
    <div class="wrap">
      <div class="sec-head">
        <?php if (!empty($channels['eyebrow'])): ?>
        <span class="eyebrow"><?= htmlspecialchars($channels['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <h2 id="canais-titulo"><?= htmlspecialchars($channels['title'] ?? 'Canais oficiais', ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($channels['description'])): ?>
        <p><?= htmlspecialchars($channels['description'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
      <div class="channels">
        <?php foreach ($channels['items'] as $ch): ?>
        <a class="ghost" href="<?= htmlspecialchars(self::sanitizeUrl($ch['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
          <span class="ico <?= ($ch['type'] ?? '') === 'whatsapp' ? 'wa' : 'igc' ?>">
            <?php if (($ch['type'] ?? '') === 'whatsapp'): ?>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.5 11.6a8.5 8.5 0 0 1-12.6 7.5L3 20.5l1.4-4.8a8.5 8.5 0 1 1 16.1-4.1Z"/><path d="M8.5 7.5c0 4.5 3.5 8 8 8l.7-2-2.8-1.4-1.1 1.1a7 7 0 0 1-2.6-2.6l1.1-1.1-1.4-2.8Z"/></svg>
            <?php else: ?>
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
            <?php endif; ?>
          </span>
          <span class="channel-copy">
            <b><?= htmlspecialchars($ch['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></b>
            <small><?= htmlspecialchars($ch['subtitle'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
            <span class="sr-only"> (abre em nova aba)</span>
          </span>
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 17 17 7M7 7h10v10"/></svg>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($faq['enabled']) && !empty($faq['items'])): ?>
  <section aria-labelledby="faq-titulo" data-block-id="blk_faq" data-block-type="faq">
    <div class="wrap">
      <div class="sec-head">
        <?php if (!empty($faq['eyebrow'])): ?>
        <span class="eyebrow"><?= htmlspecialchars($faq['eyebrow'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <h2 id="faq-titulo"><?= htmlspecialchars($faq['title'] ?? 'Perguntas frequentes', ENT_QUOTES, 'UTF-8') ?></h2>
      </div>
      <?php foreach ($faq['items'] as $item): ?>
      <details>
        <summary>
          <?= htmlspecialchars($item['question'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M12 5v14"/></svg>
        </summary>
        <p><?= htmlspecialchars($item['answer'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
      </details>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</main>

<?php if (!empty($footer['enabled'])): ?>
<div class="wrap" data-block-id="blk_footer" data-block-type="footer">
  <footer>
    <p><strong><?= htmlspecialchars($footer['responsible_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> | <?= htmlspecialchars($footer['sub_text'] ?? 'página oficial da ação', ENT_QUOTES, 'UTF-8') ?></p>
    <?php if (!empty($footer['disclaimer'])): ?>
    <p><?= htmlspecialchars($footer['disclaimer'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (!empty($footer['external_disclaimer'])): ?>
    <p><?= htmlspecialchars($footer['external_disclaimer'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </footer>
</div>
<?php endif; ?>

<?php if (!empty($stickyCta['enabled'])): ?>
<aside class="sticky" id="sticky" aria-label="Participar da ação" aria-hidden="true" inert data-block-id="blk_sticky" data-block-type="sticky_cta">
  <div class="wrap">
    <div class="lbl">
      <small><?= htmlspecialchars($stickyCta['label'] ?? 'Cota por', ENT_QUOTES, 'UTF-8') ?></small>
      <b><?= htmlspecialchars($stickyCta['price'] ?? 'R$ 0,35', ENT_QUOTES, 'UTF-8') ?></b>
    </div>
    <a class="btn js-buy" href="<?= $stickyCtaUrl ?>" tabindex="-1">
      <?= htmlspecialchars($stickyCta['button_text'] ?? 'Quero participar', ENT_QUOTES, 'UTF-8') ?>
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </a>
  </div>
</aside>
<?php endif; ?>

<script>
(() => {
  'use strict';

  // ═══ FULL TRACKING SUITE (TRAQUEAMENTO COMPLETO) ═══
  const storageKey = 'upscale_campaign_utms';
  let utmMap = {};

  // 1. Capturar e persistir UTMs e IDs de tráfego
  try {
    const saved = localStorage.getItem(storageKey);
    if (saved) utmMap = JSON.parse(saved);
  } catch (e) {}

  const currentParams = new URLSearchParams(window.location.search);
  currentParams.forEach((val, key) => {
    if (/^(utm_|fbclid|gclid|ttclid|src|sck|kwai|tiktok|source)/i.test(key)) {
      utmMap[key] = val;
    }
  });

  try {
    localStorage.setItem(storageKey, JSON.stringify(utmMap));
    sessionStorage.setItem(storageKey, JSON.stringify(utmMap));
  } catch (e) {}

  // 2. Disparar ViewContent no Meta Pixel com dados da campanha
  if (typeof window.fbq === 'function') {
    window.fbq('track', 'ViewContent', {
      content_name: <?= json_encode($seo['title'] ?? 'Campanha') ?>,
      content_type: 'product',
      value: <?= json_encode((float)str_replace(',', '.', $hero['price_value'] ?? '0')) ?>,
      currency: 'BRL'
    });
  }

  // 3. Injetar UTMs em TODOS os links e botões da página
  const applyUtms = (link) => {
    try {
      if (!link || !link.href || link.href.startsWith('#') || link.href.startsWith('javascript:')) return;
      const targetUrl = new URL(link.href, window.location.href);
      Object.keys(utmMap).forEach((k) => {
        if (!targetUrl.searchParams.has(k)) {
          targetUrl.searchParams.set(k, utmMap[k]);
        }
      });
      link.href = targetUrl.href;
    } catch (err) {}
  };

  document.querySelectorAll('a').forEach(applyUtms);

  // 4. Rastreamento de Cliques nos Botões de Ação (Hero, Sticky, etc.)
  document.querySelectorAll('.js-buy').forEach((link) => {
    link.addEventListener('click', () => {
      try {
        if (typeof window.fbq === 'function') {
          window.fbq('track', '<?= $pixelEvent ?>', {
            content_name: <?= json_encode($hero['title'] ?? 'Campanha') ?>,
            value: <?= json_encode((float)str_replace(',', '.', $hero['price_value'] ?? '0')) ?>,
            currency: 'BRL'
          });
        }
      } catch (e) {
        console.warn('Tracking de clique não enviado.', e);
      }
    });
  });

  // 5. Rastreamento de Contato / Redes Sociais / WhatsApp
  document.querySelectorAll('a[href*="whatsapp.com"], a[href*="wa.me"], a[href*="instagram.com"], a[href*="t.me"]').forEach((channel) => {
    channel.addEventListener('click', () => {
      try {
        if (typeof window.fbq === 'function') {
          window.fbq('track', 'Contact', { channel: channel.hostname });
        }
      } catch (e) {}
    });
  });

  // 6. Rastreamento de Rolagem (Scroll Depth: 25%, 50%, 75%, 90%)
  const scrollFired = {};
  window.addEventListener('scroll', () => {
    const h = document.documentElement;
    const b = document.body;
    const percent = Math.round(((h.scrollTop || b.scrollTop) / ((h.scrollHeight || b.scrollHeight) - h.clientHeight)) * 100);
    [25, 50, 75, 90].forEach((milestone) => {
      if (percent >= milestone && !scrollFired[milestone]) {
        scrollFired[milestone] = true;
        if (typeof window.fbq === 'function') {
          window.fbq('trackCustom', 'ScrollDepth', { depth: milestone + '%' });
        }
      }
    });
  }, { passive: true });

  // 7. Rastreamento de Tempo na Página (Engagement: 15s, 30s, 60s)
  [15, 30, 60].forEach((sec) => {
    setTimeout(() => {
      if (typeof window.fbq === 'function') {
        window.fbq('trackCustom', 'TimeOnPage', { seconds: sec });
      }
    }, sec * 1000);
  });

  // 8. Rastreamento de Interação com FAQ
  document.querySelectorAll('details.faq-item').forEach((faq) => {
    faq.addEventListener('toggle', () => {
      if (faq.open && typeof window.fbq === 'function') {
        const q = faq.querySelector('.faq-q');
        window.fbq('trackCustom', 'FaqInteraction', { question: q ? q.textContent.trim() : '' });
      }
    });
  });

  // Controle inteligente de vídeos
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
      motionButton.setAttribute('aria-label', paused ? 'Retomar animação da faixa' : 'Pausar animação da faixa');
    });
  }

  // CTA fixo sincronizado com a saída do primeiro botão da viewport
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
      document.documentElement.style.setProperty('--sticky-space', `${Math.ceil(sticky.getBoundingClientRect().height) + 16}px`);
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
</script>
</body>
</html>
<?php
        return ob_get_clean();
    }

    /**
     * Sanitiza URLs para evitar injeção de javascript: ou data:
     */
    public static function sanitizeUrl(?string $url): string
    {
        $url = trim((string)$url);
        if ($url === '') return '';
        if (preg_match('~^(https?://|/|#)~i', $url)) {
            return $url;
        }
        return '#' . preg_replace('/[^a-zA-Z0-9_-]/', '', $url);
    }

    /**
     * Sanitiza pequenos trechos de rich text permitindo apenas tags seguras (b, strong, em, i, span).
     */
    public static function sanitizeRichText(?string $html): string
    {
        if (empty($html)) return '';
        return strip_tags($html, '<b><strong><em><i><span><br>');
    }
}
