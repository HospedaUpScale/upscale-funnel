<?php

namespace App\Pix;

use App\Tenancy\Workspace;

interface PixGatewayInterface
{
    public function createCharge(
        Workspace $workspace,
        int $orderId,
        float $amount,
        string $description,
        ?string $customerCpf = null,
        ?string $customerName = null
    ): PixCharge;

    public function getStatus(string $gatewayTxId): string;
}
