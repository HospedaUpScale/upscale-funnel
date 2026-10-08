<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Repositories\PartnerSplitRuleRepository;
use App\Tenancy\Workspace;

class PartnerSplitRuleTest extends TestCase
{
    public function testFallbackDefaultZeroCommission(): void
    {
        $workspace = new Workspace(
            id: 99999,
            parentId: null,
            type: 'merchant',
            name: 'Merchant Test Zero',
            slug: 'test-zero',
            customDomain: null,
            document: null,
            apiTokenHash: 'hash',
            adminTokenHash: 'hash',
            gatewayProvider: 'pending',
            gatewayCredentials: null,
            splitRecipientId: null,
            status: 'active'
        );

        $repo = new PartnerSplitRuleRepository();
        $rule = $repo->resolveFor($workspace);

        $this->assertEquals(0.00, (float) $rule['saas_rate_percentage']);
        $this->assertEquals(0.00, (float) $rule['partner_rate_percentage']);
        $this->assertEquals(100.00, (float) $rule['merchant_rate_percentage']);
        $this->assertEquals('merchant', $rule['absorb_gateway_fees']);
    }
}
