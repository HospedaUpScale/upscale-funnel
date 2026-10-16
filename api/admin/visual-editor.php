<?php
/**
 * Editor Visual (GrapesJS) — carregar / salvar rascunho / publicar.
 *
 * Pensado para coexistir com o editor em CodeMirror e o builder "Elementor-like"
 * ja existentes. Marca a pagina com editor_type='visual' e armazena o projeto
 * GrapesJS (getProjectData) em grapesjs_data; publica o HTML/CSS renderizado em
 * published_content, para a rota publica (funnel.php) servir sem alteracao.
 *
 * Rotas (POST com ?action=<verbo>):
 *   action=load            : carrega um funnel (por id ou por template base)
 *   action=save-draft      : salva rascunho (JSON do projeto + HTML gerado)
 *   action=publish         : publica a revisao atual (precisa salvar antes)
 *
 * Autenticacao: Bearer admin token (mesma do restante do painel).
 * Controle de revisao: cada save/publish incrementa `revision`. O cliente deve
 * enviar `expected_revision`; conflito retorna 409 com o estado atual.
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Http\JsonResponse;
use App\Campaigns\CampaignPage;

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
}

if ($request->method() !== 'POST') {
    JsonResponse::error('Use POST para acessar o editor.', 405);
}

$action = (string)($request->get('action') ?? ($request->all()['action'] ?? ''));
$input = $request->all();

// Workspace alvo (com checagem de escopo)
$targetWsId = (int)($input['workspace_id'] ?? $request->get('workspace_id') ?? ($auth['workspace']->id ?? 0));
if ($auth && $auth['role'] !== 'super_admin') {
    $ownId = $auth['workspace']->id;
    if ($ownId !== $targetWsId) {
        $allowed = array_map(fn($w) => $w->id, $wsRepo->findDescendants($ownId));
        if (!in_array($targetWsId, $allowed, true)) {
            JsonResponse::error('Forbidden for this workspace', 403);
        }
    }
}
if ($targetWsId <= 0) {
    JsonResponse::error('workspace_id obrigatorio.', 400);
}

$targetCampaignId = (int)($input['campaign_id'] ?? 0);
$campaignContext = null;
if ($targetCampaignId > 0) {
    $campaignContext = CampaignPage::campaign($pdo, $targetWsId, $targetCampaignId);
    if (!$campaignContext) JsonResponse::error('Campanha não encontrada neste workspace.', 404);
}
if ($action === 'load-campaign') {
    if (!$campaignContext) JsonResponse::error('campaign_id obrigatório.', 422);
    $offer = CampaignPage::offer($pdo, $campaignContext);
    $stmt = $pdo->prepare('SELECT id, status FROM funnels WHERE workspace_id = ? AND campaign_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$targetWsId, $targetCampaignId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $draft = json_decode($offer['draft_config'] ?? '', true) ?: [];
    $theme = $draft['theme'] ?? (json_decode($offer['theme_config'] ?? '', true) ?: []);
    JsonResponse::send(['status' => 'success', 'campaign' => ['id' => (int)$campaignContext['id'], 'title' => $campaignContext['title']],
        'offer' => ['id' => (int)$offer['id'], 'title' => $draft['title'] ?? $offer['title'], 'slug' => $offer['slug'], 'mechanic' => $theme['mechanic'] ?? 'scratch', 'price' => (float)$offer['price'], 'is_published' => (bool)$offer['is_published'], 'public_url' => '/o/' . $offer['slug']], 'page' => $page]);
}

// A biblioteca guarda snapshots independentes; nenhuma ação publica uma página.
if ($action === 'list-templates') {
    $stmt = $pdo->prepare('SELECT id, name, category, cover_url, created_at FROM page_templates WHERE workspace_id = ? ORDER BY id DESC');
    $stmt->execute([$targetWsId]);
    JsonResponse::send(['status' => 'success', 'templates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'load-template' || $action === 'delete-template') {
    $templateId = (int)($input['template_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM page_templates WHERE id = ? AND workspace_id = ?');
    $stmt->execute([$templateId, $targetWsId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$template) JsonResponse::error('Modelo não encontrado neste workspace.', 404);
    if ($action === 'delete-template') {
        $pdo->prepare('DELETE FROM page_templates WHERE id = ? AND workspace_id = ?')->execute([$templateId, $targetWsId]);
        JsonResponse::send(['status' => 'success']);
    }
    $template['project_data'] = json_decode($template['project_data'], true);
    JsonResponse::send(['status' => 'success', 'template' => $template]);
}

if ($action === 'save-template') {
    $name = trim((string)($input['name'] ?? ''));
    $category = trim((string)($input['category'] ?? 'Personalizado')) ?: 'Personalizado';
    $cover = trim((string)($input['cover_url'] ?? ''));
    if ($name === '' || mb_strlen($name) > 150 || mb_strlen($category) > 80) {
        JsonResponse::error('Informe um nome de até 150 caracteres e categoria de até 80.', 422);
    }
    if ($cover !== '' && (strlen($cover) > 2048 || !filter_var($cover, FILTER_VALIDATE_URL) || strtolower((string)parse_url($cover, PHP_URL_SCHEME)) !== 'https')) {
        JsonResponse::error('A capa deve usar um endereço HTTPS válido.', 422);
    }
    $project = $input['project_data'] ?? null;
    $steps = is_array($project) ? ($project['upscaleFlow']['steps'] ?? null) : null;
    if (!is_array($steps) || count($steps) < 1 || count($steps) > 50) {
        JsonResponse::error('O modelo deve conter entre 1 e 50 etapas.', 422);
    }
    foreach ($steps as $step) {
        if (!is_array($step) || !is_string($step['id'] ?? null) || !is_string($step['name'] ?? null) || !is_string($step['html'] ?? null)) {
            JsonResponse::error('Etapa do modelo inválida.', 422);
        }
    }
    $json = json_encode($project, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || strlen($json) > 4 * 1024 * 1024) JsonResponse::error('Modelo acima do limite de 4 MB.', 413);
    $pdo->prepare('INSERT INTO page_templates (workspace_id, name, category, cover_url, project_data) VALUES (?, ?, ?, ?, ?)')
        ->execute([$targetWsId, $name, $category, $cover, $json]);
    JsonResponse::send(['status' => 'success', 'template_id' => (int)$pdo->lastInsertId()]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function ve_slug(string $s): string
{
    $s = strtolower(trim($s));
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    return $s === '' ? 'pagina' : substr($s, 0, 80);
}

function ve_fetch_funnel(PDO $pdo, int $wsId, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM funnels WHERE id = ? AND workspace_id = ? LIMIT 1");
    $stmt->execute([$id, $wsId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Monta um documento HTML publico completo a partir do HTML/CSS enviados pelo
 * editor. Nao carrega GrapesJS nem recursos do painel. Normaliza <head>: charset,
 * viewport, titulo e descricao.
 */
