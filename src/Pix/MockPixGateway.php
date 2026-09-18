<?php

namespace App\Pix;

use App\Pix\Emv\EmvPayloadBuilder;
use App\Pix\QrCode\SvgQrEncoder;
use App\Tenancy\Workspace;

class MockPixGateway implements PixGatewayInterface
{
    private array $config;

    public function __construct(?array $config = null)
    {
        if ($config === null) {
            $appConfig = require __DIR__ . '/../../config/config.php';
            $config = $appConfig['pix'] ?? [];
        }
        $this->config = $config;
    }

    public function createCharge(
        Workspace $workspace,
        int $orderId,
        float $amount,
        string $description,
        ?string $customerCpf = null,
        ?string $customerName = null
    ): PixCharge {
        $txId = 'ORD' . $orderId . 'T' . substr(bin2hex(random_bytes(4)), 0, 6);
        $gatewayTxId = 'mock_tx_' . bin2hex(random_bytes(8));

        $creds = $workspace->gatewayCredentials ?? [];
        $pixKey = $creds['pix_key'] ?? $this->config['sandbox_pix_key'] ?? '00000000000';
        $merchantName = $creds['merchant_name'] ?? $this->config['sandbox_merchant_name'] ?? $workspace->name;
        $merchantCity = $creds['merchant_city'] ?? $this->config['sandbox_merchant_city'] ?? 'SAO PAULO';

        // 1. Build standard BACEN EMV BR Code
        $copyPaste = EmvPayloadBuilder::build(
            pixKey: $pixKey,
            merchantName: $merchantName,
            merchantCity: $merchantCity,
            amount: $amount,
            txId: $txId
        );

        // 2. Generate SVG QR Code
        $qrBase64 = SvgQrEncoder::encode($copyPaste, 280);

        // 3. Timers
        $expiryMinutes = $this->config['expiry_minutes'] ?? 15;
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiryMinutes * 60));

        return new PixCharge(
            gatewayTxId: $gatewayTxId,
            copyPaste: $copyPaste,
            qrBase64: $qrBase64,
            expiresAt: $expiresAt,
            amount: $amount,
            mockAutoConfirmAt: null
        );
    }

    public function getStatus(string $gatewayTxId): string
    {
        return 'pending';
    }
}
