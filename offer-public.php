<?php

require_once __DIR__ . '/api/bootstrap.php';

use App\Support\OfferTheme;

$slug = strtolower(trim((string)($_GET['offer_slug'] ?? ($_GET['slug'] ?? '')), '/'));
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#^/o/([a-z0-9-]+)/?$#i', $requestPath, $route)) $slug = strtolower($route[1]);


if (empty($slug)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Oferta não especificada</title></head><body><h1>Oferta não especificada</h1></body></html>';
    exit;
}

// 1. Busca oferta publicada no banco
$stmt = $pdo->prepare("SELECT * FROM upsell_offers WHERE slug = ? AND is_published = 1 AND is_active = 1 LIMIT 1");
$stmt->execute([$slug]);
$offer = $stmt->fetch();

// 2. Se não encontrada ou não publicada -> 404 limpo e elegante
if (!$offer) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Oferta Indisponível</title>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
      <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
          font-family: 'Inter', -apple-system, sans-serif;
          background: #06152D;
          color: #FFFFFF;
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 24px;
          text-align: center;
          position: relative;
          overflow: hidden;
        }
        body::before {
          content: '';
          position: absolute;
          inset: 0;
          background: radial-gradient(circle at 50% 20%, rgba(23, 100, 255, 0.15), transparent 70%);
          pointer-events: none;
        }
        .error-card {
          position: relative;
          z-index: 1;
          max-width: 460px;
          width: 100%;
          background: #0C2247;
          border: 1px solid rgba(23, 100, 255, 0.25);
          border-radius: 16px;
          padding: 40px 28px;
          box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 30px rgba(23, 100, 255, 0.15);
        }
        .icon-box {
          width: 64px;
          height: 64px;
          border-radius: 50%;
          background: rgba(23, 100, 255, 0.12);
          border: 1.5px solid rgba(23, 100, 255, 0.4);
          color: #60A5FA;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 20px;
        }
        h1 {
          font-family: 'Sora', sans-serif;
          font-size: 22px;
          font-weight: 700;
          margin-bottom: 10px;
          color: #FFFFFF;
        }
        p {
          font-size: 14.5px;
          color: #B7C5DE;
          line-height: 1.6;
          margin-bottom: 24px;
        }
        .btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 12px 24px;
          border-radius: 8px;
          background: #1764FF;
          color: #FFFFFF;
          text-decoration: none;
          font-weight: 600;
          font-size: 14px;
          transition: background 0.2s, transform 0.15s;
        }
        .btn:hover {
          background: #0F52DB;
          transform: translateY(-1px);
        }
      </style>
    </head>
    <body>
      <div class="error-card">
        <div class="icon-box">
          <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h1>Oferta Indisponível</h1>
        <p>A condição especial que você tentou acessar não está publicada ou já foi finalizada no momento.</p>
        <a href="/" class="btn">Voltar para a Página Principal</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// O mesmo link da oferta passa a servir a página publicada da campanha.
// native=1 é usado somente no bloco de checkout, evitando recursão do funil.
if (!empty($offer['campaign_id']) && ($request->get('native') !== '1')) {
    $pageStmt = $pdo->prepare("SELECT * FROM funnels WHERE workspace_id = ? AND campaign_id = ? AND status = 'published' ORDER BY id DESC LIMIT 1");
    $pageStmt->execute([(int)$offer['workspace_id'], (int)$offer['campaign_id']]);
    if ($campaignPageOverride = $pageStmt->fetch(PDO::FETCH_ASSOC)) {
        $_GET['slug'] = $campaignPageOverride['slug'];
        require __DIR__ . '/funnel.php';
        exit;
    }
}

// 3. Montar dados a partir de published_config (congelado) com fallback
$pubConfig = [];
if (!empty($offer['published_config'])) {
    $dec = is_string($offer['published_config']) ? json_decode($offer['published_config'], true) : $offer['published_config'];
    if (is_array($dec)) {
        $pubConfig = $dec;
    }
}

$title = $pubConfig['title'] ?? ($offer['title'] ?? 'Booster 200X Ouro');
$subtitle = $pubConfig['subtitle'] ?? ($offer['subtitle'] ?? 'Kit Bobzão Bitruck + Ford F250');
$price = (float)($pubConfig['price'] ?? ($offer['price'] ?? 90.00));
$quotaCount = (int)($pubConfig['quota_count'] ?? ($offer['quota_count'] ?? 200));
$vturbAccountId = $pubConfig['vturb_account_id'] ?? ($offer['vturb_account_id'] ?? '');
$vturbPlayerId = $pubConfig['vturb_player_id'] ?? ($offer['vturb_player_id'] ?? '');
$legacyCheckoutUrl = $pubConfig['legacy_checkout_url'] ?? ($offer['legacy_checkout_url'] ?? '');
$checkoutMode = $pubConfig['checkout_mode'] ?? ($offer['checkout_mode'] ?? 'pix_native');

$themeData = $pubConfig['theme'] ?? [];
if (empty($themeData) && !empty($offer['theme_config'])) {
    $decTheme = is_string($offer['theme_config']) ? json_decode($offer['theme_config'], true) : $offer['theme_config'];
    if (is_array($decTheme)) {
        $themeData = $decTheme;
    }
}

$theme = OfferTheme::merge($themeData);
if ($request->get('native') === '1') {
    $experience = (string)$request->get('experience', 'direct');
    if (in_array($experience, ['scratch', 'wheel', 'direct'], true)) $theme['mechanic'] = $experience;
    elseif ($experience !== 'campaign') $theme['mechanic'] = 'direct';
}

$payloadForHtml = [
    'id'                  => (int)$offer['id'],
    'workspace_id'        => (int)$offer['workspace_id'],
    'title'               => $title,
    'subtitle'            => $subtitle,
    'price'               => $price,
    'quota_count'         => $quotaCount,
    'vturb_account_id'    => $vturbAccountId,
    'vturb_player_id'     => $vturbPlayerId,
    'legacy_checkout_url' => $legacyCheckoutUrl,
    'checkout_mode'       => $checkoutMode,
    'slug'                => $slug,
    'theme'               => $theme,
];

// Serve o HTML da raspadinha com os dados publicados pré-injetados
$htmlTemplate = file_get_contents(__DIR__ . '/index.html');

// Injetar script com window.__INITIAL_OFFER_DATA__ antes do fechamento do head
$injectedScript = '<script>window.__INITIAL_OFFER_DATA__ = ' . json_encode($payloadForHtml, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>' . "\n" . '</head>';
$htmlRendered = str_replace('</head>', $injectedScript, $htmlTemplate);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=60');
echo $htmlRendered;
