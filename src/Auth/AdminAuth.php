<?php

namespace App\Auth;

use App\Database\Connection;
use App\Repositories\WorkspaceRepository;
use App\Tenancy\Workspace;

class AdminAuth
{
    public function __construct(
        private WorkspaceRepository $workspaceRepo,
        private string $superAdminToken
    ) {}

    public function authenticate(?string $token): ?array
    {
        $auth = $this->resolve($token);
        if ($auth) \App\Support\AuditLog::setActor($auth);
        return $auth;
    }

    private function resolve(?string $token): ?array
    {
        if (empty($token)) {
            return null;
        }

        $token = trim($token);

        // 1. Super Admin check
        if (!empty($this->superAdminToken) && hash_equals($this->superAdminToken, $token)) {
            return [
                'role' => 'super_admin',
                'workspace' => null
            ];
        }

        // 2. Workspace Admin check by SHA256 hash
        $hash = hash('sha256', $token);
        $ws = $this->workspaceRepo->findByAdminTokenHash($hash);
        if ($ws) {
            return [
                'role' => ($ws->type === 'partner_whitelabel') ? 'partner_admin' : 'merchant_admin',
                'workspace' => $ws
            ];
        }

        // 3. Sessão de login de cliente (e-mail + senha)
        try {
            $session = (new AuthSessionRepository(Connection::make()))->find($token);
        } catch (\Throwable $e) {
            $session = null;
        }
        // A sessão só vale enquanto o usuário existir e mantiver o acesso que a criou
        // (usuário excluído ou sem o vínculo perde o acesso na hora, não só quando a sessão expira).
        if ($session && !$this->sessionStillAllowed($session)) {
            $session = null;
        }
        if ($session) {
            // Sessão master (login por usuário/senha): não depende de workspace.
            if (($session['role'] ?? '') === 'super_admin') {
                return [
                    'role' => 'super_admin',
                    'workspace' => null,
                    'user_id' => (int) $session['user_id'],
                ];
            }

            $ws = $this->workspaceRepo->findById((int) $session['workspace_id']);
            if ($ws && $ws->status === 'active') {
                return [
                    'role' => ($ws->type === 'partner_whitelabel') ? 'partner_admin' : 'merchant_admin',
                    'workspace' => $ws,
                    'user_id' => (int) $session['user_id'],
                ];
            }
        }

        return null;
    }

    /** Confere no banco se o dono da sessão ainda tem o acesso registrado nela. */
    private function sessionStillAllowed(array $session): bool
    {
        $userId = (int) ($session['user_id'] ?? 0);
        $role = (string) ($session['role'] ?? '');
        // Login master do ambiente (ADMIN_USER/ADMIN_PASSWORD) não tem usuário no banco.
        if ($role === 'super_admin' && $userId === 0) {
            return true;
        }
        if ($userId <= 0) {
            return false;
        }
        try {
            $users = new \App\Repositories\UserRepository(Connection::make());
            $memberships = $users->activeMemberships($userId);
            if ($role === 'super_admin') {
                $hasAdminWorkspace = (bool) $users->adminWorkspaceId();
                foreach ($memberships as $m) {
                    if ($m['membership_role'] === 'super_admin' && (($m['workspace_type'] ?? '') === 'super_admin' || !$hasAdminWorkspace)) {
                        return true;
                    }
                }
                return false;
            }
            foreach ($memberships as $m) {
                if ((int) $m['workspace_id'] === (int) $session['workspace_id'] && $m['membership_role'] !== 'super_admin') {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }

    public static function extractBearerToken(): ?string
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] 
            ?? $headers['authorization'] 
            ?? $_SERVER['HTTP_AUTHORIZATION'] 
            ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // Só cabeçalho: token na URL fica em logs de acesso, histórico e Referer.
        return $headers['X-Admin-Token']
            ?? $headers['x-admin-token']
            ?? $_SERVER['HTTP_X_ADMIN_TOKEN']
            ?? null;
    }
}
