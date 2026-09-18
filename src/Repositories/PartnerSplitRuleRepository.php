<?php

namespace App\Repositories;

use App\Database\Connection;
use App\Tenancy\Workspace;
use PDO;

class PartnerSplitRuleRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::make();
    }

    public function resolveFor(Workspace $workspace): array
    {
        // 1. Specific merchant override rule
        $stmt = $this->pdo->prepare("
            SELECT * FROM partner_split_rules 
            WHERE merchant_workspace_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$workspace->id]);
        $rule = $stmt->fetch();
        if ($rule) {
            return $rule;
        }

        // 2. Partner default rule if this workspace has a parent partner
        if ($workspace->parentId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM partner_split_rules 
                WHERE partner_workspace_id = ? AND merchant_workspace_id IS NULL 
                LIMIT 1
            ");
            $stmt->execute([$workspace->parentId]);
            $rule = $stmt->fetch();
            if ($rule) {
                return $rule;
            }
        }

        // 3. System fallback: 10% SaaS, 5% Partner, 85% Merchant
        return [
            'saas_rate_percentage' => 10.00,
            'partner_rate_percentage' => 5.00,
            'merchant_rate_percentage' => 85.00,
            'absorb_gateway_fees' => 'merchant'
        ];
    }
}
