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

$allowedWorkspaceIds = [];
if ($auth && $auth['role'] !== 'super_admin') {
    $allowedWorkspaceIds = [$auth['workspace']->id];
    foreach ($wsRepo->findDescendants($auth['workspace']->id) as $descendant) {
        $allowedWorkspaceIds[] = $descendant->id;
    }
    if (!in_array($targetWsId, $allowedWorkspaceIds, true)) {
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
    $serverHost = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
    $targetHost = strtolower($config['base_domain'] ?? '');
    if ($targetHost === '' || $targetHost === 'seudominio.com.br' || $targetHost === 'vendedorsincero.pro') {
        $targetHost = $serverHost ?: 'upscalefunnel.site';
    }

    $targetIp = '179.198.96.35'; // IP oficial da VPS
    $resolvedTarget = gethostbyname($targetHost);
    if ($resolvedTarget !== $targetHost && filter_var($resolvedTarget, FILTER_VALIDATE_IP)) {
        $targetIp = $resolvedTarget;
    }

    $fallbackCname = 'cname.' . $targetHost;

    return [
        'target_hostname' => $targetHost,
        'target_ip'       => $targetIp,
        'fallback_cname'  => $fallbackCname,
        'records' => [
            ['type' => 'A', 'name' => '@', 'value' => $targetIp, 'recommended_for' => 'Apontamento Principal (VPS/Easypanel)'],
            ['type' => 'CNAME', 'name' => 'www', 'value' => $targetHost, 'recommended_for' => 'Subdomínio www direto'],
            ['type' => 'CNAME', 'name' => 'oferta', 'value' => $fallbackCname, 'recommended_for' => 'Cloudflare for SaaS (Opcional)'],
        ],
    ];
}

function checkDomainDns(string $domain, array $config, string $verificationToken): array
{
    $domain = normalizeDomain($domain);
    if ($domain === '') {
        return [
            'is_pointed'   => false,
            'current_ips'  => [],
            'expected_ips' => ['179.198.96.35'],
            'message'      => 'Domínio inválido.',
        ];
    }

    $knownIps = ['179.198.96.35'];

    $serverHost = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
    if ($serverHost !== '') {
        $hostIps = gethostbynamel($serverHost) ?: [];
        $knownIps = array_merge($knownIps, $hostIps);
    }

    $targetHost = strtolower($config['base_domain'] ?? '');
    if ($targetHost !== '') {
        $hostIps = gethostbynamel($targetHost) ?: [];
        $knownIps = array_merge($knownIps, $hostIps);
    }

    $upscaleIps = gethostbynamel('upscalefunnel.site') ?: [];
    $knownIps = array_merge($knownIps, $upscaleIps);

    if (!empty($_SERVER['SERVER_ADDR']) && filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP)) {
        $knownIps[] = $_SERVER['SERVER_ADDR'];
    }

    $knownIps = array_values(array_unique(array_filter($knownIps)));

    // 1. Verificar registros A (IP)
    $domainIps = gethostbynamel($domain) ?: [];
    if (!empty($domainIps) && !empty($knownIps)) {
        if (count(array_intersect($domainIps, $knownIps)) > 0) {
            return [
                'is_pointed'   => true,
                'method'       => 'ip_match',
                'current_ips'  => $domainIps,
                'expected_ips' => $knownIps,
                'message'      => 'Apontamento DNS Tipo A confirmado com sucesso!',
            ];
        }
    }

    // 2. Verificar CNAME
    if (function_exists('dns_get_record')) {
        $cnameRecords = @dns_get_record($domain, DNS_CNAME) ?: [];
        $allowedTargets = array_unique(array_filter([
            $serverHost,
            $targetHost,
            'cname.' . $serverHost,
            'cname.' . $targetHost,
            'upscalefunnel.site',
            'cname.upscalefunnel.site'
        ]));

        foreach ($cnameRecords as $record) {
            $target = strtolower(rtrim($record['target'] ?? '', '.'));
            foreach ($allowedTargets as $allowed) {
                if ($target === $allowed || str_ends_with($target, '.' . $allowed)) {
                    return [
                        'is_pointed'   => true,
                        'method'       => 'cname_match',
                        'cname'        => $target,
                        'current_ips'  => $domainIps,
                        'expected_ips' => $knownIps,
                        'message'      => 'Apontamento DNS CNAME confirmado com sucesso!',
                    ];
                }
            }
        }

        // 3. Verificar TXT
        if ($verificationToken !== '') {
            $txtRecords = @dns_get_record($domain, DNS_TXT) ?: [];
            foreach ($txtRecords as $record) {
                $txt = $record['txt'] ?? ($record['entries'][0] ?? '');
                if (hash_equals('upscale-verification=' . $verificationToken, $txt)) {
                    return [
                        'is_pointed'   => true,
                        'method'       => 'txt_match',
                        'current_ips'  => $domainIps,
                        'expected_ips' => $knownIps,
                        'message'      => 'Verificação TXT confirmada com sucesso!',
                    ];
                }
            }
        }
    }

    $currentIpText = !empty($domainIps) ? implode(', ', $domainIps) : 'Nenhum IP encontrado';
    return [
        'is_pointed'   => false,
        'current_ips'  => $domainIps,
        'expected_ips' => $knownIps,
        'message'      => "DNS ainda não propagou. IP atual: {$currentIpText}. Esperado: 179.198.96.35",
    ];
}

