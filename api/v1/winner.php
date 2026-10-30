<?php

// ==============================================================================
// upScale Media Network - API v1: Auditoria de Ganhadores & Bônus Extra
// ==============================================================================

require_once __DIR__ . '/../bootstrap.php';

use App\Http\JsonResponse;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$token = trim((string)$request->get('token'));
$orderId = trim((string)($request->get('order_id') ?? $request->get('pedido_id') ?? $request->get('id')));
$phone = trim((string)($request->get('phone') ?? $request->get('telefone')));

// A consulta pelo token do card (segredo aleatório que só o comprador recebe) é livre.
// As consultas por pedido ou telefone exigem o token de API do workspace ou um login de admin.
$targetWsId = 0;
if ($token === '' && ($orderId !== '' || $phone !== '')) {
    $apiToken = '';
    $authHeader = (string) ($request->header('Authorization') ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
        $apiToken = trim($m[1]);
    }
    $apiToken = $apiToken ?: trim((string) ($request->header('X-Api-Token') ?? $request->header('X-Upscale-Token') ?? ''));

    $apiWorkspace = (new \App\Security\WebhookAuth($wsRepo))->workspaceForToken($apiToken);
    if ($apiWorkspace) {
        $targetWsId = $apiWorkspace->id;
    } else {
        $adminAuth = (new \App\Auth\AdminAuth($wsRepo, $config['super_admin_token'] ?? ''))->authenticate($apiToken ?: null);
        if (!$adminAuth) {
            JsonResponse::error('Autenticação obrigatória para consultar por pedido ou telefone.', 401);
        }

        $requested = (int) ($request->get('workspace_id') ?? 0);
        if ($adminAuth['role'] === 'super_admin') {
            $targetWsId = $requested > 0 ? $requested : (int) ($workspace->id ?? 0);
        } else {
            $ownId = $adminAuth['workspace']->id;
            $allowed = array_merge([$ownId], array_map(fn($w) => $w->id, $wsRepo->findDescendants($ownId)));
            $targetWsId = $requested > 0 ? $requested : $ownId;
            if (!in_array($targetWsId, $allowed, true)) {
                JsonResponse::error('Forbidden for this workspace', 403);
            }
        }
    }
}

$found = null;

if ($token !== '') {
    $stmt = $pdo->prepare("SELECT * FROM order_access_tokens WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $found = $stmt->fetch();
} elseif ($orderId !== '') {
    $stmt = $pdo->prepare("SELECT * FROM order_access_tokens WHERE workspace_id = ? AND external_order_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$targetWsId, $orderId]);
    $found = $stmt->fetch();
} elseif ($phone !== '') {
    // Busca exata pelos dígitos do telefone (sem LIKE, para não permitir varredura parcial).
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) < 10) {
        JsonResponse::error('Informe o telefone completo com DDD.', 422);
    }
    $stmt = $pdo->prepare("SELECT * FROM order_access_tokens WHERE workspace_id = ? AND customer_phone IS NOT NULL ORDER BY id DESC LIMIT 500");
    $stmt->execute([$targetWsId]);
    $found = null;
    foreach ($stmt->fetchAll() as $row) {
        $rowDigits = preg_replace('/\D/', '', (string) $row['customer_phone']);
        if ($rowDigits !== '' && ($rowDigits === $digits || ltrim($rowDigits, '0') === ltrim($digits, '0')
            || (str_starts_with($rowDigits, '55') && substr($rowDigits, 2) === $digits)
            || (str_starts_with($digits, '55') && substr($digits, 2) === $rowDigits))) {
            $found = $row;
            break;
        }
    }
}

if (!$found) {
    JsonResponse::error('Nenhum registro de pedido ou raspadinha encontrado com os dados fornecidos.', 404);
}

$isEntitled = (bool)$found['bonus_entitled'];
// Convertido = existe Pix do upsell PAGO para este card (gerar o Pix não basta).
$paidUpsell = \App\Support\UpsellConversion::paidOrder($pdo, $found);
$hasConverted = $paidUpsell !== null;
$extraBonus = (float)$found['extra_bonus'];

$veredicto = '';
if ($isEntitled && $hasConverted) {
    $veredicto = 'PARABÉNS! Ganhador 100% elegível: comprou a rifa, cumpriu o valor mínimo e ATIVOU a raspadinha. Bônus de R$ ' . number_format($extraBonus, 2, ',', '.') . ' LIBERADO para pagamento.';
} elseif ($isEntitled && !$hasConverted) {
    $veredicto = 'ATENÇÃO: O comprador atingiu a compra mínima de bilhetes, mas NÃO completou o pagamento do Booster/Raspadinha. Verifique o comprovante.';
} else {
    $veredicto = 'NÃO ELEGÍVEL: O valor da compra (R$ ' . number_format((float)$found['base_amount'], 2, ',', '.') . ') foi inferior ao mínimo exigido (R$ ' . number_format((float)$found['min_order'], 2, ',', '.') . ') para o bônus extra.';
}

// Reserva do upsell deste card (se já foi pago): o card abre direto na tela de reserva.
$reserva = null;
if ($token !== '') {
    // Qualquer Pix pago deste card (o comprador pode ter gerado mais de um).
    $paid = $paidUpsell;
    if ($paid) {
        $offerRow = !empty($paid['offer_id']) ? (new \App\Repositories\OfferRepository($pdo))->find((int)$paid['offer_id'], (int)$paid['workspace_id']) : null;
        $r = \App\Support\PaymentSettlement::reservation($pdo, $paid, $offerRow);
        if ($r) $reserva = ['pago' => true, 'quotas' => $r['quotas'], 'tickets' => $r['tickets'], 'valor' => (float)($paid['upsell_amount'] ?? $paid['total_amount'] ?? 0), 'oferta_id' => (int)($paid['offer_id'] ?? 0)];
    }
}

JsonResponse::success([
    'reserva' => $reserva,
    'order_id' => $found['external_order_id'],
    'customer_name' => $found['customer_name'],
    // Pelo token do card (consulta pública) o telefone vai mascarado; pedido/telefone exigem token de API ou admin.
    'customer_phone' => $token !== '' ? \App\Support\LogPath::maskPhone($found['customer_phone']) : $found['customer_phone'],
    'base_amount_paid' => (float)$found['base_amount'],
    'min_order_required' => (float)$found['min_order'],
    'bonus_entitled' => $isEntitled,
    'upsell_converted' => $hasConverted,
    'upsell_order_id' => $found['upsell_order_id'],
    'extra_bonus_amount' => $extraBonus,
    'carro_a' => $found['carro_a'],
    'carro_b' => $found['carro_b'],
    'veredicto' => $veredicto,
    'timestamp' => date('c')
]);
