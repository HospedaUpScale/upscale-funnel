<?php
/**
 * Registro leve de eventos da oferta pública (visita e raspada/giro).
 * Chamado pela página da oferta via sendBeacon. Responde sempre 204.
 * POST/GET: offer_id, event (view | scratch_win)
 */

require_once __DIR__ . '/bootstrap.php';

use App\Repositories\ConversionEventRepository;

$input = $request->method() === 'POST' ? $request->all() : [];
$offerId = (int)($request->get('offer_id') ?? ($input['offer_id'] ?? 0));
$event = (string)($request->get('event') ?? ($input['event'] ?? ''));

if ($offerId > 0 && in_array($event, ['view', 'scratch_win'], true)) {
    try {
        $stmt = $pdo->prepare("SELECT id, workspace_id, campaign_id FROM upsell_offers WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$offerId]);
        if ($offer = $stmt->fetch()) {
            (new ConversionEventRepository($pdo))->log((int)$offer['workspace_id'], null, $event, null, [
                'offer_id'    => (int)$offer['id'],
                'campaign_id' => $offer['campaign_id'] ? (int)$offer['campaign_id'] : null,
            ]);
        }
    } catch (\Throwable $e) {
        // Registro de métrica nunca pode atrapalhar a página da oferta.
    }
}

http_response_code(204);
