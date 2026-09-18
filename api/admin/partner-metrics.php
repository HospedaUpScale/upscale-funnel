<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

if ($auth && $auth['role'] === 'merchant_admin') {
    JsonResponse::error('Acesso restrito a parceiros white-label e administradores upScale.', 403);
}

// Target Partner Workspace
if ($auth && $auth['role'] === 'partner_admin') {
    $partnerId = $auth['workspace']->id;
} else {
    $partnerId = (int)($request->get('partner_id') ?? $request->get('workspace_id') ?? 1);
}

// Find all child merchants where parent_id = $partnerId
$stmt = $pdo->prepare("SELECT * FROM workspaces WHERE parent_id = ? ORDER BY name ASC");
$stmt->execute([$partnerId]);
$merchants = $stmt->fetchAll();

$totalGmv = 0.0;
$totalPartnerCommission = 0.0;
$totalSaasCommission = 0.0;
$totalMerchantPayout = 0.0;
$totalPaidOrders = 0;

$merchantBreakdown = [];

foreach ($merchants as $m) {
    $mId = (int)$m['id'];

    // Get paid transactions for this merchant
    $stmt = $pdo->prepare("
        SELECT t.amount, t.split_details
        FROM transactions t
        JOIN orders o ON o.id = t.order_id
        WHERE o.workspace_id = ? AND t.status = 'paid'
    ");
    $stmt->execute([$mId]);
    $txs = $stmt->fetchAll();

    $mGmv = 0.0;
    $mPartner = 0.0;
    $mSaas = 0.0;
    $mNet = 0.0;
    $mCount = count($txs);

    foreach ($txs as $tx) {
        $amount = (float)$tx['amount'];
        $splits = !empty($tx['split_details']) ? (is_array($tx['split_details']) ? $tx['split_details'] : json_decode($tx['split_details'], true)) : null;

        $pShare = (float)($splits['partner_amount'] ?? ($amount * 0.05));
        $sShare = (float)($splits['saas_amount'] ?? ($amount * 0.10));
        $mShare = (float)($splits['merchant_amount'] ?? ($amount - $pShare - $sShare));

        $mGmv += $amount;
        $mPartner += $pShare;
        $mSaas += $sShare;
        $mNet += $mShare;
    }

    $totalGmv += $mGmv;
    $totalPartnerCommission += $mPartner;
    $totalSaasCommission += $mSaas;
    $totalMerchantPayout += $mNet;
    $totalPaidOrders += $mCount;

    $merchantBreakdown[] = [
        'workspace_id'       => $mId,
        'name'               => $m['name'],
        'slug'               => $m['slug'],
        'custom_domain'      => $m['custom_domain'],
        'paid_orders'        => $mCount,
        'gmv'                => $mGmv,
        'partner_commission' => $mPartner,
        'merchant_payout'    => $mNet,
    ];
}

JsonResponse::send([
    'status' => 'success',
    'partner_workspace_id' => $partnerId,
    'summary' => [
        'total_merchants'         => count($merchants),
        'total_paid_orders'       => $totalPaidOrders,
        'total_gmv'               => $totalGmv,
        'total_partner_commission'=> $totalPartnerCommission,
        'total_saas_commission'   => $totalSaasCommission,
        'total_merchant_payout'   => $totalMerchantPayout,
    ],
    'merchants' => $merchantBreakdown
]);
