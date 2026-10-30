<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

$targetWsId = (int)($request->get('workspace_id') ?? ($request->all()['workspace_id'] ?? ($auth['workspace']->id ?? 1)));
if ($targetWsId <= 0) {
    $targetWsId = $auth['workspace']->id ?? 1;
}

// Biblioteca de mídia por workspace: cada cliente vê só os próprios arquivos (e os antigos, sem dono).
$allowedWsIds = null; // null = todos (master)
if ($auth && $auth['role'] !== 'super_admin') {
    $allowedWsIds = array_merge([$auth['workspace']->id], array_map(fn($w) => $w->id, $wsRepo->findDescendants($auth['workspace']->id)));
    if (!in_array($targetWsId, $allowedWsIds, true)) {
        JsonResponse::error('Acesso negado para este workspace.', 403);
    }
}
$filePrefix = 'w' . $targetWsId . '_';

$uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// 1. GET: Listar arquivos já enviados
if ($request->method() === 'GET') {
    $files = [];
    if (is_dir($uploadDir)) {
        $scanned = scandir($uploadDir, SCANDIR_SORT_DESCENDING);
        if ($scanned) {
            foreach ($scanned as $item) {
                if ($item === '.' || $item === '..' || str_starts_with($item, '.')) continue;
                // Arquivo de outro workspace (prefixo wN_): só aparece para quem administra aquele workspace.
                if (preg_match('/^w(\d+)_/', $item, $own)) {
                    if ((int)$own[1] !== $targetWsId) continue;
                }
                $filePath = $uploadDir . DIRECTORY_SEPARATOR . $item;
                if (!is_file($filePath)) continue;

                $size = filesize($filePath);
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                $type = in_array($ext, ['mp4', 'webm', 'mov'], true) ? 'video' : 'image';

                $files[] = [
                    'filename' => $item,
                    'url' => '/uploads/' . rawurlencode($item),
                    'size' => $size,
                    'type' => $type,
                    'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                ];
                if (count($files) >= 50) break;
            }
        }
    }
    JsonResponse::success(['files' => $files]);
}

// 2. POST: Fazer upload de arquivo novo
if ($request->method() === 'POST') {
    if (empty($_FILES['file']) || !isset($_FILES['file']['error'])) {
        JsonResponse::error('Nenhum arquivo enviado.', 422);
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'O arquivo excede o limite do servidor (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'O arquivo excede o limite do formulário.',
            UPLOAD_ERR_PARTIAL    => 'O upload foi feito parcialmente. Tente novamente.',
            UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo foi selecionado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente no servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao salvar arquivo no disco.',
        ];
        JsonResponse::error($errors[$file['error']] ?? 'Erro ao processar upload.', 400);
    }

    $origName = basename($file['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'mov'  => 'video/quicktime',
    ];

    if (!array_key_exists($ext, $allowed)) {
        JsonResponse::error('Formato não permitido. Formatos aceitos: JPG, PNG, WEBP, GIF, MP4, WEBM.', 415);
    }

    // Validação de MIME Type via finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Extensão E conteúdo precisam bater (antes bastava um dos dois).
    $extIsVideo = in_array($ext, ['mp4', 'webm', 'mov'], true);
    $isImage = !$extIsVideo && str_starts_with((string)$mime, 'image/') && $mime !== 'image/svg+xml';
    $isVideo = $extIsVideo && (str_starts_with((string)$mime, 'video/') || $mime === 'application/octet-stream');

    if (!$isImage && !$isVideo) {
        JsonResponse::error('Tipo MIME inválido: ' . htmlspecialchars((string)$mime), 415);
    }

    // Limites de tamanho (Imagens até 15MB, Vídeos até 100MB)
    $maxSize = $isVideo ? (100 * 1024 * 1024) : (15 * 1024 * 1024);
    if ($file['size'] > $maxSize) {
        JsonResponse::error('O arquivo excede o limite permitido (' . ($isVideo ? '100 MB para vídeos' : '15 MB para imagens') . ').', 413);
    }

    $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($origName, PATHINFO_FILENAME));
    if ($safeBaseName === '') $safeBaseName = 'media';
    $targetFileName = $filePrefix . $safeBaseName . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $targetFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        JsonResponse::error('Não foi possível mover o arquivo para a pasta de destino.', 500);
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    $publicUrl = $scheme . '://' . $host . '/uploads/' . rawurlencode($targetFileName);

    JsonResponse::success([
        'message' => 'Upload realizado com sucesso!',
        'filename' => $targetFileName,
        'url' => '/uploads/' . rawurlencode($targetFileName),
        'absolute_url' => $publicUrl,
        'type' => $isVideo ? 'video' : 'image',
        'size' => $file['size'],
    ]);
}

JsonResponse::error('Method not allowed', 405);
