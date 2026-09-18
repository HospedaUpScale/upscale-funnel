<?php
// ==============================================================================
// Roteador para Servidor Local de Desenvolvimento PHP (php -S 127.0.0.1:8000)
// ==============================================================================

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

if ($uri === '/') {
    require __DIR__ . '/funnel.php';
    return;
}

// 1. Se for um arquivo estático existente (.html, .css, .js, .png, etc.), serve direto
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// 2. Se for uma pasta existente e tiver index.html ou index.php
if (is_dir($file)) {
    $indexPath = rtrim($file, '/\\') . DIRECTORY_SEPARATOR . 'index.html';
    if (file_exists($indexPath)) {
        require $indexPath;
        return;
    }
    $indexPathPhp = rtrim($file, '/\\') . DIRECTORY_SEPARATOR . 'index.php';
    if (file_exists($indexPathPhp)) {
        require $indexPathPhp;
        return;
    }
}

// 3. Rotas específicas da API (com ou sem extensão .php)
if (strpos($uri, '/api') === 0) {
    // Checa se existe arquivo .php direto correspondente
    $phpFile = __DIR__ . rtrim($uri, '/') . '.php';
    if (file_exists($phpFile)) {
        require $phpFile;
        return;
    }

    // Rotas da API DX Hub / Sorteamos padrão
    require __DIR__ . '/api/index.php';
    return;
}

// 4. Funis publicados em URL amigável
if (preg_match('#^/f/([a-z0-9-]+)/?$#i', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/funnel.php';
    return;
}

// 5. Raiz resolve funil por domínio ou mantém a oferta padrão
return false;
