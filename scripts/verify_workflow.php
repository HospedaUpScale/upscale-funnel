<?php

echo "--- 1. Testando GET /o/kit-bobzao-booster-200x (Public Route) ---\n";
$c = curl_init('http://127.0.0.1:8000/o/kit-bobzao-booster-200x');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($c);
$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
curl_close($c);
echo "HTTP Code: $code\n";
echo "Contem 'Raspe e descubra': " . (str_contains($html, 'Raspe e descubra') ? "SIM" : "NAO") . "\n";
echo "Contem marca 'upScale': " . (str_contains(strtolower($html), '>upscale<') ? "SIM (ERRO)" : "NAO (CORRETO)") . "\n\n";

echo "--- 2. Testando GET /o/slug-inexistente-999 (404 Test) ---\n";
$c = curl_init('http://127.0.0.1:8000/o/slug-inexistente-999');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
$html404 = curl_exec($c);
$code404 = curl_getinfo($c, CURLINFO_HTTP_CODE);
curl_close($c);
echo "HTTP Code: $code404 (Esperado: 404)\n";
echo "Contem 'Oferta Indisponivel': " . (str_contains($html404, 'Oferta Indisponível') ? "SIM" : "NAO") . "\n\n";

echo "--- 3. Testando GET /api/admin/offer.php?workspace_id=2 (Offer Details) ---\n";
$c = curl_init('http://127.0.0.1:8000/api/admin/offer.php?workspace_id=2');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
$resOffer = curl_exec($c);
$jsonOffer = json_decode($resOffer, true);
curl_close($c);
echo "Status: " . ($jsonOffer['status'] ?? 'err') . "\n";
echo "Title: " . ($jsonOffer['offer']['title'] ?? 'err') . "\n";
echo "Slug: " . ($jsonOffer['offer']['slug'] ?? 'err') . "\n";
echo "Is Published: " . (($jsonOffer['offer']['is_published'] ?? false) ? "SIM" : "NAO") . "\n";
echo "Public URL: " . ($jsonOffer['offer']['public_url'] ?? 'err') . "\n\n";

echo "--- 4. Testando check_slug ---\n";
$c = curl_init('http://127.0.0.1:8000/api/admin/offer.php?action=check_slug&slug=kit-bobzao-booster-200x&offer_id=1');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
$resSlug = curl_exec($c);
$jsonSlug = json_decode($resSlug, true);
curl_close($c);
echo "Slug 'kit-bobzao-booster-200x' para a propria oferta (offer_id=1) disponivel: " . (($jsonSlug['available'] ?? false) ? "SIM" : "NAO") . "\n";

$c = curl_init('http://127.0.0.1:8000/api/admin/offer.php?action=check_slug&slug=kit-bobzao-booster-200x&offer_id=2');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
$resSlugCol = curl_exec($c);
$jsonSlugCol = json_decode($resSlugCol, true);
curl_close($c);
echo "Slug 'kit-bobzao-booster-200x' para outra oferta (offer_id=2) disponivel: " . (($jsonSlugCol['available'] ?? false) ? "SIM" : "NAO (CORRETO - Bloqueado colisão)") . "\n";

$c = curl_init('http://127.0.0.1:8000/api/admin/offer.php?action=check_slug&slug=api');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
$resSlugRes = curl_exec($c);
$jsonSlugRes = json_decode($resSlugRes, true);
curl_close($c);
echo "Slug 'api' (reservado) disponivel: " . (($jsonSlugRes['available'] ?? false) ? "SIM" : "NAO (CORRETO - Bloqueado reservado)") . "\n\n";

echo "--- 5. Testando POST /api/admin/offer.php?action=save_draft (workspace_id=2) ---\n";
$payloadDraft = [
    'workspace_id' => 2,
    'title' => 'Booster 200X Ouro',
    'subtitle' => 'Kit Bobzão Bitruck + Ford F250',
    'price' => 90.00,
    'quota_count' => 200,
    'slug' => 'kit-bobzao-booster-200x',
    'theme' => [
        'primary_color' => '#1764FF',
        'page_bg' => '#06152D'
    ]
];
$c = curl_init('http://127.0.0.1:8000/api/admin/offer.php?action=save_draft');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_POST, true);
curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($payloadDraft));
curl_setopt($c, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resDraft = curl_exec($c);
$jsonDraft = json_decode($resDraft, true);
curl_close($c);
echo "Save Draft Status: " . ($jsonDraft['status'] ?? 'err') . " - " . ($jsonDraft['message'] ?? '') . "\n\n";

echo "--- 6. Testando POST /api/admin/offer.php?action=publish (workspace_id=2) ---\n";
$c = curl_init('http://127.0.0.1:8000/api/admin/offer.php?action=publish');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_POST, true);
curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($payloadDraft));
curl_setopt($c, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resPub = curl_exec($c);
$jsonPub = json_decode($resPub, true);
curl_close($c);
echo "Publish Status: " . ($jsonPub['status'] ?? 'err') . " - " . ($jsonPub['message'] ?? '') . "\n";
echo "Public URL retornada: " . ($jsonPub['public_url'] ?? '') . "\n";
