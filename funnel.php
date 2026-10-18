<?php

require_once __DIR__ . '/api/bootstrap.php';

$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
$lookupHost = str_starts_with($host, 'www.') ? substr($host, 4) : $host;
$slug = strtolower(trim((string)($_GET['slug'] ?? ''), '/'));
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#^/f/([a-z0-9-]+)/?$#i', $requestPath, $route)) {
    $slug = strtolower($route[1]);
} elseif (preg_match('#^/f(?:/|$)#i', $requestPath)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Página não encontrada</h1>';
    exit;
}

$domain = null;

try {
    $domainStmt = $pdo->prepare("SELECT * FROM custom_domains WHERE domain = ? AND status = 'active' LIMIT 1");
    $domainStmt->execute([$lookupHost]);
    $domain = $domainStmt->fetch();
} catch (PDOException $e) {
    $domain = null;
}

// Na raiz (/): SEMPRE serve a Raspadinha / Oferta Booster nativa!
// A raspadinha NUNCA é sobrescrita por funis HTML.
if ($slug === '') {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/index.html');
    exit;
}

// Quando há slug (/f/{slug} ou /{slug}): busca o funil HTML correspondente
$funnel = $campaignPageOverride ?? null;
$targetWsId = $domain ? (int)$domain['workspace_id'] : ($workspace ? (int)$workspace->id : 0);

// 1. Se houver domínio customizado identificado, busca preferencialmente o funil deste domínio
if (!$funnel && $domain) {
    $stmt = $pdo->prepare("SELECT * FROM funnels WHERE custom_domain_id = ? AND slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([(int)$domain['id'], $slug]);
    $funnel = $stmt->fetch();
}

// 2. Busca no workspace correspondente pelo slug publicado
if (!$funnel && $targetWsId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM funnels WHERE workspace_id = ? AND slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$targetWsId, $slug]);
    $funnel = $stmt->fetch();
}

// 3. Fallback: busca global pelo slug ativo
if (!$funnel) {
    $stmt = $pdo->prepare("SELECT * FROM funnels WHERE slug = ? AND status = 'published' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$slug]);
    $funnel = $stmt->fetch();
}

if (!$funnel) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Página não encontrada</title><style>body{font:16px system-ui,-apple-system,sans-serif;background:#05153f;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0}.box{max-width:520px;padding:36px;background:#0b1e52;border:1px solid rgba(255,255,255,0.1);border-radius:18px;text-align:center;box-shadow:0 20px 40px rgba(0,0,0,0.3)}h1{font-size:22px;margin:0 0 10px;color:#9fe870}p{color:#dce2fd;font-size:14px;line-height:1.6;margin:0 0 20px}a{display:inline-block;background:#0229c4;color:#fff;text-decoration:none;padding:10px 20px;border-radius:8px;font-weight:600;font-size:13px}</style></head><body><div class="box"><h1>Página ou Funil não encontrado</h1><p>O link acessado (/' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . ') ainda não foi publicado ou não está ativo no momento.</p><a href="/">Ir para a Oferta Principal</a></div></body></html>';
    exit;
}

// Visita da página (uma por visitante a cada 30 min; ignora robôs e prévias)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && empty($_GET['preview'])
    && !preg_match('/bot|crawl|spider|preview|facebookexternalhit|whatsapp|curl|wget/i', $_SERVER['HTTP_USER_AGENT'] ?? 'bot')) {
    $visitCookie = 'upv_' . (int)$funnel['id'];
    if (empty($_COOKIE[$visitCookie])) {
        try {
            (new \App\Repositories\ConversionEventRepository($pdo))->log((int)$funnel['workspace_id'], null, 'page_view', null, [
                'funnel_id'   => (int)$funnel['id'],
                'campaign_id' => !empty($funnel['campaign_id']) ? (int)$funnel['campaign_id'] : null,
            ]);
            @setcookie($visitCookie, '1', ['expires' => time() + 1800, 'path' => '/', 'samesite' => 'Lax']);
        } catch (\Throwable $e) {
        }
    }
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
$scheme = $isHttps ? 'https' : 'http';
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? $host);
$rawHtml = !empty($funnel['published_content']) ? $funnel['published_content'] : $funnel['html_content'];

$campaignOfferUrl = '';
if (!empty($funnel['campaign_id'])) {
    $offerRole = $funnel['template_slug'] === 'funil-downsell' ? 'downsell' : 'upsell';
    $campaignOfferStmt = $pdo->prepare('SELECT slug FROM upsell_offers WHERE workspace_id = ? AND campaign_id = ? AND offer_role = ? ORDER BY id ASC LIMIT 1');
    $campaignOfferStmt->execute([(int)$funnel['workspace_id'], (int)$funnel['campaign_id'], $offerRole]);
    if ($offerSlug = $campaignOfferStmt->fetchColumn()) $campaignOfferUrl = '/o/' . rawurlencode($offerSlug) . '?native=1';
}

