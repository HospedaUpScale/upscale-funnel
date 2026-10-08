<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Database\Connection;
use PDO;

class OfferPublicationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Connection::make();
    }

    public function testOfferSchemaHasSlugAndPublicationColumns(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info(upsell_offers)");
        $columns = array_map(fn($col) => $col['name'], $stmt->fetchAll(PDO::FETCH_ASSOC));

        $this->assertTrue(in_array('slug', $columns, true), "Column 'slug' must exist in upsell_offers.");
        $this->assertTrue(in_array('is_published', $columns, true), "Column 'is_published' must exist in upsell_offers.");
        $this->assertTrue(in_array('draft_config', $columns, true), "Column 'draft_config' must exist in upsell_offers.");
        $this->assertTrue(in_array('published_config', $columns, true), "Column 'published_config' must exist in upsell_offers.");
    }

    public function testOfferDraftAndPublicationWorkflow(): void
    {
        // 1. Garantir existência de uma oferta de teste
        $testSlug = 'teste-booster-ouro-' . time();
        $stmt = $this->pdo->prepare("
            INSERT INTO upsell_offers (
                workspace_id, title, subtitle, price, quota_count,
                slug, is_published, draft_config, published_config, is_active
            ) VALUES (1, 'Booster Teste 200X', 'Subtitulo Campanha Teste', 90.00, 200, ?, 0, '{\"draft\":true}', null, 1)
        ");
        $stmt->execute([$testSlug]);
        $offerId = (int)$this->pdo->lastInsertId();

        $this->assertTrue($offerId > 0, "Offer must be created.");

        // 2. Verificar que como rascunho (is_published = 0) ela NÃO é retornada como oferta pública
        $stmtCheck = $this->pdo->prepare("SELECT id FROM upsell_offers WHERE slug = ? AND is_published = 1 AND is_active = 1");
        $stmtCheck->execute([$testSlug]);
        $this->assertFalse($stmtCheck->fetch() !== false, "Draft offer must NOT be available on public query.");

        // 3. Simular publicação: is_published = 1 e preenchimento de published_config
        $pubConfig = [
            'title' => 'Booster Teste 200X',
            'subtitle' => 'Subtitulo Campanha Teste',
            'price' => 90.00,
            'quota_count' => 200,
            'slug' => $testSlug,
            'theme' => [
                'primary_color' => '#1764FF',
                'accent_color' => '#9FE870'
            ]
        ];
        $stmtPub = $this->pdo->prepare("
            UPDATE upsell_offers SET
                is_published = 1,
                published_at = CURRENT_TIMESTAMP,
                published_config = ?
            WHERE id = ?
        ");
        $stmtPub->execute([json_encode($pubConfig), $offerId]);

        // 4. Verificar que agora é retornada na consulta pública
        $stmtCheck->execute([$testSlug]);
        $pubRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $this->assertTrue($pubRow !== false, "Published offer must be returned on public query.");

        // 5. Limpeza da oferta temporária de teste
        $this->pdo->prepare("DELETE FROM upsell_offers WHERE id = ?")->execute([$offerId]);
    }

    public function testPublicPageHasNoUpscaleBranding(): void
    {
        $indexPath = dirname(__DIR__, 2) . '/index.html';
        $this->assertTrue(file_exists($indexPath), "index.html must exist.");

        $html = file_get_contents($indexPath);

        // Verificar que não existe nenhuma marca visível upScale na página pública
        $this->assertFalse(str_contains(strtolower($html), '>upscale<'), "Public page must not contain upScale brand tag.");
        $this->assertFalse(str_contains(strtolower($html), 'alt="upscale"'), "Public page must not contain upScale logo image.");
        $this->assertFalse(str_contains(strtolower($html), 'power by upscale'), "Public page must not contain 'power by upscale'.");
        $this->assertFalse(str_contains(strtolower($html), 'powered by upscale'), "Public page must not contain 'powered by upscale'.");
    }

    public function testPublicRouteFileExists(): void
    {
        $publicFile = dirname(__DIR__, 2) . '/offer-public.php';
        $this->assertTrue(file_exists($publicFile), "offer-public.php must exist.");
    }
}
