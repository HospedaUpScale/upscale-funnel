<?php

namespace App\Support;

use App\Database\Connection;
use PDO;

/**
 * Histórico de tudo que é criado, alterado ou apagado pelo painel.
 *
 * Funciona sozinho: AdminAuth registra quem está logado e JsonResponse avisa quando a
 * resposta sai. Só ações de escrita bem-sucedidas em /api/admin/ entram no histórico.
 * Salvamentos repetidos do mesmo item pela mesma pessoa em até 10 minutos viram uma
 * linha só (com contador), para o autosave do editor não encher a lista.
 *
 * Quem pode ver: apenas o dono da conta (veja isOwner()).
 */
class AuditLog
{
    private static ?array $actor = null;
    private static bool $done = false;

    /** Ações que usam POST mas só leem dados. */
    private const READ_ONLY_ACTIONS = [
        'check_slug', 'test_dns', 'verify_test', 'load', 'load-campaign', 'load-template', 'list-templates',
        'get_offers', 'get_templates', 'get_template_defaults', 'list', 'list_all', 'preview', 'generate',
    ];

    /** Endpoints que não entram no histórico. */
    private const IGNORED_ENDPOINTS = ['login', 'logout', 'auth-status', 'ai-copy', 'metrics', 'logs', 'audit-log', 'partner-metrics', 'webhook-info'];

    private const ENTITY_LABELS = [
        'campaigns' => 'Campanha', 'campaign-identity' => 'Identidade visual', 'offer' => 'Oferta',
        'funnels' => 'Página', 'visual-editor' => 'Página (editor visual)', 'domains' => 'Domínio',
        'users' => 'Usuário', 'workspaces' => 'Workspace', 'upload' => 'Imagem',
    ];

    private const ACTION_LABELS = [
        'create' => 'criou', 'create_global' => 'criou', 'create_user' => 'criou', 'create_subdomain' => 'criou o subdomínio',
        'save' => 'salvou', 'save_draft' => 'salvou o rascunho', 'save-draft' => 'salvou o rascunho', 'save-template' => 'salvou o modelo',
        'update' => 'alterou', 'update_global' => 'alterou', 'update_user' => 'alterou',
        'publish' => 'publicou', 'publish_offer' => 'publicou a oferta no domínio', 'publish_domain' => 'publicou no domínio',
        'unpublish' => 'despublicou', 'unpublish_offer' => 'tirou a oferta do domínio', 'reset_default_domain' => 'voltou ao domínio padrão',
        'finalize' => 'finalizou', 'duplicate' => 'duplicou', 'delete' => 'apagou', 'delete_global' => 'apagou', 'delete_user' => 'apagou',
        'delete-template' => 'apagou o modelo', 'revoke' => 'removeu o acesso de', 'set_domain' => 'trocou o domínio de',
        'make_official' => 'tornou oficial', 'primary' => 'tornou oficial', 'verify' => 'verificou', 'assign_workspace' => 'moveu',
        'apply' => 'aplicou', 'upload' => 'enviou', 'access' => 'criou ou redefiniu o acesso de', 'status' => 'mudou o status de',
    ];

    private const SECRET_KEYS = '/pass|senha|token|secret|credential|api_key|apikey|cpf|card/i';

    public static function setActor(array $auth): void
    {
        self::$actor = $auth;
    }

    /** Chamado por JsonResponse::send antes de responder. Nunca interrompe a resposta. */
    public static function capture(int $status, mixed $data): void
    {
        if (self::$done) return;
        self::$done = true;
        try {
            self::record($status, is_array($data) ? $data : []);
        } catch (\Throwable $e) {
            // O histórico nunca pode quebrar o painel.
        }
    }

