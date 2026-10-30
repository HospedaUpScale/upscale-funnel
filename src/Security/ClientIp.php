<?php

namespace App\Security;

/**
 * IP de quem fez a requisição, sem confiar em cabeçalho que o próprio cliente escolhe.
 *
 * Conexão direta (REMOTE_ADDR público): vale o REMOTE_ADDR; X-Forwarded-For/CF-Connecting-IP são ignorados.
 * Atrás de proxy na rede interna (Easypanel/Traefik): vale o último IP público do X-Forwarded-For,
 * que é o que o proxy acrescentou (o cliente só controla o começo da lista).
 */
class ClientIp
{
    public static function get(): string
    {
        $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote === '' || !filter_var($remote, FILTER_VALIDATE_IP)) {
            return '0.0.0.0';
        }
        if (!self::isInternal($remote)) {
            return $remote;
        }
        $chain = array_map('trim', explode(',', (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')));
        for ($i = count($chain) - 1; $i >= 0; $i--) {
            $ip = $chain[$i];
            if (filter_var($ip, FILTER_VALIDATE_IP) && !self::isInternal($ip)) {
                return $ip;
            }
        }
        return $remote;
    }

    public static function isInternal(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
