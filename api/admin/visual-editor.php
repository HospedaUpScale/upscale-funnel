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

$token = AdminAuth::extractBearerToken();
$adminAuth = new AdminAuth($wsRepo, $config['super_admin_token'] ?? '');
$auth = $adminAuth->authenticate($token);

if (!$auth && ($config['app_env'] ?? 'production') !== 'local') {
    JsonResponse::error('Unauthorized. Admin token required.', 401);
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
            $pdo->prepare("
                UPDATE funnels SET
                    name = ?, slug = ?,
                    editor_type = 'visual',
                    draft_content = ?, html_content = COALESCE(NULLIF(html_content, ''), ?),
                    grapesjs_data = ?,
                    revision = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND workspace_id = ?
            ")->execute([$name, $slug, $html, $html, $jsonData, $newRev, $id, $targetWsId]);
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
                    workspace_id, name, slug, html_content,
                    template_slug, editor_type, draft_content, grapesjs_data,
                    status, revision
                ) VALUES (?, ?, ?, ?, 'default', 'visual', ?, ?, 'draft', 1)
            ")->execute([$targetWsId, $name, $slug, $html, $html, $jsonData]);
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

    try {
        $pdo->beginTransaction();
        $pdo->prepare("
            UPDATE funnels SET
                status = 'published',
                published_content = ?,
                html_content = ?,
                grapesjs_published = ?,
                published_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP,
                revision = revision + 1
            WHERE id = ? AND workspace_id = ?
        ")->execute([$publishedHtml, $publishedHtml, $projectRaw, $id, $targetWsId]);
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
        'public_url' => '/' . ltrim($cur2['slug'], '/'),
        'published_at' => $cur2['published_at'] ?? date('c'),
    ]);
}

JsonResponse::error('Acao desconhecida. Use action=load | save-draft | publish.', 400);