$html = strtr($rawHtml, [
    '{{campaign_offer_url}}' => htmlspecialchars($campaignOfferUrl, ENT_QUOTES, 'UTF-8'),
    '{{workspace_id}}' => (string)($funnel['workspace_id'] ?? $workspace->id),
    '{{workspace_slug}}' => $workspace->slug ?? '',
    '{{workspace_name}}' => htmlspecialchars($workspace->name ?? '', ENT_QUOTES, 'UTF-8'),
    '{{domain}}' => $host,
    '{{base_url}}' => $baseUrl,
    '{{api_base}}' => $baseUrl . '/api',
]);

// ═══ GARANTIA DE TRAQUEAMENTO TOTAL ("TRAQUEIE TUDO") ═══
// Extrai Pixel ID se configurado no settings_config
$pixelId = '';
if (!empty($funnel['settings_config'])) {
    $cfg = json_decode((string)$funnel['settings_config'], true);
    if (!empty($cfg['tracking']['meta_pixel_id'])) {
        $pixelId = trim((string)$cfg['tracking']['meta_pixel_id']);
    }
}

// Injeta Snippet do Meta Pixel no <head> se configurado e ainda não presente
if ($pixelId !== '' && !str_contains($html, 'connect.facebook.net/en_US/fbevents.js')) {
    $pixelHeadSnippet = "
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
  fbq('init', " . json_encode($pixelId) . ");
  fbq('track', 'PageView');
  </script>
  <noscript><img height='1' width='1' style='display:none' src='https://www.facebook.com/tr?id=" . urlencode($pixelId) . "&ev=PageView&noscript=1'/></noscript>
  <!-- End Meta Pixel Code -->";
    if (str_contains($html, '</head>')) {
        $html = str_replace('</head>', $pixelHeadSnippet . "\n</head>", $html);
    }
}

// Injeta Script Universal de Traqueamento se ainda não presente no HTML
if (!str_contains($html, 'upscale_campaign_utms')) {
    $universalTrackingScript = "
<script>
(() => {
  'use strict';
  const storageKey = 'upscale_campaign_utms';
  let utmMap = {};

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

  if (typeof window.fbq === 'function') {
    window.fbq('track', 'ViewContent', { content_name: " . json_encode($funnel['name'] ?? 'Campanha') . " });
  }

  const applyUtms = (link) => {
    try {
      if (!link || !link.href || link.href.startsWith('#') || link.href.startsWith('javascript:')) return;
      const targetUrl = new URL(link.href, window.location.href);
      Object.keys(utmMap).forEach((k) => {
        if (!targetUrl.searchParams.has(k)) targetUrl.searchParams.set(k, utmMap[k]);
      });
      link.href = targetUrl.href;
    } catch (err) {}
  };
  document.querySelectorAll('a').forEach(applyUtms);

  document.querySelectorAll('.js-buy, .btn-cta, [data-block-type=\"button\"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (typeof window.fbq === 'function') {
        window.fbq('track', 'InitiateCheckout', { content_name: " . json_encode($funnel['name'] ?? 'Campanha') . " });
      }
    });
  });

  document.querySelectorAll('a[href*=\"whatsapp.com\"], a[href*=\"wa.me\"], a[href*=\"instagram.com\"], a[href*=\"t.me\"]').forEach((ch) => {
    ch.addEventListener('click', () => {
      if (typeof window.fbq === 'function') window.fbq('track', 'Contact', { channel: ch.hostname });
    });
  });

  const scrollFired = {};
  window.addEventListener('scroll', () => {
    const h = document.documentElement, b = document.body;
    const pct = Math.round(((h.scrollTop || b.scrollTop) / ((h.scrollHeight || b.scrollHeight) - h.clientHeight)) * 100);
    [25, 50, 75, 90].forEach((m) => {
      if (pct >= m && !scrollFired[m]) {
        scrollFired[m] = true;
        if (typeof window.fbq === 'function') window.fbq('trackCustom', 'ScrollDepth', { depth: m + '%' });
      }
    });
  }, { passive: true });

  [15, 30, 60].forEach((sec) => {
    setTimeout(() => {
      if (typeof window.fbq === 'function') window.fbq('trackCustom', 'TimeOnPage', { seconds: sec });
    }, sec * 1000);
  });
})();
</script>";

    if (str_contains($html, '</body>')) {
        $html = str_replace('</body>', $universalTrackingScript . "\n</body>", $html);
    } else {
        $html .= $universalTrackingScript;
    }
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Robots-Tag: index, follow');
echo $html;

