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
           o.source, o.source_ref,
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
unset($ro);

// 4. Vendas por canal de origem (todas as vendas, inclusive o link exclusivo do upsell)
$sourceSql = "
    SELECT COALESCE(NULLIF(source, ''), 'direct') AS source,
           COUNT(*) AS total,
           SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid,
           SUM(CASE WHEN status = 'paid' THEN upsell_amount ELSE 0 END) AS revenue
    FROM orders
    " . ($isGlobal ? "" : "WHERE workspace_id = ?") . "
    GROUP BY COALESCE(NULLIF(source, ''), 'direct')
    ORDER BY revenue DESC
";
$stmt = $pdo->prepare($sourceSql);
$stmt->execute($isGlobal ? [] : [$targetWsId]);
$sourceRows = $stmt->fetchAll();
$salesBySource = array_map(fn($r) => [
    'source'  => $r['source'],
    'total'   => (int)$r['total'],
    'paid'    => (int)$r['paid'],
    'revenue' => (float)$r['revenue'],
], $sourceRows);

// 5. Período escolhido no painel: série diária, totais e comparação com o período anterior
$periodParam = (string)($request->get('period') ?? '30');
$today = new DateTimeImmutable('today');
if ($periodParam === 'month') {
    $periodStart = $today->modify('first day of this month');
} else {
    $periodDays = in_array((int)$periodParam, [7, 14, 30, 90], true) ? (int)$periodParam : 30;
    $periodStart = $today->modify('-' . ($periodDays - 1) . ' days');
}
$dayCount = (int)$periodStart->diff($today)->days + 1;
$prevStart = $periodStart->modify('-' . $dayCount . ' days');

$wsFilter = $isGlobal ? '' : 'workspace_id = ? AND ';
$params = $isGlobal ? [$prevStart->format('Y-m-d 00:00:00')] : [$targetWsId, $prevStart->format('Y-m-d 00:00:00')];

$stmt = $pdo->prepare("SELECT DATE(created_at) AS d, status, COUNT(*) AS n, COALESCE(SUM(upsell_amount), 0) AS v
    FROM orders WHERE {$wsFilter}created_at >= ? GROUP BY DATE(created_at), status");
$stmt->execute($params);
$orderDays = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT DATE(created_at) AS d, event_name, COUNT(*) AS n
    FROM conversion_events WHERE {$wsFilter}created_at >= ? GROUP BY DATE(created_at), event_name");
$stmt->execute($params);
$eventDays = $stmt->fetchAll();

$emptyDay = ['revenue' => 0.0, 'paid' => 0, 'orders' => 0, 'views' => 0, 'scratches' => 0, 'pix_generated' => 0];
$byDay = [];
foreach ($orderDays as $r) {
    $d = substr((string)$r['d'], 0, 10);
    $byDay[$d] = $byDay[$d] ?? $emptyDay;
    $byDay[$d]['orders'] += (int)$r['n'];
    if ($r['status'] === 'paid') {
        $byDay[$d]['paid'] += (int)$r['n'];
        $byDay[$d]['revenue'] += (float)$r['v'];
    }
}
foreach ($eventDays as $r) {
    $d = substr((string)$r['d'], 0, 10);
    $byDay[$d] = $byDay[$d] ?? $emptyDay;
    $name = $r['event_name'];
    if ($name === 'view') $byDay[$d]['views'] += (int)$r['n'];
    elseif ($name === 'scratch_win' || $name === 'scratch') $byDay[$d]['scratches'] += (int)$r['n'];
    elseif ($name === 'pix_generated') $byDay[$d]['pix_generated'] += (int)$r['n'];
}

$sumRange = function (DateTimeImmutable $from, int $days) use ($byDay, $emptyDay): array {
    $tot = $emptyDay;
    for ($i = 0; $i < $days; $i++) {
        $row = $byDay[$from->modify("+{$i} days")->format('Y-m-d')] ?? $emptyDay;
        foreach ($tot as $k => $_) $tot[$k] += $row[$k];
    }
    $tot['average_ticket'] = $tot['paid'] > 0 ? round($tot['revenue'] / $tot['paid'], 2) : 0;
    $tot['conversion_rate'] = $tot['views'] > 0 ? round($tot['paid'] / $tot['views'] * 100, 2) : null;
    $tot['revenue'] = round($tot['revenue'], 2);
    return $tot;
};

$series = [];
for ($i = 0; $i < $dayCount; $i++) {
    $d = $periodStart->modify("+{$i} days")->format('Y-m-d');
    $row = $byDay[$d] ?? $emptyDay;
    $row['revenue'] = round($row['revenue'], 2);
    $row['average_ticket'] = $row['paid'] > 0 ? round($row['revenue'] / $row['paid'], 2) : 0;
    $row['conversion_rate'] = $row['views'] > 0 ? round($row['paid'] / $row['views'] * 100, 2) : null;
    $series[] = ['date' => $d] + $row;
}

$periodData = [
    'key'      => $periodParam === 'month' ? 'month' : (string)$dayCount,
    'start'    => $periodStart->format('Y-m-d'),
    'end'      => $today->format('Y-m-d'),
    'days'     => $dayCount,
    'current'  => $sumRange($periodStart, $dayCount),
    'previous' => $sumRange($prevStart, $dayCount),
    'series'   => $series,
];

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
    'period' => $periodData,
    'recent_orders' => $recentOrders,
    'sales_by_source' => $salesBySource
]);
