<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Database\Connection;
use PDO;

class FunnelBuilderTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Connection::make();
    }

    public function testFunnelSchemaHasDraftAndBlocksColumns(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info(funnels)");
        $columns = array_map(fn($col) => $col['name'], $stmt->fetchAll(PDO::FETCH_ASSOC));

        $this->assertTrue(in_array('draft_content', $columns, true), "Column 'draft_content' must exist in funnels.");
        $this->assertTrue(in_array('published_content', $columns, true), "Column 'published_content' must exist in funnels.");
        $this->assertTrue(in_array('blocks_config', $columns, true), "Column 'blocks_config' must exist in funnels.");
        $this->assertTrue(in_array('settings_config', $columns, true), "Column 'settings_config' must exist in funnels.");
        $this->assertTrue(in_array('status', $columns, true), "Column 'status' must exist in funnels.");
    }

    public function testDraftAndPublishSeparation(): void
    {
        $slug = 'teste-funil-elementor-' . time();
        $draftHtml = '<h1 data-block-id="blk_1">Rascunho de Oferta</h1><p>Versão não publicada</p>';
        $publishedHtml = '<h1 data-block-id="blk_1">Oferta Oficial Publicada</h1><p>Versão pública no ar</p>';

        // 1. Criar funil como rascunho
        $stmt = $this->pdo->prepare("
            INSERT INTO funnels (
                workspace_id, name, slug, draft_content, published_content, html_content, status
            ) VALUES (1, 'Funil Elementor Teste', ?, ?, NULL, ?, 'draft')
        ");
        $stmt->execute([$slug, $draftHtml, $draftHtml]);
        $funnelId = (int)$this->pdo->lastInsertId();

        $this->assertTrue($funnelId > 0, "Funnel must be created");

        // 2. Verificar que como rascunho não pode ser retornado em busca pública
        $stmtPub = $this->pdo->prepare("SELECT id FROM funnels WHERE slug = ? AND status = 'published'");
        $stmtPub->execute([$slug]);
        $this->assertFalse($stmtPub->fetch() !== false, "Draft funnel should NOT be published");

        // 3. Simular publicação
        $stmtUpdate = $this->pdo->prepare("
            UPDATE funnels SET
                draft_content = ?,
                published_content = ?,
                html_content = ?,
                status = 'published',
                published_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmtUpdate->execute([$draftHtml, $publishedHtml, $publishedHtml, $funnelId]);

        // 4. Verificar que agora está publicado com o conteúdo publicado
        $stmtPub2 = $this->pdo->prepare("SELECT id, published_content, draft_content, status FROM funnels WHERE slug = ? AND status = 'published'");
        $stmtPub2->execute([$slug]);
        $row = $stmtPub2->fetch(PDO::FETCH_ASSOC);

        $this->assertTrue($row !== false, "Funnel should now be published");
        $this->assertEquals('published', $row['status']);
        $this->assertEquals($publishedHtml, $row['published_content']);

        // 5. Simular alteração do rascunho sem publicar
        $newDraft = '<h1 data-block-id="blk_1">Novo Rascunho com Alterações</h1>';
        $stmtDraftUpdate = $this->pdo->prepare("
            UPDATE funnels SET draft_content = ? WHERE id = ?
        ");
        $stmtDraftUpdate->execute([$newDraft, $funnelId]);

        // 6. Verificar que a versão pública permaneceu intocada
        $stmtPub3 = $this->pdo->prepare("SELECT published_content, draft_content FROM funnels WHERE id = ?");
        $stmtPub3->execute([$funnelId]);
        $rowAfter = $stmtPub3->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals($publishedHtml, $rowAfter['published_content'], "Public version must NOT change when saving draft.");
        $this->assertEquals($newDraft, $rowAfter['draft_content'], "Draft version must be updated.");

        // Cleanup
        $this->pdo->prepare("DELETE FROM funnels WHERE id = ?")->execute([$funnelId]);
    }

    public function testElementorBuilderFilesExist(): void
    {
        $this->assertTrue(file_exists(__DIR__ . '/../../hub/elementor-builder.css'), "elementor-builder.css must exist.");
        $this->assertTrue(file_exists(__DIR__ . '/../../hub/elementor-builder.js'), "elementor-builder.js must exist.");
        $this->assertTrue(file_exists(__DIR__ . '/../../funnel.php'), "funnel.php must exist.");
    }
}