/**
 * Domínio pronto para os cards: o DNS aponta para o upScale E o endereço abre o upScale.
 * Um domínio que responde por outro site (a própria rifa, por exemplo) não conta, mesmo respondendo:
 * os cards sairiam nele e dariam 404.
 */
function checkDomainPointing(string $domain, array $config, string $verificationToken): array
{
    $dns = checkDomainDns($domain, $config, $verificationToken);
    $domain = normalizeDomain($domain);
    if ($domain === '') return $dns;

    $probe = \App\Support\DomainProbe::probe($domain);
    if ($probe['result'] === \App\Support\DomainProbe::UPSCALE) {
        return array_merge($dns, [
            'is_pointed' => true,
            'method'     => 'upscale_probe',
            'message'    => 'Domínio conectado: ' . $domain . ' abre o upScale.',
        ]);
    }
    if ($probe['result'] === \App\Support\DomainProbe::OTHER) {
        $expected = $dns['expected_ips'][0] ?? '179.198.96.35';
        $ips = !empty($dns['current_ips']) ? implode(', ', $dns['current_ips']) : 'sem IP';
        $message = !empty($dns['is_pointed'])
            ? "O DNS de {$domain} já aponta para o servidor, mas o endereço ainda abre outro site (HTTP {$probe['code']}). Adicione {$domain} ao app do upScale no Easypanel e verifique de novo."
            : "{$domain} abre outro site (IP {$ips}), não o upScale: os cards dariam 404 nesse endereço. Use um endereço só para o upScale, por exemplo " . suggestedOfferSubdomain($domain) . ", apontado para {$expected}.";
        return array_merge($dns, [
            'is_pointed' => false,
            'method'     => 'other_site',
            'message'    => $message,
        ]);
    }
    // Sem resposta conclusiva (certificado ainda não emitido, por exemplo): vale o DNS.
    return $dns;
}

/** Sugestão de subdomínio para o upScale: rifa.cliente.com → oferta.cliente.com; cliente.com.br → oferta.cliente.com.br. */
function suggestedOfferSubdomain(string $domain): string
{
    $parts = explode('.', $domain);
    if (count($parts) >= 3) {
        $rest = implode('.', array_slice($parts, 1));
        if (!preg_match('/^(com|net|org|gov|edu|co|art|blog|app)\.[a-z]{2}$/', $rest)) {
            return 'oferta.' . $rest;
        }
    }
    return 'oferta.' . $domain;
}

function domainIsPointed(string $domain, array $config, string $verificationToken): bool
{
    $res = checkDomainPointing($domain, $config, $verificationToken);
    return !empty($res['is_pointed']);
}