function ve_render_public_document(string $html, string $css, string $title, string $description, string $extraHead = ''): string
{
    $safeTitle = htmlspecialchars($title !== '' ? $title : 'upScale', ENT_QUOTES, 'UTF-8');
    $safeDesc  = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

    $doc  = "<!doctype html>\n";
    $doc .= "<html lang=\"pt-BR\">\n<head>\n";
    $doc .= "<meta charset=\"UTF-8\">\n";
    $doc .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
    $doc .= "<title>{$safeTitle}</title>\n";
    if ($description !== '') {
        $doc .= "<meta name=\"description\" content=\"{$safeDesc}\">\n";
    }
    $doc .= $extraHead;
    $doc .= "<style>\n{$css}\n</style>\n";
    $doc .= "</head>\n<body>\n";
    $doc .= $html;
    $doc .= "\n</body>\n</html>";
    return $doc;
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: load
// Carrega um funil (por id). Se id=0 e template=<slug>, devolve o modelo padrao.
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'load') {
    $id = (int)($input['id'] ?? $request->get('id') ?? 0);

    if ($id > 0) {
        $funnel = ve_fetch_funnel($pdo, $targetWsId, $id);
        if ($funnel && $targetCampaignId > 0 && (int)$funnel['campaign_id'] !== $targetCampaignId) JsonResponse::error('Página pertence a outra campanha.', 403);
        if (!$funnel) {
            JsonResponse::error('Pagina nao encontrada neste workspace.', 404);
        }

        $isVisual = ($funnel['editor_type'] ?? 'code') === 'visual';
        $projectData = null;
        if ($isVisual && !empty($funnel['grapesjs_data'])) {
            $decoded = json_decode((string)$funnel['grapesjs_data'], true);
            if (is_array($decoded)) $projectData = $decoded;
        }

        JsonResponse::send([
            'status'         => 'success',
            'id'             => (int)$funnel['id'],
            'name'           => (string)$funnel['name'],
            'slug'           => (string)$funnel['slug'],
            'editor_type'    => $isVisual ? 'visual' : 'code',
            'revision'       => (int)($funnel['revision'] ?? 0),
            'campaign_id'    => !empty($funnel['campaign_id']) ? (int)$funnel['campaign_id'] : null,
            'project_format' => (string)($funnel['project_format_version'] ?? '1'),
            'is_published'   => ($funnel['status'] === 'published'),
            'project_data'   => $projectData,
            // paginas legadas: devolve o HTML para o editor saber que nao e visual
            'legacy_html'    => $isVisual ? null : (string)($funnel['draft_content'] ?? $funnel['html_content'] ?? ''),
        ]);
    }

    // Novo: devolve o modelo base de landing page
    require_once __DIR__ . '/../../src/VisualEditor/DefaultTemplate.php';
    JsonResponse::send([
        'status'         => 'success',
        'id'             => 0,
        'name'           => 'Nova pagina',
        'slug'           => '',
        'editor_type'    => 'visual',
        'revision'       => 0,
        'project_format' => '1',
        'is_published'   => false,
        'project_data'   => \App\VisualEditor\DefaultTemplate::projectData(),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: save-draft
// Payload: { id, name, slug, project_data, html, css, expected_revision, title, description }
// Salva o JSON e o HTML renderizado em draft_content. NAO toca em published_content.
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'save-draft') {
    $id = (int)($input['id'] ?? 0);
    $name = trim((string)($input['name'] ?? 'Nova pagina'));
    $slug = ve_slug((string)($input['slug'] ?? $name));
    if (in_array($slug, ['api', 'hub', 'assets', 'vendor', 'f', 'o', 'src', 'storage', 'config', 'scripts', 'migrations', 'index', 'admin', 'login'], true)) {
        JsonResponse::error('Este endereço é reservado. Escolha outro.', 422);
    }
    $expectedRev = (int)($input['expected_revision'] ?? 0);
    $projectData = $input['project_data'] ?? null;
    $html = (string)($input['html'] ?? '');
    $css  = (string)($input['css'] ?? '');

    if (!is_array($projectData)) {
        JsonResponse::error('project_data invalido (deve ser objeto JSON do GrapesJS).', 422);
    }
    // Limite defensivo de payload
    $jsonData = json_encode($projectData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonData === false || strlen($jsonData) > 4 * 1024 * 1024) {
        JsonResponse::error('O projeto esta muito grande (limite 4 MB).', 413);
    }
    if (strlen($html) > 2 * 1024 * 1024) {
        JsonResponse::error('O HTML gerado esta muito grande.', 413);
    }

    try {
        if ($id > 0) {
            $cur = ve_fetch_funnel($pdo, $targetWsId, $id);
            if (!$cur) JsonResponse::error('Pagina nao encontrada.', 404);
            if ($targetCampaignId > 0 && (int)$cur['campaign_id'] !== $targetCampaignId) JsonResponse::error('Página pertence a outra campanha.', 403);
            // Conflito entre abas / sessoes
            if ((int)($cur['revision'] ?? 0) !== $expectedRev) {
                JsonResponse::send([
                    'status'  => 'error',
                    'code'    => 'revision_conflict',
                    'message' => 'A pagina foi alterada em outra aba. Recarregue antes de salvar.',
                    'current_revision' => (int)$cur['revision'],
                ], 409);
            }

            // Garante slug unico no workspace
            $slugCheck = $pdo->prepare("SELECT id FROM funnels WHERE workspace_id = ? AND slug = ? AND id <> ? LIMIT 1");
            $slugCheck->execute([$targetWsId, $slug, $id]);
            if ($slugCheck->fetchColumn()) {
                $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            }

            $newRev = (int)$cur['revision'] + 1;
            $update = $pdo->prepare("
                UPDATE funnels SET
                    name = ?, slug = ?,
                    editor_type = 'visual',
                    draft_content = ?, html_content = COALESCE(NULLIF(html_content, ''), ?),
                    grapesjs_data = ?,
                    revision = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND workspace_id = ? AND revision = ?
            ");
            $update->execute([$name, $slug, $html, $html, $jsonData, $newRev, $id, $targetWsId, $expectedRev]);
            if ($update->rowCount() !== 1) {
                JsonResponse::error('A página foi alterada em outra aba. Recarregue antes de salvar.', 409, ['code' => 'revision_conflict']);
            }
            $funnelId = $id;
        } else {
            // Novo rascunho: cria com slug unico
            $base = $slug;
            $n = 1;
            while (true) {
                $try = $pdo->prepare("SELECT id FROM funnels WHERE workspace_id = ? AND slug = ? LIMIT 1");
                $try->execute([$targetWsId, $slug]);
                if (!$try->fetchColumn()) break;
                $n++;
                $slug = $base . '-' . $n;
                if ($n > 50) { $slug = $base . '-' . substr(bin2hex(random_bytes(3)), 0, 6); break; }
            }

            $pdo->prepare("
                INSERT INTO funnels (
                    workspace_id, campaign_id, name, slug, html_content,
                    template_slug, editor_type, draft_content, grapesjs_data,
                    status, revision
                ) VALUES (?, ?, ?, ?, ?, 'default', 'visual', ?, ?, 'draft', 1)
            ")->execute([$targetWsId, $targetCampaignId ?: null, $name, $slug, $html, $html, $jsonData]);
            $funnelId = (int)$pdo->lastInsertId();
            $newRev = 1;
        }

        JsonResponse::send([
            'status'   => 'success',
            'id'       => $funnelId,
            'slug'     => $slug,
            'revision' => $newRev,
            'saved_at' => date('c'),
        ]);
    } catch (\Throwable $e) {
        error_log('visual-editor save-draft: ' . $e->getMessage());
        JsonResponse::error('Falha ao salvar: ' . $e->getMessage(), 500);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: publish
// Payload: { id, expected_revision, title, description }
// Promove o rascunho atual a versao publica. Preserva a versao publicada anterior
// ate a nova gravacao concluir; se qualquer coisa falhar, nao sobrescreve.
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'publish') {
    $id = (int)($input['id'] ?? 0);
    $expectedRev = (int)($input['expected_revision'] ?? 0);
    $title = trim((string)($input['title'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));

    if ($id <= 0) {
        JsonResponse::error('id obrigatorio para publicar.', 422);
    }

    $cur = ve_fetch_funnel($pdo, $targetWsId, $id);
    if (!$cur) JsonResponse::error('Pagina nao encontrada.', 404);
    if ($targetCampaignId > 0 && (int)$cur['campaign_id'] !== $targetCampaignId) JsonResponse::error('Página pertence a outra campanha.', 403);

    if ((int)($cur['revision'] ?? 0) !== $expectedRev) {
        JsonResponse::send([
            'status'  => 'error',
            'code'    => 'revision_conflict',
            'message' => 'A pagina foi alterada em outra aba. Salve-a novamente antes de publicar.',
            'current_revision' => (int)$cur['revision'],
        ], 409);
    }

    $draftHtml = (string)($cur['draft_content'] ?? '');
    $projectRaw = (string)($cur['grapesjs_data'] ?? '');
    if (trim($draftHtml) === '' || trim($projectRaw) === '') {
        JsonResponse::error('Nao ha rascunho para publicar. Salve a pagina antes.', 422);
    }

    // CSS do GrapesJS fica embutido no HTML via <style>; o save-draft ja grava
    // tudo pronto em draft_content, entao o published_content recebe o mesmo.
    $publishedHtml = $draftHtml;
    $pageTitle = $title !== '' ? $title : (string)$cur['name'];
    $campaignOffer = null;

    try {
        $pdo->beginTransaction();
        $update = $pdo->prepare("
            UPDATE funnels SET
                status = 'published',
                published_content = ?,
                html_content = ?,
                grapesjs_published = ?,
                published_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP,
                revision = revision + 1
            WHERE id = ? AND workspace_id = ? AND revision = ?
        ");
        $update->execute([$publishedHtml, $publishedHtml, $projectRaw, $id, $targetWsId, $expectedRev]);
        if ($update->rowCount() !== 1) {
            $pdo->rollBack();
            JsonResponse::error('A página foi alterada em outra aba. Recarregue antes de publicar.', 409, ['code' => 'revision_conflict']);
        }
        if (!empty($cur['campaign_id'])) {
            $campaign = CampaignPage::campaign($pdo, $targetWsId, (int)$cur['campaign_id']);
            if (!$campaign) throw new \RuntimeException('Campanha da página não encontrada.');
            $project = json_decode($projectRaw, true) ?: [];
            $mechanic = $project['upscaleFlow']['campaignMechanic'] ?? null;
            $campaignOffer = CampaignPage::publishOffer($pdo, $campaign, $mechanic);
            $pdo->prepare("UPDATE campaigns SET status = 'published', published_at = CURRENT_TIMESTAMP, ended_at = NULL WHERE id = ? AND workspace_id = ?")->execute([$campaign['id'], $targetWsId]);
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('visual-editor publish: ' . $e->getMessage());
        JsonResponse::error('Falha ao publicar: ' . $e->getMessage(), 500);
    }

    $cur2 = ve_fetch_funnel($pdo, $targetWsId, $id);
    JsonResponse::send([
        'status'   => 'success',
        'id'       => $id,
        'slug'     => $cur2['slug'],
        'revision' => (int)$cur2['revision'],
        'public_url' => $campaignOffer ? '/o/' . $campaignOffer['slug'] : '/f/' . ltrim($cur2['slug'], '/'),
        'published_at' => $cur2['published_at'] ?? date('c'),
    ]);
}

JsonResponse::error('Acao desconhecida. Use action=load | save-draft | publish.', 400);
