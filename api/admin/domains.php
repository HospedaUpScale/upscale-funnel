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

    $fallbackCname = 'cname.' . $targetHost;

    return [
        'target_hostname' => $targetHost,
        'target_ip'       => $targetIp,
        'fallback_cname'  => $fallbackCname,
        'records' => [
            ['type' => 'CNAME', 'name' => 'oferta', 'value' => $fallbackCname, 'recommended_for' => 'Cloudflare for SaaS (SSL Automático Grátis)'],
            ['type' => 'A', 'name' => '@', 'value' => $targetIp, 'recommended_for' => 'Hostinger / DNS Direto'],
            ['type' => 'CNAME', 'name' => 'www', 'value' => $targetHost, 'recommended_for' => 'www direto'],
        ],
    ];
}

function domainIsPointed(string $domain, array $config, string $verificationToken): bool
{
    $targetHost = strtolower($config['base_domain'] ?? '');
    $fallbackCname = 'cname.' . $targetHost;
    $domainIps = gethostbynamel($domain) ?: [];
    $targetIps = $targetHost !== '' ? (gethostbynamel($targetHost) ?: []) : [];
    if ($domainIps && $targetIps && array_intersect($domainIps, $targetIps)) return true;

    if (function_exists('dns_get_record')) {
        $cnameRecords = @dns_get_record($domain, DNS_CNAME) ?: [];
        foreach ($cnameRecords as $record) {
            $target = strtolower(rtrim($record['target'] ?? '', '.'));
            if ($target === $targetHost || $target === $fallbackCname || str_ends_with($target, '.' . $targetHost)) {
                return true;
            }
        }

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
    $stmtWs = $pdo->prepare("SELECT custom_domain FROM workspaces WHERE id = ?");
    $stmtWs->execute([$targetWsId]);
    $currentWsDomain = (string)($stmtWs->fetchColumn() ?: '');

    foreach ($domains as &$domain) {
        $domain['id'] = (int)$domain['id'];
        $domain['workspace_id'] = (int)$domain['workspace_id'];
        $domain['is_primary'] = (bool)$domain['is_primary'];
        $domain['is_published_offer'] = ($domain['domain'] === $currentWsDomain);
        $domain['dns'] = domainDnsInstructions($domain['domain'], $config);
        $domain['verification_txt'] = 'upscale-verification=' . $domain['verification_token'];
    }
    JsonResponse::success([
        'domains' => $domains,
        'published_domain' => $currentWsDomain,
        'dns' => domainDnsInstructions('', $config)
    ]);
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

        $existingStmt = $pdo->prepare("SELECT * FROM custom_domains WHERE domain = ? LIMIT 1");
        $existingStmt->execute([$domain]);
        $existingDomain = $existingStmt->fetch();
        if ($existingDomain) {
            if ((int)$existingDomain['workspace_id'] !== $targetWsId) {
                JsonResponse::error(
                    'Este domínio já está vinculado a outro workspace. Remova o vínculo anterior antes de reutilizá-lo.',
                    409,
                    ['code' => 'domain_in_use']
                );
            }

            if (!empty($input['is_primary']) && empty($existingDomain['is_primary'])) {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE custom_domains SET is_primary = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ?")->execute([$targetWsId]);
                $pdo->prepare("UPDATE custom_domains SET is_primary = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([(int)$existingDomain['id']]);
                $pdo->commit();
                $existingDomain['is_primary'] = 1;
            }

            JsonResponse::success([
                'message' => 'Este domínio já estava cadastrado neste workspace e continua disponível para uso.',
                'domain_id' => (int)$existingDomain['id'],
                'domain' => $domain,
                'existing' => true,
                'domain_status' => $existingDomain['status'],
                'is_primary' => (bool)$existingDomain['is_primary'],
                'dns' => domainDnsInstructions($domain, $config),
                'verification_txt' => 'upscale-verification=' . $existingDomain['verification_token'],
            ]);
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
            if ((string)$e->getCode() === '23000') {
                JsonResponse::error('Este domínio foi cadastrado por outra solicitação. Atualize a lista e tente novamente.', 409, ['code' => 'domain_conflict']);
            }
            JsonResponse::error('Não foi possível cadastrar o domínio agora.', 500, ['code' => 'domain_create_failed']);
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
    $domainStr = normalizeDomain((string)($input['domain'] ?? ''));

    if ($action === 'unpublish_offer' || $action === 'reset_default_domain') {
        $pdo->prepare("UPDATE workspaces SET custom_domain = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$targetWsId]);
        JsonResponse::success([
            'message' => 'Oferta restaurada para a URL padrão do sistema.',
            'published_domain' => null,
            'public_url' => '',
            'embed_url' => '',
            'iframe_code' => ''
        ]);
    }

    if ($action === 'publish_offer' || $action === 'publish_domain') {
        $domain = null;
        if ($domainId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM custom_domains WHERE id = ? AND workspace_id = ?");
            $stmt->execute([$domainId, $targetWsId]);
            $domain = $stmt->fetch();
        } elseif ($domainStr !== '') {
            $stmt = $pdo->prepare("SELECT * FROM custom_domains WHERE domain = ? AND workspace_id = ?");
            $stmt->execute([$domainStr, $targetWsId]);
            $domain = $stmt->fetch();
            if (!$domain) {
                $tokenValue = bin2hex(random_bytes(16));
                $stmtIns = $pdo->prepare("INSERT INTO custom_domains (workspace_id, domain, verification_token, status, is_primary) VALUES (?, ?, ?, 'active', 1)");
                $stmtIns->execute([$targetWsId, $domainStr, $tokenValue]);
                $domainId = (int)$pdo->lastInsertId();
                $domain = ['id' => $domainId, 'domain' => $domainStr, 'workspace_id' => $targetWsId, 'status' => 'active'];
            } else {
                $domainId = (int)$domain['id'];
            }
        }

        if (!$domain) {
            JsonResponse::error('Selecione ou informe um domínio válido para publicar.', 422);
        }

        // 1. Marca esse domínio como primário e ativo
        $pdo->prepare("UPDATE custom_domains SET is_primary = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ?")->execute([$targetWsId]);
        $pdo->prepare("UPDATE custom_domains SET is_primary = 1, status = 'active', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$domainId]);

        // 2. Vincula no workspace como custom_domain oficial
        $pdo->prepare("UPDATE workspaces SET custom_domain = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$domain['domain'], $targetWsId]);

        // 3. Remove qualquer funil HTML que estivesse sobrepondo a raiz deste domínio
        $pdo->prepare("UPDATE funnels SET is_home = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ? AND custom_domain_id = ?")
            ->execute([$targetWsId, $domainId]);

        // 4. Garante que a oferta de upsell está ativa
        $pdo->prepare("UPDATE upsell_offers SET is_active = 1 WHERE workspace_id = ?")->execute([$targetWsId]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
            ? 'https' : 'http';
        $publicUrl = $scheme . '://' . $domain['domain'];

        JsonResponse::success([
            'message' => "Oferta Booster publicada com sucesso no domínio {$domain['domain']}!",
            'domain_id' => $domainId,
            'domain' => $domain['domain'],
            'public_url' => $publicUrl,
            'embed_url' => $publicUrl . '/?nome={{nome}}',
            'iframe_code' => '<iframe id="upscale-frame" src="' . $publicUrl . '/?nome={{nome}}" style="width:100%;border:0;height:820px;" scrolling="no"></iframe>'
        ]);
    }

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
