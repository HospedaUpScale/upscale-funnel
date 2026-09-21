<?php

require_once __DIR__ . '/api/bootstrap.php';

$host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
$lookupHost = str_starts_with($host, 'www.') ? substr($host, 4) : $host;
$slug = strtolower(trim((string)($_GET['slug'] ?? ''), '/'));
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
$funnel = null;

// 1. Tenta buscar no workspace atual pelo slug publicado
$stmt = $pdo->prepare("SELECT * FROM funnels WHERE workspace_id = ? AND slug = ? AND status = 'published' LIMIT 1");
$stmt->execute([$workspace->id, $slug]);
$funnel = $stmt->fetch();

// 2. Se houver domínio customizado, tenta pelo custom_domain_id
if (!$funnel && $domain) {
    $stmt = $pdo->prepare("SELECT * FROM funnels WHERE custom_domain_id = ? AND slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([(int)$domain['id'], $slug]);
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

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
$scheme = $isHttps ? 'https' : 'http';
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? $host);
$html = strtr($funnel['html_content'], [
    '{{workspace_id}}' => (string)$workspace->id,
    '{{workspace_slug}}' => $workspace->slug,
    '{{workspace_name}}' => htmlspecialchars($workspace->name, ENT_QUOTES, 'UTF-8'),
    '{{domain}}' => $host,
    '{{base_url}}' => $baseUrl,
    '{{api_base}}' => $baseUrl . '/api',
]);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Robots-Tag: index, follow');
echo $html;
