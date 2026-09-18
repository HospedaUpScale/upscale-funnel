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

$targetWsId = (int)($request->get('workspace_id') ?? $workspace->id);

$isGlobal = ($targetWsId === 0 && $auth && $auth['role'] === 'super_admin');

if (!$isGlobal && $auth && $auth['role'] !== 'super_admin') {
    if ($auth['workspace']->id !== $targetWsId) {
        $descendants = $wsRepo->findDescendants($auth['workspace']->id);
        $allowedIds = array_map(fn($w) => $w->id, $descendants);
        if (!in_array($targetWsId, $allowedIds, true)) {
            JsonResponse::error('Forbidden for this workspace', 403);
        }
    }
}

// 1. Orders aggregation
$orderSql = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'paid' THEN upsell_amount ELSE 0 END) as total_recovered,
        AVG(CASE WHEN status = 'paid' THEN upsell_amount ELSE NULL END) as average_ticket
    FROM orders
" . ($isGlobal ? "" : "WHERE workspace_id = ?");

$stmt = $pdo->prepare($orderSql);
$stmt->execute($isGlobal ? [] : [$targetWsId]);
$orderStats = $stmt->fetch();

// 2. Funnel conversion events
$eventSql = "
    SELECT event_name, COUNT(*) as count
    FROM conversion_events
    " . ($isGlobal ? "" : "WHERE workspace_id = ?") . "
    GROUP BY event_name
";
$stmt = $pdo->prepare($eventSql);
$stmt->execute($isGlobal ? [] : [$targetWsId]);
$eventRows = $stmt->fetchAll();
$events = [];
foreach ($eventRows as $r) {
    $events[$r['event_name']] = (int)$r['count'];
}

$views = $events['view'] ?? 0;
$scratches = $events['scratch_win'] ?? ($events['scratch'] ?? 0);
$pixGenerated = $events['pix_generated'] ?? 0;
$pixPaid = (int)($orderStats['paid_orders'] ?? 0);

$conversionRate = ($views > 0) ? round(($pixPaid / $views) * 100, 2) : 0;
$scratchToPixRate = ($scratches > 0) ? round(($pixPaid / $scratches) * 100, 2) : 0;

// 3. Recent orders list
$recentSql = "
    SELECT o.id, o.workspace_id, o.status, o.upsell_amount, o.created_at, o.paid_at,
           c.name as customer_name, c.phone as customer_phone,
           w.name as workspace_name,
           t.gateway_tx_id, t.pix_copy_paste, t.split_details
    FROM orders o
    LEFT JOIN customers c ON c.id = o.customer_id
    LEFT JOIN workspaces w ON w.id = o.workspace_id
    LEFT JOIN transactions t ON t.order_id = o.id
    " . ($isGlobal ? "" : "WHERE o.workspace_id = ?") . "
    ORDER BY o.id DESC
    LIMIT 35
";
$stmt = $pdo->prepare($recentSql);
$stmt->execute($isGlobal ? [] : [$targetWsId]);
$recentOrders = $stmt->fetchAll();

// Decode split_details in recent orders
foreach ($recentOrders as &$ro) {
    if (!empty($ro['split_details']) && is_string($ro['split_details'])) {
        $ro['split_details'] = json_decode($ro['split_details'], true);
    }
}

JsonResponse::send([
    'status' => 'success',
    'workspace_id' => $targetWsId,
    'metrics' => [
        'total_recovered'    => (float)($orderStats['total_recovered'] ?? 0),
        'paid_orders'        => (int)($orderStats['paid_orders'] ?? 0),
        'pending_orders'     => (int)($orderStats['pending_orders'] ?? 0),
        'total_orders'       => (int)($orderStats['total_orders'] ?? 0),
        'average_ticket'     => round((float)($orderStats['average_ticket'] ?? 0), 2),
        'conversion_rate'    => $conversionRate,
        'scratch_to_pix_rate'=> $scratchToPixRate,
        'funnel' => [
            'views'          => $views,
            'scratches'      => $scratches,
            'pix_generated'  => $pixGenerated,
            'pix_paid'       => $pixPaid,
        ]
    ],
    'recent_orders' => $recentOrders
]);