    private static function record(int $status, array $data): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) || $status >= 400) return;
        if (($data['status'] ?? 'success') === 'error') return;

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        if (!preg_match('#/api/admin/([a-z0-9-]+)\.php$#i', $path, $m)) return;
        $endpoint = strtolower($m[1]);
        if (in_array($endpoint, self::IGNORED_ENDPOINTS, true)) return;

        $input = self::input();
        $action = (string)($input['action'] ?? ($_GET['action'] ?? ''));
        if ($action === '') {
            $action = match ($method) { 'DELETE' => 'delete', 'PUT', 'PATCH' => 'update', default => $endpoint === 'upload' ? 'upload' : ($endpoint === 'users' ? 'access' : 'save') };
        }
        if (in_array($action, self::READ_ONLY_ACTIONS, true)) return;

        $pdo = Connection::make();
        $actor = self::describeActor($pdo);
        [$entityId, $label] = self::entityRef($endpoint, $input, $data);
        $wsId = self::workspaceId($input, $data);
        $wsName = null;
        if ($wsId) {
            $st = $pdo->prepare('SELECT name FROM workspaces WHERE id = ?');
            $st->execute([$wsId]);
            $wsName = $st->fetchColumn() ?: null;
        }

        $entityName = self::ENTITY_LABELS[$endpoint] ?? ucfirst($endpoint);
        $verb = self::ACTION_LABELS[$action] ?? str_replace(['_', '-'], ' ', $action);
        $details = [];
        $summary = trim(($actor['name'] ?: 'Alguém') . ' ' . $verb . ' ' . mb_strtolower($entityName) . ($label ? ' "' . $label . '"' : ($entityId ? ' #' . $entityId : '')));
        if (!empty($data['message']) && is_string($data['message'])) {
            $details['mensagem'] = mb_substr($data['message'], 0, 300);
        }
        $details['dados'] = self::sanitize($input);
        $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($detailsJson) > 20000) $detailsJson = substr($detailsJson, 0, 20000);

        // Mesmo item salvo de novo pela mesma pessoa em até 10 min: atualiza a linha existente.
        $since = date('Y-m-d H:i:s', time() - 600);
        $actorCond = $actor['id'] ? 'actor_user_id = ?' : 'actor_user_id IS NULL';
        $st = $pdo->prepare('SELECT id FROM audit_log WHERE entity = ? AND action = ? AND entity_id = ? AND ' . $actorCond . ' AND updated_at >= ? ORDER BY id DESC LIMIT 1');
        $st->execute(array_values(array_filter([$endpoint, $action, (string)$entityId, $actor['id'] ?: null, $since], fn($v) => $v !== null)));
        if ($entityId !== null && ($rowId = $st->fetchColumn())) {
            $pdo->prepare('UPDATE audit_log SET updated_at = ?, repeat_count = repeat_count + 1, summary = ?, details = ?, entity_label = ? WHERE id = ?')
                ->execute([date('Y-m-d H:i:s'), $summary, $detailsJson, $label, $rowId]);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO audit_log (created_at, updated_at, actor_user_id, actor_name, actor_email, actor_role, workspace_id, workspace_name, entity, action, entity_id, entity_label, summary, details, ip)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$now, $now, $actor['id'], $actor['name'], $actor['email'], $actor['role'], $wsId, $wsName, $endpoint, $action,
                $entityId !== null ? (string)$entityId : null, $label, mb_substr($summary, 0, 500), $detailsJson, self::ip()]);
    }

    /**
     * Dono da conta: o login master das variáveis de ambiente (ADMIN_USER/ADMIN_EMAIL),
     * o usuário com o e-mail ADMIN_EMAIL e quem estiver em OWNER_EMAILS (separados por vírgula).
     * Outros Super admins não veem o histórico.
     */
    public static function isOwner(?array $auth, array $config, PDO $pdo): bool
    {
        if (!$auth || ($auth['role'] ?? '') !== 'super_admin') return false;
        if (!array_key_exists('user_id', $auth)) return true; // SUPER_ADMIN_TOKEN do ambiente
        $userId = (int)$auth['user_id'];
        if ($userId === 0) return true; // login master com a senha do ambiente
        $owners = array_filter(array_map(fn($e) => strtolower(trim($e)), array_merge(
            [(string)($config['admin_email'] ?? '')],
            explode(',', (string)(getenv('OWNER_EMAILS') ?: ($_ENV['OWNER_EMAILS'] ?? '')))
        )));
        if (!$owners) return false;
        $st = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $st->execute([$userId]);
        $email = strtolower((string)$st->fetchColumn());
        return $email !== '' && in_array($email, $owners, true);
    }

    private static function describeActor(PDO $pdo): array
    {
        $a = self::$actor ?? [];
        $role = $a['role'] ?? null;
        $userId = array_key_exists('user_id', $a) ? (int)$a['user_id'] : null;
        $name = null; $email = null;
        if ($userId) {
            $st = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
            $st->execute([$userId]);
            if ($u = $st->fetch(PDO::FETCH_ASSOC)) { $name = $u['name']; $email = $u['email']; }
        } elseif ($role === 'super_admin') {
            $name = 'Master (conta principal)';
        } elseif (!empty($a['workspace'])) {
            $name = 'Token do workspace ' . $a['workspace']->name;
        }
        if (!self::$actor) $name = 'Ambiente local';
        return ['id' => $userId ?: null, 'name' => $name, 'email' => $email, 'role' => $role];
    }

    private static function entityRef(string $endpoint, array $in, array $data): array
    {
        $id = null;
        foreach (['user_id', 'campaign_id', 'offer_id', 'funnel_id', 'domain_id', 'workspace_id', 'id'] as $k) {
            $preferred = ['users' => 'user_id', 'campaigns' => 'campaign_id', 'campaign-identity' => 'campaign_id', 'offer' => 'offer_id',
                'funnels' => 'funnel_id', 'visual-editor' => 'funnel_id', 'domains' => 'domain_id', 'workspaces' => 'workspace_id'][$endpoint] ?? null;
            if ($preferred && $k !== $preferred && $k !== 'id') continue;
            foreach ([$data, $data['user'] ?? [], $data['workspace'] ?? [], $data['domain'] ?? [], $data['offer'] ?? [], $data['access'] ?? [], $in] as $src) {
                if (is_array($src) && !empty($src[$k]) && is_scalar($src[$k])) { $id = (string)$src[$k]; break 2; }
            }
        }
        $label = null;
        foreach (['title', 'name', 'domain', 'email', 'slug', 'filename'] as $k) {
            foreach ([$in, $data['user'] ?? [], $data['workspace'] ?? [], $data['domain'] ?? [], $data] as $src) {
                if (is_array($src) && !empty($src[$k]) && is_string($src[$k])) { $label = mb_substr(trim($src[$k]), 0, 200); break 2; }
            }
        }
        if ($endpoint === 'campaign-identity' && !empty($data['identity']['name'])) $label = 'tema ' . $data['identity']['name'];
        return [$id, $label];
    }

    private static function workspaceId(array $in, array $data): ?int
    {
        foreach ([$in['workspace_id'] ?? null, $data['workspace_id'] ?? null] as $v) {
            if (is_numeric($v) && (int)$v > 0) return (int)$v;
        }
        $a = self::$actor;
        return (!empty($a['workspace']) && isset($a['workspace']->id)) ? (int)$a['workspace']->id : null;
    }

    private static function input(): array
    {
        $in = $_POST;
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) $in = array_merge($in, $json);
        }
        return $in;
    }

    private static function sanitize(mixed $v, int $depth = 0): mixed
    {
        if ($depth > 4) return '…';
        if (is_array($v)) {
            $out = [];
            $n = 0;
            foreach ($v as $k => $item) {
                if (++$n > 40) { $out['…'] = 'mais itens omitidos'; break; }
                if (is_string($k) && preg_match(self::SECRET_KEYS, $k)) { $out[$k] = '•••'; continue; }
                if (is_string($k) && preg_match('/html|content|css|blocks|base64/i', $k) && is_string($item) && strlen($item) > 200) {
                    $out[$k] = '[conteúdo da página, ' . number_format(strlen($item) / 1024, 1, ',', '.') . ' KB]';
                    continue;
                }
                $out[$k] = self::sanitize($item, $depth + 1);
            }
            return $out;
        }
        if (is_string($v) && mb_strlen($v) > 300) return mb_substr($v, 0, 300) . '…';
        return $v;
    }

    private static function ip(): ?string
    {
        $ip = \App\Security\ClientIp::get();
        $ip = trim(explode(',', $ip)[0]);
        return $ip !== '' ? mb_substr($ip, 0, 64) : null;
    }
}
