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
        $this->assertTrue(file_exists(__DIR__ . '/../../hub/pressel-premium-builder.js'), "pressel-premium-builder.js must exist.");
        $this->assertTrue(file_exists(__DIR__ . '/../../funnel.php'), "funnel.php must exist.");
    }

    public function testCreatePresselWithCustomSlugDomainAndFullTracking(): void
    {
        $slug = 'acao-ranger-v6-' . time();
        $pixelId = '987654321012345';
        $cfg = \App\Templates\TemplateRegistry::getDefaultConfig('pressel-premium');
        $blocks = $cfg['blocks'];
        $settings = $cfg['settings'];
        $settings['tracking']['meta_pixel_id'] = $pixelId;
        $settings['seo']['title'] = 'Ranger V6 Brayam Braz';

        $renderedHtml = \App\Templates\TemplateRegistry::render('pressel-premium', $blocks, $settings, ['is_editor' => false]);

        $this->assertTrue(str_contains($renderedHtml, 'upscale_campaign_utms'), "Must contain UTM storage key.");
        $this->assertTrue(str_contains($renderedHtml, 'ScrollDepth'), "Must contain ScrollDepth tracking.");
        $this->assertTrue(str_contains($renderedHtml, 'TimeOnPage'), "Must contain TimeOnPage tracking.");
        $this->assertTrue(str_contains($renderedHtml, 'InitiateCheckout'), "Must contain InitiateCheckout tracking.");
        $this->assertTrue(str_contains($renderedHtml, $pixelId), "Must contain Meta Pixel ID.");

        // Inserir funil vinculado a domínio e slug
        $stmtDom = $this->pdo->query("SELECT id, domain FROM custom_domains WHERE domain = 'canedo.upscalebrasil.com' LIMIT 1");
        $domRow = $stmtDom->fetch(PDO::FETCH_ASSOC);
        $domId = $domRow ? (int)$domRow['id'] : null;

        $stmt = $this->pdo->prepare("
            INSERT INTO funnels (
                workspace_id, custom_domain_id, name, slug, template_slug,
                draft_content, published_content, html_content,
                blocks_config, settings_config,
                status, is_home, published_at
            ) VALUES (1, ?, 'Ação Ranger V6 Brayam Braz', ?, 'pressel-premium', ?, ?, ?, ?, ?, 'published', 0, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $domId,
            $slug,
            $renderedHtml,
            $renderedHtml,
            $renderedHtml,
            json_encode($blocks),
            json_encode($settings),
        ]);
        $funnelId = (int)$this->pdo->lastInsertId();
        $this->assertTrue($funnelId > 0, "Funnel ID must be positive.");

        // Simular requisição ao funnel.php com domínio e slug
        $_GET['slug'] = $slug;
        $_SERVER['HTTP_HOST'] = 'canedo.upscalebrasil.com';
        $_SERVER['REQUEST_URI'] = '/f/' . $slug;

        ob_start();
        require __DIR__ . '/../../funnel.php';
        $output = ob_get_clean();

        $this->assertTrue(str_contains($output, 'upscale_campaign_utms'), "Output must contain full UTM tracking.");
        $this->assertTrue(str_contains($output, $pixelId), "Output must contain Meta Pixel.");
        $this->assertTrue(str_contains($output, 'Ranger'), "Output must contain campaign title.");

        // Limpeza
        $this->pdo->prepare("DELETE FROM funnels WHERE id = ?")->execute([$funnelId]);
    }

    public function testTemplatePickerMarkupAndStylesExist(): void
    {
        $html = file_get_contents(__DIR__ . '/../../hub/index.html');
        $this->assertTrue(str_contains($html, 'id="view-template-picker"'), "Dedicated template picker view pane must exist.");
        $this->assertTrue(str_contains($html, 'id="modal-template-preview-large"'), "Enlarged sandboxed preview dialog must exist.");
        $this->assertTrue(str_contains($html, 'id="tp-card-pressel-premium-campanhas"'), "Pressel Premium card must exist.");
        $this->assertTrue(str_contains($html, 'id="tp-card-booster-padrao"'), "Booster Padrao card must exist.");
        $this->assertTrue(str_contains($html, 'id="tp-card-pagina-em-branco"'), "Pagina em Branco card must exist.");

        $css = file_get_contents(__DIR__ . '/../../hub/elementor-builder.css');
        $this->assertTrue(str_contains($css, '.template-picker-screen'), "CSS must include .template-picker-screen.");
        $this->assertTrue(str_contains($css, '.tp-card'), "CSS must include .tp-card.");
        $this->assertTrue(str_contains($css, '.tp-mini-pressel'), "CSS must include .tp-mini-pressel.");
        $this->assertTrue(str_contains($css, '.tp-mini-booster'), "CSS must include .tp-mini-booster.");
        $this->assertTrue(str_contains($css, '.tp-mini-blank'), "CSS must include .tp-mini-blank.");
        $this->assertTrue(str_contains($css, '.tp-modal-preview-dialog'), "CSS must include .tp-modal-preview-dialog.");

        $js = file_get_contents(__DIR__ . '/../../hub/elementor-builder.js');
        $this->assertTrue(str_contains($js, 'abrirTelaSelecaoModelos'), "JS must expose abrirTelaSelecaoModelos.");
        $this->assertTrue(str_contains($js, 'selecionarModeloPicker'), "JS must expose selecionarModeloPicker.");
        $this->assertTrue(str_contains($js, 'ampliarPreviaModal'), "JS must expose ampliarPreviaModal.");
        $this->assertTrue(str_contains($js, 'fecharPreviaModal'), "JS must expose fecharPreviaModal.");
        $this->assertTrue(str_contains($js, 'confirmarUsoModelo'), "JS must expose confirmarUsoModelo.");
    }

    public function testLandingEnxutaTemplateIsRegisteredAndEditableByPanel(): void
    {
        $html = file_get_contents(__DIR__ . '/../../hub/index.html');
        $js = file_get_contents(__DIR__ . '/../../hub/elementor-builder.js');
        $source = file_get_contents(__DIR__ . '/../../hub/landing-enxuta-template.js');
        $this->assertTrue(str_contains($html, 'landing-enxuta-template.js'), "Landing Enxuta template script must be loaded.");
        $this->assertTrue(str_contains($html, 'id="builder-page-theme"'), "Page appearance panel must exist.");
        $this->assertTrue(str_contains($js, 'function enviarImagemDoBloco'), "Panel must offer image upload.");
        $this->assertTrue(str_contains($js, 'function aplicarTemaDaPagina'), "Panel must change fonts and accent color.");

        foreach (['landing-enxuta' => 'impacto', 'landing-enxuta-velocidade' => 'velocidade', 'landing-enxuta-elegante' => 'elegante', 'landing-enxuta-moderna' => 'moderna'] as $model => $variant) {
            $this->assertTrue(str_contains($html, 'id="tp-card-' . $model . '"'), "Card for {$model} must exist.");
            $this->assertTrue(str_contains($js, "landingVariant:'{$variant}'"), "Picker must map {$model} to the {$variant} fonts.");
            $this->assertTrue(str_contains($source, "    {$variant}: {"), "Template must define the {$variant} fonts.");
        }

        $start = strpos($source, '<!DOCTYPE html>');
        $end = strpos($source, '</html>');
        $this->assertTrue($start !== false && $end !== false, "Template must contain a full HTML document.");
        // Interpolações do template literal (título, fontes, tema) viram texto simples para o parser.
        $page = preg_replace('/\$\{[^}]*\}/', 'x', substr($source, $start, $end - $start + 7));

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $page);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $ids = [];
        foreach ($xpath->query('//*[@data-block-id]') as $node) {
            $id = $node->getAttribute('data-block-id');
            $this->assertTrue(!isset($ids[$id]), "Block id '{$id}' must be unique.");
            $ids[$id] = true;

            $type = $node->getAttribute('data-block-type');
            if (in_array($type, ['heading', 'text', 'button', 'link'], true)) {
                // O painel troca o texto inteiro desses blocos; tags internas seriam apagadas.
                $this->assertTrue($xpath->query('./*', $node)->length === 0, "Text block '{$id}' must contain only text.");
            }
            if ($type === 'button') {
                $this->assertTrue($node->getAttribute('data-dest-type') !== '', "Button '{$id}' must declare data-dest-type.");
            }
        }

        $images = $xpath->query('//*[@data-block-type="image"]');
        $this->assertTrue($images->length >= 1, "Template must have an editable image block.");
        $this->assertTrue($xpath->query('.//img', $images->item(0))->length >= 1, "Image block must contain an <img>.");
    }

    public function testIndependentDraftCreationFromTemplatePicker(): void
    {
        $templates = [
            ['slug_prefix' => 'ranger-v6', 'tpl' => 'pressel-premium', 'name' => 'Pressel Premium — Campanhas'],
            ['slug_prefix' => 'booster', 'tpl' => 'default', 'name' => 'Página Booster Padrão'],
            ['slug_prefix' => 'pagina', 'tpl' => 'blank', 'name' => 'Página em Branco'],
        ];

        $createdIds = [];

        foreach ($templates as $t) {
            $uniqueSlug = $t['slug_prefix'] . '-' . bin2hex(random_bytes(3));
            $initialContent = "<!-- Template {$t['tpl']} content -->";

            $stmt = $this->pdo->prepare("
                INSERT INTO funnels (
                    workspace_id, name, slug, template_slug,
                    draft_content, published_content, html_content,
                    status
                ) VALUES (1, ?, ?, ?, ?, NULL, ?, 'draft')
            ");
            $stmt->execute([
                $t['name'],
                $uniqueSlug,
                $t['tpl'],
                $initialContent,
                $initialContent
            ]);

            $id = (int)$this->pdo->lastInsertId();
            $this->assertTrue($id > 0, "Created draft funnel ID must be positive.");
            $this->assertFalse(in_array($id, $createdIds, true), "Each created draft must have a unique ID.");
            $createdIds[] = $id;

            // Verificar persistência independente
            $stmtVerify = $this->pdo->prepare("SELECT id, name, slug, template_slug, status, published_content, draft_content FROM funnels WHERE id = ?");
            $stmtVerify->execute([$id]);
            $row = $stmtVerify->fetch(PDO::FETCH_ASSOC);

            $this->assertEquals($t['name'], $row['name']);
            $this->assertEquals($uniqueSlug, $row['slug']);
            $this->assertEquals($t['tpl'], $row['template_slug']);
            $this->assertEquals('draft', $row['status']);
            $this->assertTrue($row['published_content'] === null, "Draft published_content must be null.");
            $this->assertEquals($initialContent, $row['draft_content']);
        }

        // Limpeza dos 3 funis criados
        foreach ($createdIds as $id) {
            $this->pdo->prepare("DELETE FROM funnels WHERE id = ?")->execute([$id]);
        }
    }
}
