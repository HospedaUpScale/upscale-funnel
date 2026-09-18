<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

$targetWsId = (int)($request->get('workspace_id') ?? ($auth['workspace']->id ?? 2));
if ($targetWsId <= 0) $targetWsId = $auth['workspace']->id ?? 2;

if ($auth && $auth['role'] !== 'super_admin') {
    $allowedIds = [$auth['workspace']->id];
    foreach ($wsRepo->findDescendants($auth['workspace']->id) as $descendant) {
        $allowedIds[] = $descendant->id;
    }
    if (!in_array($targetWsId, $allowedIds, true)) {
        JsonResponse::error('Acesso negado para este workspace.', 403);
    }
}

function normalizeDomain(string $value): string
{
    $value = trim(strtolower($value));
    if ($value === '') return '';
    if (!str_contains($value, '://')) $value = 'https://' . $value;
    $host = parse_url($value, PHP_URL_HOST) ?: '';
    $host = rtrim(strtolower($host), '.');
    return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
}

function domainDnsInstructions(string $domain, array $config): array
{
    $targetHost = strtolower($config['base_domain'] ?? ($_SERVER['HTTP_HOST'] ?? ''));
    $targetIp = $targetHost !== '' ? gethostbyname($targetHost) : '';
    if ($targetIp === $targetHost || $targetIp === '') {
        $targetIp = $_SERVER['SERVER_ADDR'] ?? '';
    }

    return [
        'target_hostname' => $targetHost,
        'target_ip' => $targetIp,
        'records' => [
            ['type' => 'A', 'name' => '@', 'value' => $targetIp, 'recommended_for' => 'domínio raiz'],
            ['type' => 'CNAME', 'name' => 'www', 'value' => $targetHost, 'recommended_for' => 'www'],
        ],
    ];
}

function domainIsPointed(string $domain, array $config, string $verificationToken): bool
{
    $targetHost = strtolower($config['base_domain'] ?? '');
    $domainIps = gethostbynamel($domain) ?: [];
    $targetIps = $targetHost !== '' ? (gethostbynamel($targetHost) ?: []) : [];
    if ($domainIps && $targetIps && array_intersect($domainIps, $targetIps)) return true;

    if (function_exists('dns_get_record')) {
        $txtRecords = @dns_get_record($domain, DNS_TXT) ?: [];
        foreach ($txtRecords as $record) {
            $txt = $record['txt'] ?? '';
            if (hash_equals('upscale-verification=' . $verificationToken, $txt)) return true;
        }
    }
    return false;
}

if ($request->method() === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM custom_domains WHERE workspace_id = ? ORDER BY is_primary DESC, id DESC");
    $stmt->execute([$targetWsId]);
    $domains = $stmt->fetchAll();
    foreach ($domains as &$domain) {
        $domain['id'] = (int)$domain['id'];
        $domain['workspace_id'] = (int)$domain['workspace_id'];
        $domain['is_primary'] = (bool)$domain['is_primary'];
        $domain['dns'] = domainDnsInstructions($domain['domain'], $config);
        $domain['verification_txt'] = 'upscale-verification=' . $domain['verification_token'];
    }
    JsonResponse::success(['domains' => $domains, 'dns' => domainDnsInstructions('', $config)]);
}

if ($request->method() === 'POST') {
    $input = $request->all();
    $action = $input['action'] ?? 'create';

    if ($action === 'create') {
        $domain = normalizeDomain((string)($input['domain'] ?? ''));
        if ($domain === '' || !filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            JsonResponse::error('Informe um domínio válido, sem caminhos.', 422);
        }
        if (in_array($domain, ['localhost', strtolower($config['base_domain'] ?? '')], true)) {
            JsonResponse::error('Este domínio é reservado pelo sistema.', 422);
        }
        $tokenValue = bin2hex(random_bytes(16));
        try {
            $pdo->beginTransaction();
            if (!empty($input['is_primary'])) {
                $pdo->prepare("UPDATE custom_domains SET is_primary = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ?")->execute([$targetWsId]);
            }
            $stmt = $pdo->prepare("INSERT INTO custom_domains (workspace_id, domain, verification_token, is_primary) VALUES (?, ?, ?, ?)");
            $stmt->execute([$targetWsId, $domain, $tokenValue, !empty($input['is_primary']) ? 1 : 0]);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            JsonResponse::error('Este domínio já está cadastrado.', 409);
        }
        JsonResponse::success([
            'message' => 'Domínio adicionado. Configure o DNS e clique em verificar.',
            'domain_id' => (int)$pdo->lastInsertId(),
            'domain' => $domain,
            'dns' => domainDnsInstructions($domain, $config),
            'verification_txt' => 'upscale-verification=' . $tokenValue,
        ], 201);
    }

    $domainId = (int)($input['domain_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM custom_domains WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$domainId, $targetWsId]);
    $domain = $stmt->fetch();
    if (!$domain) JsonResponse::error('Domínio não encontrado.', 404);

    if ($action === 'verify') {
        $active = domainIsPointed($domain['domain'], $config, $domain['verification_token']);
        $status = $active ? 'active' : 'pending';
        $pdo->prepare("UPDATE custom_domains SET status = ?, last_checked_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$status, $domainId]);
        if ($active && !empty($domain['is_primary'])) {
            $pdo->prepare("UPDATE workspaces SET custom_domain = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$domain['domain'], $targetWsId]);
        }
        JsonResponse::success([
            'verified' => $active,
            'status' => $status,
            'message' => $active ? 'Domínio conectado com sucesso.' : 'DNS ainda não propagou. Tente novamente em alguns minutos.',
        ]);
    }

    if ($action === 'primary') {
        $pdo->prepare("UPDATE custom_domains SET is_primary = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ?")->execute([$targetWsId]);
        $pdo->prepare("UPDATE custom_domains SET is_primary = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$domainId]);
        if ($domain['status'] === 'active') {
            $pdo->prepare("UPDATE workspaces SET custom_domain = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$domain['domain'], $targetWsId]);
        }
        JsonResponse::success(['message' => 'Domínio principal atualizado.']);
    }

    JsonResponse::error('Ação inválida.', 422);
}

if ($request->method() === 'DELETE') {
    $input = $request->all();
    $domainId = (int)($input['domain_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT domain, is_primary FROM custom_domains WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$domainId, $targetWsId]);
    $domain = $stmt->fetch();
    if (!$domain) JsonResponse::error('Domínio não encontrado.', 404);
    $pdo->prepare("DELETE FROM custom_domains WHERE id = ? AND workspace_id = ?")->execute([$domainId, $targetWsId]);
    if (!empty($domain['is_primary'])) {
        $pdo->prepare("UPDATE workspaces SET custom_domain = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND custom_domain = ?")
            ->execute([$targetWsId, $domain['domain']]);
    }
    JsonResponse::success(['message' => 'Domínio removido.']);
}

JsonResponse::error('Method not allowed', 405);
