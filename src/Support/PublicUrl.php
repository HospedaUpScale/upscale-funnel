<?php

namespace App\Support;

use PDO;

/**
 * Endereço público do workspace usado nos links e iframes das ofertas.
 * Usa o domínio oficial do workspace quando o DNS dele já está ativo;
 * sem domínio oficial ativo, usa o endereço atual do painel.
 */
class PublicUrl
{
    public static function base(PDO $pdo, int $workspaceId): string
    {
        $domain = self::officialDomain($pdo, $workspaceId);
        return $domain !== null ? 'https://' . $domain : self::currentHost();
    }

    public static function officialDomain(PDO $pdo, int $workspaceId): ?string
    {
        if ($workspaceId <= 0) return null;
        try {
            $stmt = $pdo->prepare("SELECT domain FROM custom_domains WHERE workspace_id = ? AND is_primary = 1 AND status = 'active' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$workspaceId]);
            $domain = $stmt->fetchColumn();
            if (!$domain) {
                // Domínio gravado no workspace, desde que continue ativo.
                $stmt = $pdo->prepare("SELECT d.domain FROM workspaces w INNER JOIN custom_domains d ON d.workspace_id = w.id AND d.domain = w.custom_domain WHERE w.id = ? AND d.status = 'active' LIMIT 1");
                $stmt->execute([$workspaceId]);
                $domain = $stmt->fetchColumn();
            }
        } catch (\Throwable $e) {
            return null;
        }
        $domain = strtolower(trim((string)$domain));
        return $domain !== '' ? $domain : null;
    }

    public static function currentHost(): string
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        return ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000');
    }
}
