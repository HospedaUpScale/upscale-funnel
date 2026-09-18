<?php

namespace App\Pix;

class PixCharge
{
    public function __construct(
        public string $gatewayTxId,
        public string $copyPaste,
        public string $qrBase64,
        public string $expiresAt,
        public float $amount,
        public ?string $mockAutoConfirmAt = null
    ) {}
}
