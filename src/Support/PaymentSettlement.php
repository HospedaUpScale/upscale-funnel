<?php

namespace App\Support;

use App\Repositories\ConversionEventRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\OfferRepository;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use PDO;

/**
 * Liquidação de um Pix do upsell: marca pago, registra o evento e avisa a rifa (cotas pagas).
 * Usada pelo webhook do gateway e pela confirmação manual de Pix de teste no painel.
 */
class PaymentSettlement
{
    public static function settle(PDO $pdo, array $config, array $order, array $transaction, string $source, array $meta = []): array
    {
        $workspaceId = (int)$order['workspace_id'];
        $txRepo = new TransactionRepository($pdo);
        $orderRepo = new OrderRepository($pdo);
        $eventRepo = new ConversionEventRepository($pdo);

        $txRepo->markPaid((int)$transaction['id']);
        $orderRepo->markPaid((int)$order['id']);
        $eventRepo->log($workspaceId, (int)$order['id'], 'pix_paid', array_merge([
            'transaction_id' => (int)$transaction['id'],
            'gateway'        => $transaction['gateway'] ?? 'webhook',
            'amount'         => (float)$transaction['amount'],
            'source'         => $source,
        ], $meta));

        $customer = !empty($order['customer_id']) ? (new CustomerRepository($pdo))->find((int)$order['customer_id']) : null;
        $offer = !empty($order['offer_id']) ? (new OfferRepository($pdo))->find((int)$order['offer_id'], $workspaceId) : null;
        $orderFresh = $orderRepo->find((int)$order['id']) ?: $order;
        $payload = RafflePostback::payload($pdo, $orderFresh, $customer, $offer);
        // Campo legado: assinatura de "order_id|external_order_id" (mantido para quem já integrou).
        $secret = (string)($config['postback_secret'] ?? '');
        if ($secret !== '') {
            $payload['signature'] = hash_hmac('sha256', (string)$order['id'] . '|' . $order['external_order_id'], $secret);
        }
        $url = RafflePostback::destination($pdo, $workspaceId, $offer);
        $result = $url ? RafflePostback::send($url, $payload, $secret) : 'no_url_configured';
        try {
            $eventRepo->log($workspaceId, (int)$order['id'], 'postback', [
                'url'    => $url ? parse_url($url, PHP_URL_HOST) : null,
                'result' => $result,
            ], ['offer_id' => $offer['id'] ?? null]);
        } catch (\Throwable $e) {
        }

        return [
            'quotas_awarded'    => $payload['quotas_awarded'],
            'customer'          => ['name' => $customer['name'] ?? null, 'phone' => $customer['phone'] ?? null],
            'postback_delivery' => $result,
        ];
    }
}
