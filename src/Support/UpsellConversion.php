<?php

namespace App\Support;

use PDO;

/**
 * "Converteu o upsell" = o Pix do upsell foi PAGO.
 *
 * Antes o card era marcado como convertido na hora em que o Pix era gerado, e a auditoria de ganhador
 * liberava o bônus para quem só tinha aberto o Pix. Agora a marcação acontece na liquidação e a
 * auditoria confere o pedido pago.
 */
class UpsellConversion
{
    /** Pedido do upsell pago ligado a este card (pelo upsell_order_id ou pelo pedido da rifa), ou null. */
    public static function paidOrder(PDO $pdo, array $card): ?array
    {
        $ext = (string)($card['external_order_id'] ?? '');
        try {
            $st = $pdo->prepare("SELECT * FROM orders WHERE workspace_id = ? AND status = 'paid' AND (id = ? OR (external_order_id = ? AND external_order_id <> '')) ORDER BY paid_at DESC, id DESC LIMIT 1");
            $st->execute([(int)$card['workspace_id'], (int)($card['upsell_order_id'] ?? 0), $ext]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /** Liquidação do Pix: marca como convertido o card da venda que originou o pedido. */
    public static function markPaid(PDO $pdo, array $order): void
    {
        $ws = (int)$order['workspace_id'];
        try {
            $st = $pdo->prepare('UPDATE order_access_tokens SET upsell_converted = 1 WHERE workspace_id = ? AND upsell_order_id = ?');
            $st->execute([$ws, (int)$order['id']]);
            $ext = (string)($order['external_order_id'] ?? '');
            // Pedido sem card ligado (aberto pelo número do pedido da rifa): marca a venda com esse número.
            if ($st->rowCount() === 0 && $ext !== '' && !preg_match('/^ord_[0-9a-f]+$/i', $ext)) {
                $pdo->prepare('UPDATE order_access_tokens SET upsell_converted = 1 WHERE workspace_id = ? AND external_order_id = ?')->execute([$ws, $ext]);
            }
        } catch (\PDOException $e) {
            // banco sem a tabela de tokens (instalação muito antiga): nada a marcar
        }
    }
}
