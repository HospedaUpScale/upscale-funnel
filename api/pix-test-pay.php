<?php
/**
 * Pix de teste: simula o pagamento aprovado de um Pix da oferta.
 * Só funciona com o "Pix de teste" ligado no workspace (painel → Webhooks) e apenas para Pix do
 * gateway de demonstração. Segue o mesmo caminho de um pagamento real: cotas, eventos e o aviso
 * upsell.quotas_paid para a rifa.
 * POST {transaction_id}
 */

require_once __DIR__ . '/bootstrap.php';

use App\Http\JsonResponse;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use App\Support\PaymentSettlement;
use App\Support\PixTestMode;

if ($request->method() !== 'POST') {
    JsonResponse::error('Method not allowed. Use POST.', 405);
}

$txId = (int)($request->all()['transaction_id'] ?? 0);
$tx = $txId > 0 ? (new TransactionRepository($pdo))->find($txId) : null;
$order = $tx ? (new OrderRepository($pdo))->find((int)$tx['order_id']) : null;
if (!$tx || !$order || (int)$order['workspace_id'] !== $workspace->id) {
    JsonResponse::error('Pix não encontrado.', 404);
}

if (!PixTestMode::active($pdo, $workspace->id)) {
    JsonResponse::error('O Pix de teste está desligado. Ligue no painel do upScale: Webhooks → Pix de teste.', 403);
}
if (!PixTestMode::isTestTransaction($tx)) {
    JsonResponse::error('Este Pix é de um gateway real: só o gateway confirma o pagamento.', 409);
}
if ($tx['status'] === 'paid') {
    JsonResponse::success(['paid' => true, 'message' => 'Este Pix já estava pago.']);
}
if ($tx['status'] === 'expired' || (!empty($tx['expires_at']) && strtotime($tx['expires_at']) <= time())) {
    JsonResponse::error('Este Pix expirou. Gere um novo.', 410);
}

$result = PaymentSettlement::settle($pdo, $config, $order, $tx, 'pix_test', ['simulated' => true]);

$entrega = $result['postback_delivery'];
if (is_array($entrega)) {
    if ($entrega['delivered']) {
        $q = (int)$result['quotas_awarded'];
        $avisoRifa = 'Rifa avisada: ' . $q . ($q === 1 ? ' cota extra enviada.' : ' cotas extras enviadas.');
    } elseif ((int)$entrega['status_code'] === 0) {
        $avisoRifa = 'Não foi possível conectar ao endereço de retorno da rifa. Confira o endereço em Webhooks → Retorno para a rifa.';
    } elseif ((int)$entrega['status_code'] === 401) {
        $avisoRifa = 'A rifa recusou o aviso (HTTP 401): a chave não confere. No retorno automático, faça uma venda nova; no modo antigo, o POSTBACK_SECRET do upScale e o segredo da rifa precisam ser iguais.';
    } else {
        $avisoRifa = 'O aviso para a rifa falhou (HTTP ' . $entrega['status_code'] . '): ' . mb_substr((string)$entrega['response'], 0, 140);
    }
} else {
    $avisoRifa = 'Nenhum endereço de retorno da rifa cadastrado (painel → Webhooks → Retorno para a rifa).';
}

JsonResponse::success([
    'paid'             => true,
    'quotas_awarded'   => $result['quotas_awarded'],
    'raffle_notified'  => is_array($entrega) && $entrega['delivered'],
    'raffle_http'      => is_array($entrega) ? $entrega['status_code'] : null,
    'tickets'          => $result['reservation']['tickets'] ?? [],
    'message'          => 'Pagamento de teste aprovado. ' . $avisoRifa,
]);