/**
 * Domínio do mesmo workspace do qual $domain é subdomínio (o mais específico), ou null.
 */
function findParentDomain(PDO $pdo, int $wsId, string $domain): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM custom_domains WHERE workspace_id = ?");
    $stmt->execute([$wsId]);
    $best = null;
    foreach ($stmt->fetchAll() as $row) {
        $root = (string)$row['domain'];
        if ($root !== $domain && str_ends_with($domain, '.' . $root)) {
            if (!$best || strlen($root) > strlen($best['domain'])) $best = $row;
        }
    }
    return $best;
}

/** Instruções de DNS de um subdomínio: CNAME do rótulo para o host da plataforma. */
function subdomainDnsInstructions(string $domain, string $parent, array $config): array
{
    $base = domainDnsInstructions($domain, $config);
    $label = substr($domain, 0, -strlen('.' . $parent));
    $base['records'] = [
        ['type' => 'CNAME', 'name' => $label, 'value' => $base['target_hostname'], 'recommended_for' => 'Subdomínio (recomendado)'],
        ['type' => 'A', 'name' => $label, 'value' => $base['target_ip'], 'recommended_for' => 'Alternativa se o provedor não aceitar CNAME'],
    ];
    return $base;
}

/** Torna um domínio raiz o domínio oficial do workspace (só um oficial por workspace). */
function makeOfficialDomain(PDO $pdo, int $wsId, int $domainId): void
{
    $pdo->prepare("UPDATE custom_domains SET is_primary = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ?")->execute([$wsId]);
    $pdo->prepare("UPDATE custom_domains SET is_primary = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$domainId]);
    // O workspace passa a responder no domínio oficial assim que o DNS dele estiver confirmado.
    $row = $pdo->prepare("SELECT domain, status FROM custom_domains WHERE id = ?");
    $row->execute([$domainId]);
    $d = $row->fetch();
    if ($d && $d['status'] === 'active') {
        $pdo->prepare("UPDATE workspaces SET custom_domain = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$d['domain'], $wsId]);
    }
}

if ($request->method() === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM custom_domains WHERE workspace_id = ? ORDER BY is_primary DESC, id DESC");
    $stmt->execute([$targetWsId]);
    $domains = $stmt->fetchAll();
    $stmtWs = $pdo->prepare("SELECT custom_domain FROM workspaces WHERE id = ?");
    $stmtWs->execute([$targetWsId]);
    $currentWsDomain = (string)($stmtWs->fetchColumn() ?: '');

    $names = array_column($domains, 'domain');
    $official = null;
    foreach ($domains as &$domain) {
        $domain['id'] = (int)$domain['id'];
        $domain['workspace_id'] = (int)$domain['workspace_id'];
        $domain['is_primary'] = (bool)$domain['is_primary'];
        $domain['is_published_offer'] = ($domain['domain'] === $currentWsDomain);
        $parent = null;
        foreach ($names as $root) {
            if ($root !== $domain['domain'] && str_ends_with($domain['domain'], '.' . $root) && (!$parent || strlen($root) > strlen($parent))) {
                $parent = $root;
            }
        }
        $domain['kind'] = $parent ? 'subdomain' : 'root';
        $domain['parent_domain'] = $parent;
        $domain['is_official'] = $domain['is_primary'] && !$parent;
        if ($domain['is_official']) $official = $domain['domain'];
        $domain['dns'] = $parent ? subdomainDnsInstructions($domain['domain'], $parent, $config) : domainDnsInstructions($domain['domain'], $config);
        $domain['verification_txt'] = 'upscale-verification=' . $domain['verification_token'];
    }
    unset($domain);
    JsonResponse::success([
        'domains' => $domains,
        'published_domain' => $currentWsDomain,
        'official_domain' => $official,
        'dns' => domainDnsInstructions('', $config)
    ]);
}

function deleteWorkspaceDomain(PDO $pdo, int $wsId, int $domainId, array $domain): void
{
    $pdo->prepare("UPDATE funnels SET custom_domain_id = NULL, is_home = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ? AND custom_domain_id = ?")
        ->execute([$wsId, $domainId]);
    $pdo->prepare("DELETE FROM custom_domains WHERE id = ? AND workspace_id = ?")->execute([$domainId, $wsId]);
    $pdo->prepare("UPDATE workspaces SET custom_domain = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND custom_domain = ?")
        ->execute([$wsId, $domain['domain']]);
}

if ($request->method() === 'POST') {
    $input = $request->all();
    $action = $input['action'] ?? 'create';

    if ($action === 'test_dns' || $action === 'verify_test') {
        $domainStr = normalizeDomain((string)($input['domain'] ?? ''));
        if ($domainStr === '') {
            JsonResponse::error('Informe o domínio a ser testado.', 422);
        }
        $check = checkDomainPointing($domainStr, $config, '');
        JsonResponse::success([
            'verified'     => !empty($check['is_pointed']),
            'domain'       => $domainStr,
            'current_ips'  => $check['current_ips'] ?? [],
            'expected_ips' => $check['expected_ips'] ?? ['179.198.96.35'],
            'message'      => $check['message'],
        ]);
    }

    if ($action === 'create_subdomain') {
        $label = strtolower(trim((string)($input['subdomain'] ?? $input['label'] ?? '')));
        $label = trim($label, '.');
        if ($label === '' || !preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/', $label)) {
            JsonResponse::error('Informe só o começo do subdomínio, com letras, números e hífen (ex.: oferta).', 422);
        }
        if ($label === 'www') {
            JsonResponse::error('O www já aponta para o domínio oficial. Escolha outro nome (ex.: oferta, rifa).', 422);
        }
        $parentId = (int)($input['parent_domain_id'] ?? 0);
        $stmt = $parentId > 0
            ? $pdo->prepare("SELECT * FROM custom_domains WHERE id = ? AND workspace_id = ?")
            : $pdo->prepare("SELECT * FROM custom_domains WHERE workspace_id = ? AND is_primary = 1 LIMIT 1");
        $stmt->execute($parentId > 0 ? [$parentId, $targetWsId] : [$targetWsId]);
        $parentRow = $stmt->fetch();
        if (!$parentRow) {
            JsonResponse::error('Conecte primeiro o domínio oficial do workspace para criar subdomínios.', 422);
        }
        $input['domain'] = $label . '.' . $parentRow['domain'];
        $action = 'create';
    }

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

        // Domínio principal adicionado à conta vira o domínio oficial do workspace;
        // subdomínio de um domínio já conectado entra como subdomínio.
        $parentRow = findParentDomain($pdo, $targetWsId, $domain);
        $isOfficial = !$parentRow;
        $tokenValue = bin2hex(random_bytes(16));
        try {
            $pdo->beginTransaction();
            if ($isOfficial) {
                $pdo->prepare("UPDATE custom_domains SET is_primary = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ?")->execute([$targetWsId]);
            }
            $stmt = $pdo->prepare("INSERT INTO custom_domains (workspace_id, domain, verification_token, is_primary) VALUES (?, ?, ?, ?)");
            $stmt->execute([$targetWsId, $domain, $tokenValue, $isOfficial ? 1 : 0]);
            $newId = (int)$pdo->lastInsertId();
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ((string)$e->getCode() === '23000') {
                JsonResponse::error('Este domínio foi cadastrado por outra solicitação. Atualize a lista e tente novamente.', 409, ['code' => 'domain_conflict']);
            }
            JsonResponse::error('Não foi possível cadastrar o domínio agora.', 500, ['code' => 'domain_create_failed']);
        }
        JsonResponse::success([
            'message' => $isOfficial
                ? "Domínio {$domain} definido como domínio oficial do workspace. Configure o DNS e clique em verificar."
                : "Subdomínio {$domain} adicionado. Configure o DNS e clique em verificar.",
            'domain_id' => $newId,
            'domain' => $domain,
            'kind' => $isOfficial ? 'root' : 'subdomain',
            'parent_domain' => $parentRow['domain'] ?? null,
            'is_official' => $isOfficial,
            'dns' => $isOfficial ? domainDnsInstructions($domain, $config) : subdomainDnsInstructions($domain, $parentRow['domain'], $config),
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

    if ($action === 'assign_workspace') {
        $destinationWsId = (int)($input['target_workspace_id'] ?? 0);
        if ($destinationWsId <= 0) {
            JsonResponse::error('Selecione o cliente que receberá este domínio.', 422);
        }
        if ($auth && $auth['role'] !== 'super_admin' && !in_array($destinationWsId, $allowedWorkspaceIds, true)) {
            JsonResponse::error('Você não possui acesso ao cliente selecionado.', 403);
        }

        $destination = $wsRepo->findById($destinationWsId);
        if (!$destination) {
            JsonResponse::error('Cliente de destino não encontrado.', 404);
        }
        if ($destinationWsId === $targetWsId) {
            JsonResponse::success([
                'message' => "O domínio {$domain['domain']} já está vinculado a {$destination->name}.",
                'domain_id' => (int)$domain['id'],
                'workspace_id' => $destinationWsId,
            ]);
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE funnels SET custom_domain_id = NULL, is_home = 0, updated_at = CURRENT_TIMESTAMP WHERE workspace_id = ? AND custom_domain_id = ?")
                ->execute([$targetWsId, $domainId]);
            $pdo->prepare("UPDATE workspaces SET custom_domain = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND custom_domain = ?")
                ->execute([$targetWsId, $domain['domain']]);
            $pdo->prepare("UPDATE custom_domains SET workspace_id = ?, is_primary = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$destinationWsId, $domainId]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            JsonResponse::error('Não foi possível vincular o domínio ao cliente selecionado.', 500);
        }

        JsonResponse::success([
            'message' => "Domínio {$domain['domain']} vinculado ao cliente {$destination->name}.",
            'domain_id' => (int)$domain['id'],
            'workspace_id' => $destinationWsId,
            'workspace_name' => $destination->name,
        ]);
    }

    if ($action === 'verify') {
        $check = checkDomainPointing($domain['domain'], $config, $domain['verification_token'] ?? '');
        $active = !empty($check['is_pointed']);
        $status = $active ? 'active' : 'pending';
        $pdo->prepare("UPDATE custom_domains SET status = ?, last_checked_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$status, $domainId]);
        if ($active && !empty($domain['is_primary'])) {
            $pdo->prepare("UPDATE workspaces SET custom_domain = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$domain['domain'], $targetWsId]);
        }
        JsonResponse::success([
            'verified'     => $active,
            'status'       => $status,
            'domain'       => $domain['domain'],
            'current_ips'  => $check['current_ips'] ?? [],
            'expected_ips' => $check['expected_ips'] ?? ['179.198.96.35'],
            'message'      => $check['message'] ?? ($active ? 'Domínio conectado com sucesso.' : 'DNS ainda não propagou. Tente novamente em alguns minutos.'),
        ]);
    }

    if ($action === 'primary' || $action === 'make_official') {
        if (findParentDomain($pdo, $targetWsId, $domain['domain'])) {
            JsonResponse::error('Um subdomínio não pode ser o domínio oficial. Escolha o domínio principal.', 422);
        }
        makeOfficialDomain($pdo, $targetWsId, $domainId);
        JsonResponse::success(['message' => "{$domain['domain']} agora é o domínio oficial do workspace."]);
    }

    if ($action === 'delete') {
        deleteWorkspaceDomain($pdo, $targetWsId, $domainId, $domain);
        JsonResponse::success(['message' => 'Domínio removido.']);
    }

    JsonResponse::error('Ação inválida.', 422);
}

if ($request->method() === 'DELETE') {
    $input = $request->all();
    $domainId = (int)($input['domain_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM custom_domains WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$domainId, $targetWsId]);
    $domain = $stmt->fetch();
    if (!$domain) JsonResponse::error('Domínio não encontrado.', 404);
    deleteWorkspaceDomain($pdo, $targetWsId, $domainId, $domain);
    JsonResponse::success(['message' => 'Domínio removido.']);
}

JsonResponse::error('Method not allowed', 405);
