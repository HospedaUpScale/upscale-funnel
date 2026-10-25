<?php

namespace App\Support;

use PDO;

/**
 * Pix de teste por workspace: enquanto ligado, a janela do Pix da oferta mostra o botão
 * "Simular pagamento aprovado", que liquida o Pix pelo mesmo caminho de um pagamento real
 * (cotas, eventos e aviso upsell.quotas_paid para a rifa). Desliga sozinho depois de HOURS horas.
 */
class PixTestMode
{
    public const HOURS = 24;

    /** Horário em que o modo de teste desliga, ou null se estiver desligado. */
    public static function until(PDO $pdo, int $workspaceId): ?string
    {
        try {
            $st = $pdo->prepare('SELECT pix_test_until FROM workspaces WHERE id = ?');
            $st->execute([$workspaceId]);
            $until = $st->fetchColumn();
        } catch (\PDOException $e) {
            return null; // banco ainda sem a migração 0032
        }
        if (!$until || strtotime((string)$until) <= time()) {
            return null;
        }
        return (string)$until;
    }

    public static function active(PDO $pdo, int $workspaceId): bool
    {
        return self::until($pdo, $workspaceId) !== null;
    }

    /** Liga por HOURS horas e devolve o horário em que desliga. */
    public static function enable(PDO $pdo, int $workspaceId): string
    {
        $until = date('Y-m-d H:i:s', time() + self::HOURS * 3600);
        $pdo->prepare('UPDATE workspaces SET pix_test_until = ? WHERE id = ?')->execute([$until, $workspaceId]);
        return $until;
    }

    public static function disable(PDO $pdo, int $workspaceId): void
    {
        $pdo->prepare('UPDATE workspaces SET pix_test_until = NULL WHERE id = ?')->execute([$workspaceId]);
    }

    /** Pix gerado pelo gateway de demonstração (sem cobrança real que o gateway confirme sozinho). */
    public static function isTestTransaction(array $transaction): bool
    {
        $gateway = strtolower((string)($transaction['gateway'] ?? ''));
        return in_array($gateway, ['', 'mock', 'sandbox', 'teste'], true)
            || stripos((string)($transaction['pix_copy_paste'] ?? ''), 'UPSCALE SANDBOX') !== false;
    }
}
