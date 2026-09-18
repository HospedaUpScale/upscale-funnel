<?php

namespace App\Auth;

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

        return null;
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

        return $headers['X-Admin-Token'] 
            ?? $headers['x-admin-token'] 
            ?? $_GET['admin_token'] 
            ?? $_GET['token'] 
            ?? null;
    }
}
