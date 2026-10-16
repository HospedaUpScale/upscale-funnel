<?php

namespace App\Security;

use App\Repositories\WorkspaceRepository;
use App\Tenancy\Workspace;

/**
 * Autenticação de chamadas máquina-a-máquina (webhooks de pagamento e de vendas).
 *
 * Aceita o token de API do workspace (comparado pelo hash SHA-256 salvo no banco)
 * ou, opcionalmente, um segredo global WEBHOOK_SECRET. O token pode vir em:
 *   - Authorization: Bearer <token>
 *   - X-Webhook-Token / X-Upscale-Token / X-Api-Token
 *   - ?token=<token> ou ?api_token=<token> (para gateways que não enviam headers customizados)
 */
class WebhookAuth
{
    public function __construct(
        private WorkspaceRepository $workspaceRepo,
        private string $globalSecret = ''
    ) {}

    public static function extractToken(): string
    {
        $auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
            return trim($m[1]);
        }

        foreach (['HTTP_X_WEBHOOK_TOKEN', 'HTTP_X_UPSCALE_TOKEN', 'HTTP_X_API_TOKEN'] as $header) {
            if (!empty($_SERVER[$header])) {
                return trim((string) $_SERVER[$header]);
            }
        }

        return trim((string) ($_GET['token'] ?? $_GET['api_token'] ?? ''));
    }

    public function isGlobalSecret(string $token): bool
    {
        return $token !== '' && $this->globalSecret !== '' && hash_equals($this->globalSecret, $token);
    }

    /** Workspace ativo dono do token de API, ou null. */
    public function workspaceForToken(string $token): ?Workspace
    {
        if ($token === '') {
            return null;
        }

        $ws = $this->workspaceRepo->findByApiTokenHash(hash('sha256', $token));
        if (!$ws || $ws->status !== 'active') {
            return null;
        }

        return $ws;
    }

    /** O token autoriza operar sobre o workspace informado? */
    public function authorizesWorkspace(string $token, int $workspaceId): bool
    {
        if ($this->isGlobalSecret($token)) {
            return true;
        }

        $ws = $this->workspaceForToken($token);
        return $ws !== null && $ws->id === $workspaceId;
    }
}
