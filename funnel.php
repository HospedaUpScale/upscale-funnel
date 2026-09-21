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

if ($slug === '' && !$domain) {
    readfile(__DIR__ . '/index.html');
    exit;
}

if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT * FROM funnels WHERE workspace_id = ? AND slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$workspace->id, $slug]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM funnels WHERE workspace_id = ? AND custom_domain_id = ? AND is_home = 1 AND status = 'published' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$workspace->id, (int)$domain['id']]);
}

$funnel = $stmt->fetch();
if (!$funnel) {
    // Se não há página HTML customizada na raiz do domínio, serve a Oferta Booster nativa do Workspace!
    if ($slug === '') {
        readfile(__DIR__ . '/index.html');
        exit;
    }
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Funil não encontrado</title><style>body{font:16px system-ui;background:#f4f6fa;color:#05153f;display:grid;place-items:center;min-height:100vh;margin:0}.box{max-width:520px;padding:32px;background:#fff;border:1px solid #e4e8f0;border-radius:18px;text-align:center}a{color:#0229c4}</style></head><body><div class="box"><h1>Funil não encontrado</h1><p>Esta página ainda não foi publicada ou não está vinculada a este domínio.</p></div></body></html>';
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
