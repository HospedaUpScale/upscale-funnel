<?php
/**
 * Confirma manualmente um Pix de TESTE (gateway de demonstração) pelo painel: botão "Confirmar".
 * Segue o mesmo caminho de um pagamento real (cotas, evento e aviso para a rifa).
 * Pix de gateway real não pode ser confirmado à mão: o gateway é quem confirma.
 * POST {order_id}
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use App\Support\PaymentSettlement;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);
if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Não autenticado. Entre novamente.', 401);
}
if ($request->method() !== 'POST') {
    JsonResponse::error('Method not allowed', 405);
}

$orderId = (int)($request->all()['order_id'] ?? 0);
$order = $orderId > 0 ? (new OrderRepository($pdo))->find($orderId) : null;
if (!$order) {
    JsonResponse::error('Pedido não encontrado.', 404);
}

// O pedido precisa ser de um workspace que a pessoa logada administra.
if ($auth && $auth['role'] !== 'super_admin') {
    $allowed = [$auth['workspace']->id];
    foreach ($wsRepo->findDescendants($auth['workspace']->id) as $d) $allowed[] = $d->id;
    if (!in_array((int)$order['workspace_id'], $allowed, true)) {
        JsonResponse::error('Pedido não encontrado.', 404);
    }
}

$transaction = (new TransactionRepository($pdo))->findByOrderId((int)$order['id']);
if (!$transaction) {
    JsonResponse::error('Este pedido não tem Pix gerado.', 422);
}
if ($transaction['status'] === 'paid') {
    JsonResponse::success(['message' => 'Este Pix já estava pago.', 'order_id' => (int)$order['id']]);
}
// Pix de teste = gerado pelo gateway de demonstração (cobrança "UPSCALE SANDBOX") ou marcado como mock.
$gateway = strtolower((string)($transaction['gateway'] ?? ''));
$ehTeste = in_array($gateway, ['', 'mock', 'sandbox', 'teste'], true)
    || stripos((string)($transaction['pix_copy_paste'] ?? ''), 'UPSCALE SANDBOX') !== false;
if (!$ehTeste) {
    JsonResponse::error('Este Pix é de um gateway real: a confirmação vem do próprio gateway.', 409);
}

$result = PaymentSettlement::settle($pdo, $config, $order, $transaction, 'manual_test', [
    'confirmed_by' => $auth['user_id'] ?? null,
]);

$avisoRifa = is_array($result['postback_delivery'])
    ? ($result['postback_delivery']['delivered'] ? ' Rifa avisada (cotas pagas).' : ' Aviso à rifa falhou (HTTP ' . $result['postback_delivery']['status_code'] . ').')
    : ' Nenhum endereço de retorno da rifa configurado.';

JsonResponse::success([
    'message'           => 'Pix de teste confirmado.' . $avisoRifa,
    'order_id'          => (int)$order['id'],
    'quotas_awarded'    => $result['quotas_awarded'],
    'postback_delivery' => $result['postback_delivery'],
]);
