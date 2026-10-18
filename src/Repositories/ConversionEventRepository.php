<?php

namespace App\Repositories;

use App\Database\Connection;
use PDO;

class ConversionEventRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make();
    }

    /**
     * Grava um evento do funil. $context pode trazer offer_id, campaign_id e funnel_id
     * para os números separados por campanha e por página.
     */
    public function log(int $workspaceId, ?int $orderId, string $eventName, ?array $metadata = null, array $context = []): bool
    {
        $metaJson = $metadata ? json_encode($metadata) : null;
        $offerId = isset($context['offer_id']) ? (int)$context['offer_id'] ?: null : null;
        $campaignId = isset($context['campaign_id']) ? (int)$context['campaign_id'] ?: null : null;
        $funnelId = isset($context['funnel_id']) ? (int)$context['funnel_id'] ?: null : null;

        if ($offerId && !$campaignId) {
            $stmt = $this->pdo->prepare("SELECT campaign_id FROM upsell_offers WHERE id = ?");
            $stmt->execute([$offerId]);
            $campaignId = (int)($stmt->fetchColumn() ?: 0) ?: null;
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO conversion_events (workspace_id, order_id, event_name, metadata, offer_id, campaign_id, funnel_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$workspaceId, $orderId, $eventName, $metaJson, $offerId, $campaignId, $funnelId]);
        } catch (\PDOException $e) {
            // Banco ainda sem a migração 0027: grava sem o contexto.
            $stmt = $this->pdo->prepare("
                INSERT INTO conversion_events (workspace_id, order_id, event_name, metadata)
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$workspaceId, $orderId, $eventName, $metaJson]);
        }
    }
}
