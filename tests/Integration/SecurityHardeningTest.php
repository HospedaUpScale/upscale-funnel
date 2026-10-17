<?php

namespace Tests\Integration;

use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use PDO;
use Tests\TestCase;

/**
 * Testes de ponta a ponta das correções de segurança: sobe o servidor embutido do PHP
 * com um banco SQLite temporário e chama os endpoints por HTTP, como um atacante faria.
 */
class SecurityHardeningTest extends TestCase
{
    private const ADMIN_PASSWORD = 'senha-master-de-teste-123';
    private const SUPER_TOKEN = 'super-token-de-teste-nunca-enviado-ao-navegador';
    private const POSTBACK_SECRET = 'segredo-postback-teste';

    private static ?array $server = null;
    private static string $baseUrl = '';
    private static string $dbPath = '';
    private static ?PDO $pdo = null;
    private static array $ws = [];

    protected function setUp(): void
    {
        if (self::$server !== null) {
            return;
        }

        $root = realpath(__DIR__ . '/../..');
        self::$dbPath = sys_get_temp_dir() . '/upscale_sec_' . getmypid() . '.sqlite';
        @unlink(self::$dbPath);

        $env = $this->serverEnv();

        // Migrations no banco temporário
        $proc = proc_open([PHP_BINARY, $root . '/scripts/migrate.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $env);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        proc_close($proc);

        self::$pdo = new PDO('sqlite:' . self::$dbPath);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Dois workspaces com tokens de API conhecidos
        foreach (['sec-a', 'sec-b'] as $slug) {
            $apiToken = 'api_' . $slug . '_' . bin2hex(random_bytes(6));
            self::$pdo->prepare(
                "INSERT INTO workspaces (type, name, slug, api_token_hash, admin_token_hash, status) VALUES ('merchant', ?, ?, ?, ?, 'active')"
            )->execute([$slug, $slug, hash('sha256', $apiToken), hash('sha256', 'adm_' . $apiToken)]);
            self::$ws[$slug] = ['id' => (int) self::$pdo->lastInsertId(), 'api_token' => $apiToken];
        }

        $port = random_int(20000, 40000);
        self::$baseUrl = "http://127.0.0.1:{$port}";
        self::$server = [
            'proc' => proc_open(
                [PHP_BINARY, '-S', "127.0.0.1:{$port}", $root . '/router.php'],
                [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
                $pipes,
                $root,
                $env
            ),
        ];

        for ($i = 0; $i < 50; $i++) {
            if (@fsockopen('127.0.0.1', $port)) {
                break;
            }
            usleep(100000);
        }

        register_shutdown_function(function () {
            if (self::$server) {
                proc_terminate(self::$server['proc']);
            }
            @unlink(self::$dbPath);
        });
    }

    private function serverEnv(): array
    {
        $env = getenv();
        unset($env['APP_ENV']); // valida o padrão seguro (produção)
        return array_merge($env, [
            'ANTHROPIC_API_KEY' => '', // Nenhum teste deve usar credenciais reais.
            'DB_DRIVER'        => 'sqlite',
            'DB_SQLITE_PATH'   => self::$dbPath,
            'SUPER_ADMIN_TOKEN'=> self::SUPER_TOKEN,
            'ADMIN_USER'       => 'admin',
            'ADMIN_PASSWORD'   => self::ADMIN_PASSWORD,
            'POSTBACK_SECRET'  => self::POSTBACK_SECRET,
        ]);
    }

    private function http(string $method, string $path, ?array $json = null, array $headers = []): array
    {
        $hasHost = (bool)array_filter($headers, fn($h) => str_starts_with(strtolower($h), 'host:'));
        $headerLines = array_merge(['Content-Type: application/json'], $hasHost ? [] : ['Host: 127.0.0.1'], $headers);
        $ctx = stream_context_create(['http' => [
            'method'        => $method,
            'header'        => implode("\r\n", $headerLines),
            'content'       => $json !== null ? json_encode($json) : '',
            'ignore_errors' => true,
            'timeout'       => 10,
        ]]);
        $body = @file_get_contents(self::$baseUrl . $path, false, $ctx);
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) {
                $status = (int) $m[1];
            }
        }
        return [$status, json_decode((string) $body, true), (string) $body];
    }

    /** Cria um pedido pendente com transação Pix no workspace informado. */
    private function pendingOrder(string $slug): array
    {
        $orders = new OrderRepository(self::$pdo);
        $txs = new TransactionRepository(self::$pdo);
        $order = $orders->create([
            'workspace_id'      => self::$ws[$slug]['id'],
            'status'            => 'pending',
            'external_order_id' => 'ext_' . bin2hex(random_bytes(4)),
            'total_amount'      => 90,
            'upsell_amount'     => 90,
        ]);
        $tx = $txs->create([
            'order_id'      => $order['id'],
            'type'          => 'upsell_order',
            'status'        => 'pending',
            'gateway'       => 'mock',
            'gateway_tx_id' => 'mock_tx_' . bin2hex(random_bytes(6)),
            'amount'        => 90,
            'expires_at'    => date('Y-m-d H:i:s', time() + 900),
        ]);
        return ['order' => $order, 'tx' => $tx];
    }

    private function txStatus(int $txId): string
    {
        $stmt = self::$pdo->prepare('SELECT status FROM transactions WHERE id = ?');
        $stmt->execute([$txId]);
        return (string) $stmt->fetchColumn();
    }

    public function testPixUsesPublishedPriceInsteadOfOfferDraft(): void
    {
        $wsId = self::$ws['sec-a']['id'];
        self::$pdo->prepare("INSERT INTO upsell_offers (workspace_id, title, price, quota_count, slug, is_active, is_published, published_config) VALUES (?, 'Preço em rascunho', 3.50, 1, ?, 1, 1, ?)")
            ->execute([$wsId, 'published-price-' . bin2hex(random_bytes(4)), json_encode(['title' => 'Preço publicado', 'price' => 7])]);
        $id = (int)self::$pdo->lastInsertId();
        [$status, $data] = $this->http('POST', '/api/pix-upsell.php',
            ['offer_id' => $id, 'name' => 'Teste de preço publicado'], ['Host: sec-a']);
        $this->assertEquals(201, $status);
        $this->assertEquals(7.0, (float)$data['pix']['amount']);
        $q = self::$pdo->prepare('SELECT offer_id FROM orders WHERE id = ?');
        $q->execute([$data['order_id']]);
        $this->assertEquals($id, (int)$q->fetchColumn());
    }

    public function testCampaignSupportsMultipleIndependentPagesAndExplicitPrimary(): void
    {
        $wsId = self::$ws['sec-a']['id'];
        $headers = ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']];
        [, $created] = $this->http('POST', '/api/admin/campaigns.php?workspace_id=' . $wsId,
            ['action' => 'create', 'title' => 'Múltiplas páginas', 'ticket_price' => 7, 'status' => 'draft'], $headers);
        $campaignId = $created['campaign_id'];
        $ids = [];
        try {
            foreach (['A', 'B'] as $marker) {
                [$status, $page] = $this->http('POST', '/api/admin/funnels.php', [
                    'workspace_id' => $wsId, 'campaign_id' => $campaignId, 'action' => 'publish',
                    'name' => 'Página ' . $marker, 'slug' => 'many-' . $campaignId . '-' . strtolower($marker),
                    'html_content' => '<h1>PÁGINA ' . $marker . '</h1><iframe src="{{campaign_offer_url}}&experience=direct"></iframe>',
                    'settings_config' => ['experience' => ['mechanic' => 'direct']],
                ], $headers);
                $this->assertEquals(200, $status);
                $ids[] = $page['funnel_id'];
                [$code, , $html] = $this->http('GET', parse_url($page['public_url'], PHP_URL_PATH));
                $this->assertEquals(200, $code);
                $this->assertStringContains('PÁGINA ' . $marker, $html);
            }
            $campaignUrl = $page['campaign_url'];
            [, , $main] = $this->http('GET', $campaignUrl);
            $this->assertStringContains('PÁGINA A', $main, 'Publicar B não pode substituir a principal A.');
            [, $list] = $this->http('GET', '/api/admin/funnels.php?summary=1&workspace_id=' . $wsId . '&campaign_id=' . $campaignId, null, $headers);
            $this->assertEquals(2, count($list['funnels']));
            $this->assertFalse(isset($list['funnels'][0]['html_content']));
            $this->assertEquals('Múltiplas páginas', $list['funnels'][0]['campaign_name']);
            [$foreign] = $this->http('POST', '/api/admin/funnels.php',
                ['workspace_id' => self::$ws['sec-b']['id'], 'id' => $ids[1], 'action' => 'set_primary'],
                ['Authorization: Bearer adm_' . self::$ws['sec-b']['api_token']]);
            $this->assertEquals(422, $foreign);
            [$primary] = $this->http('POST', '/api/admin/funnels.php', ['workspace_id' => $wsId, 'id' => $ids[1], 'action' => 'set_primary'], $headers);
            $this->assertEquals(200, $primary);
            [, , $mainB] = $this->http('GET', $campaignUrl);
            $this->assertStringContains('PÁGINA B', $mainB);
            [$unpublished] = $this->http('POST', '/api/admin/funnels.php', ['workspace_id' => $wsId, 'id' => $ids[1], 'action' => 'unpublish'], $headers);
            $this->assertEquals(200, $unpublished);
            [, , $fallback] = $this->http('GET', $campaignUrl);
            $this->assertFalse(str_contains($fallback, '<h1>PÁGINA B</h1>'));
            $this->assertStringContains('__INITIAL_OFFER_DATA__', $fallback);
            [$gone] = $this->http('GET', parse_url($page['public_url'], PHP_URL_PATH));
            $this->assertEquals(404, $gone);
            [, $duplicate] = $this->http('POST', '/api/admin/funnels.php', ['workspace_id' => $wsId, 'id' => $ids[0], 'action' => 'duplicate'], $headers);
            $this->assertEquals('success', $duplicate['status']);
            $ids[] = $duplicate['funnel_id'];
            $q = self::$pdo->prepare('SELECT campaign_id, status FROM funnels WHERE id = ?');$q->execute([$duplicate['funnel_id']]);$copy = $q->fetch();
            $this->assertEquals($campaignId, (int)$copy['campaign_id']);
            $this->assertEquals('draft', $copy['status']);
        } finally {
            foreach ($ids as $id) self::$pdo->prepare('DELETE FROM funnels WHERE id = ?')->execute([$id]);
            self::$pdo->prepare('DELETE FROM upsell_offers WHERE campaign_id = ?')->execute([$campaignId]);
            self::$pdo->prepare('DELETE FROM campaigns WHERE id = ?')->execute([$campaignId]);
        }
    }

    public function testClaudeRequiresAuthenticationAndWorkspaceAccess(): void
    {
        $path = '/api/admin/ai-copy.php?workspace_id=' . self::$ws['sec-a']['id'];
        [$anonymous] = $this->http('POST', $path, ['brief' => 'Melhorar', 'context' => []]);
        $this->assertEquals(401, $anonymous);
        [$foreign] = $this->http('POST', $path, ['brief' => 'Melhorar', 'context' => []],
            ['Authorization: Bearer adm_' . self::$ws['sec-b']['api_token']]);
        $this->assertEquals(403, $foreign);
        [$status, $data, $raw] = $this->http('GET', $path,
            null, ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']]);
        $this->assertEquals(200, $status);
        $this->assertArrayHasKey('configured', $data);
        $this->assertFalse($data['configured']);
        [$notConfigured] = $this->http('POST', $path, ['brief' => 'Melhorar', 'context' => []],
            ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']]);
        $this->assertEquals(503, $notConfigured);
        $this->assertArrayHasKey('quota', $data);
        $this->assertFalse(str_contains($raw, 'api_key'));
        $this->assertEquals(0, (int)self::$pdo->query("SELECT attempts FROM ai_generation_quota WHERE bucket = 'total'")->fetchColumn());
    }

    public function testPaymentWebhookRejectsRequestsWithoutToken(): void
    {
        $p = $this->pendingOrder('sec-a');
        [$status] = $this->http('POST', '/api/webhook.php', ['txid' => $p['tx']['gateway_tx_id'], 'status' => 'paid']);
        $this->assertEquals(401, $status, 'Webhook sem token deve responder 401.');
        $this->assertEquals('pending', $this->txStatus($p['tx']['id']), 'Pedido não pode ser pago sem token.');
    }

    public function testPaymentWebhookRejectsInvalidToken(): void
    {
        $p = $this->pendingOrder('sec-a');
        [$status] = $this->http('POST', '/api/webhook.php?token=invalido', ['txid' => $p['tx']['gateway_tx_id'], 'status' => 'paid']);
        $this->assertEquals(401, $status, 'Token inválido deve responder 401.');
        $this->assertEquals('pending', $this->txStatus($p['tx']['id']));
    }

    public function testPaymentWebhookRejectsTokenFromAnotherWorkspace(): void
    {
        $p = $this->pendingOrder('sec-a');
        [$status] = $this->http('POST', '/api/webhook.php', ['txid' => $p['tx']['gateway_tx_id'], 'status' => 'paid'],
            ['Authorization: Bearer ' . self::$ws['sec-b']['api_token']]);
        $this->assertEquals(404, $status, 'Token de outro workspace não pode localizar o pedido.');
        $this->assertEquals('pending', $this->txStatus($p['tx']['id']));
    }

    public function testPaymentWebhookDoesNotAssumePaidWithoutStatus(): void
    {
        $p = $this->pendingOrder('sec-a');
        [$status, $data] = $this->http('POST', '/api/webhook.php', ['txid' => $p['tx']['gateway_tx_id']],
            ['X-Webhook-Token: ' . self::$ws['sec-a']['api_token']]);
        $this->assertEquals(200, $status);
        $this->assertEquals('ignored', $data['status'] ?? null, 'Sem status explícito, o pagamento não pode ser confirmado.');
        $this->assertEquals('pending', $this->txStatus($p['tx']['id']));
    }

    public function testPaymentWebhookDoesNotTreatUnpaidAsPaid(): void
    {
        $p = $this->pendingOrder('sec-a');
        [, $data] = $this->http('POST', '/api/webhook.php', ['txid' => $p['tx']['gateway_tx_id'], 'status' => 'unpaid'],
            ['X-Webhook-Token: ' . self::$ws['sec-a']['api_token']]);
        $this->assertEquals('ignored', $data['status'] ?? null, '"unpaid" não pode ser lido como "paid".');
        $this->assertEquals('pending', $this->txStatus($p['tx']['id']));
    }

    public function testPaymentWebhookSettlesWithValidToken(): void
    {
        $p = $this->pendingOrder('sec-a');
        [$status, $data] = $this->http('POST', '/api/webhook.php?token=' . urlencode(self::$ws['sec-a']['api_token']),
            ['txid' => $p['tx']['gateway_tx_id'], 'status' => 'paid']);
        $this->assertEquals(200, $status);
        $this->assertEquals('success', $data['status'] ?? null);
        $this->assertEquals('paid', $this->txStatus($p['tx']['id']));
    }

    public function testWebhookLogsAreNotPublic(): void
    {
        [, , $body] = $this->http('GET', '/api/webhook.php?action=logs');
        $this->assertTrue(!str_contains($body, 'recent_webhooks'), 'GET ?action=logs não pode expor os webhooks recebidos.');
    }

    public function testHardcodedMasterPasswordsNoLongerWork(): void
    {
        foreach (['admin123', 'Master@2026'] as $pass) {
            [$status] = $this->http('POST', '/api/admin/login.php',
                ['type' => 'master', 'username' => 'admin', 'password' => $pass],
                ['X-Forwarded-For: 10.0.0.' . random_int(1, 250)]);
            $this->assertEquals(401, $status, "A senha fixa {$pass} não pode mais dar acesso master.");
        }
    }

    public function testMasterLoginIssuesExpiringSessionInsteadOfSuperToken(): void
    {
        [$status, $data] = $this->http('POST', '/api/admin/login.php',
            ['type' => 'master', 'username' => 'admin', 'password' => self::ADMIN_PASSWORD],
            ['X-Forwarded-For: 10.1.0.' . random_int(1, 250)]);
        $this->assertEquals(200, $status, 'Login master com a senha do ambiente deve funcionar.');
        $this->assertTrue(($data['token'] ?? '') !== self::SUPER_TOKEN, 'O SUPER_ADMIN_TOKEN não pode ser enviado ao navegador.');
        $this->assertTrue(!empty($data['expires_at']), 'A sessão master deve ter validade.');

        [$st, $auth] = $this->http('GET', '/api/admin/auth-status.php', null, ['Authorization: Bearer ' . $data['token']]);
        $this->assertEquals(200, $st);
        $this->assertEquals('super_admin', $auth['auth']['role'] ?? null, 'A sessão master deve ser reconhecida como super_admin.');
    }

    public function testAdminEndpointsRequireTokenByDefault(): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (is_file($envFile) && preg_match('/^\s*APP_ENV\s*=/m', (string) file_get_contents($envFile))) {
            $this->assertTrue(true); // .env local define APP_ENV; o padrão não se aplica aqui
            return;
        }
        [$status] = $this->http('GET', '/api/admin/workspaces.php');
        $this->assertEquals(401, $status, 'Sem APP_ENV, os endpoints de admin devem exigir token.');
    }

    public function testSalesWebhookRequiresToken(): void
    {
        [$status] = $this->http('POST', '/api/v1/webhook.php', ['order_id' => 'x1', 'amount' => 100, 'status' => 'paid']);
        $this->assertEquals(401, $status, 'Webhook de vendas sem token deve responder 401.');

        [$status2, $data] = $this->http('POST', '/api/v1/webhook.php', ['order_id' => 'x2', 'amount' => 100, 'status' => 'paid'],
            ['Authorization: Bearer ' . self::$ws['sec-b']['api_token']]);
        $this->assertEquals(200, $status2, 'Webhook de vendas com token válido deve funcionar.');
        $this->assertEquals('success', $data['status'] ?? null);
    }

    public function testWinnerLookupByPhoneRequiresAuth(): void
    {
        [$status] = $this->http('GET', '/api/v1/winner.php?phone=11999999999');
        $this->assertEquals(401, $status, 'Consulta de ganhador por telefone deve exigir autenticação.');

        [$status2] = $this->http('GET', '/api/v1/winner.php?order_id=x2');
        $this->assertEquals(401, $status2, 'Consulta de ganhador por pedido deve exigir autenticação.');
    }
    public function testVisualEditorRoundTripAndRevisionProtection(): void
    {
        $headers = ['Authorization: Bearer ' . self::SUPER_TOKEN];
        $wsId = self::$ws['sec-a']['id'];
        $path = '/api/admin/visual-editor.php';
        $slug = 'editor-roundtrip-' . bin2hex(random_bytes(4));
        $project = ['upscaleFlow' => ['template' => 'html', 'steps' => [
            ['id' => 'inicio', 'name' => 'HTML', 'raw' => true, 'html' => '<h1>Original</h1>']
        ]]];
        $payload = ['workspace_id' => $wsId, 'name' => 'HTML importado', 'slug' => $slug,
            'project_data' => $project, 'html' => '<!doctype html><html><body><h1>Original</h1></body></html>',
            'expected_revision' => 0];
        [$unauthorized] = $this->http('POST', $path . '?action=save-draft', $payload);
        $this->assertEquals(401, $unauthorized);
        [$status, $saved] = $this->http('POST', $path . '?action=save-draft', $payload, $headers);
        $this->assertEquals(200, $status);
        $id = (int) $saved['id'];
        try {
            [$draftStatus] = $this->http('GET', '/f/' . $slug);
            $this->assertEquals(404, $draftStatus, 'Rascunhos não podem aparecer na URL pública.');
            [$loadStatus, $loaded] = $this->http('POST', $path . '?action=load', ['workspace_id' => $wsId, 'id' => $id], $headers);
            $this->assertEquals(200, $loadStatus);
            $this->assertEquals($project, $loaded['project_data']);
            [$conflict] = $this->http('POST', $path . '?action=publish', ['workspace_id' => $wsId, 'id' => $id, 'expected_revision' => 0], $headers);
            $this->assertEquals(409, $conflict);
            [$pubStatus, $published] = $this->http('POST', $path . '?action=publish', ['workspace_id' => $wsId, 'id' => $id, 'expected_revision' => 1], $headers);
            $this->assertEquals(200, $pubStatus);
            $this->assertEquals('/f/' . $slug, $published['public_url']);
            [$publicStatus, , $html] = $this->http('GET', '/f/' . $slug);
            $this->assertEquals(200, $publicStatus);
            $this->assertStringContains('<h1>Original</h1>', $html);
            $payload['id'] = $id;
            $payload['expected_revision'] = 2;
            $payload['html'] = '<h1>Novo rascunho</h1>';
            [$updated] = $this->http('POST', $path . '?action=save-draft', $payload, $headers);
            $this->assertEquals(200, $updated);
            [, , $stillPublished] = $this->http('GET', '/f/' . $slug);
            $this->assertStringContains('<h1>Original</h1>', $stillPublished, 'Salvar não deve substituir a versão publicada.');
            [$staleSave] = $this->http('POST', $path . '?action=save-draft', $payload, $headers);
            $this->assertEquals(409, $staleSave);
        } finally {
            self::$pdo->prepare('DELETE FROM funnels WHERE id = ?')->execute([$id]);
        }
    }

    public function testVisualEditorRejectsReservedAddressAndOtherWorkspace(): void
    {
        $ws = self::$ws['sec-a'];
        $headers = ['Authorization: Bearer adm_' . $ws['api_token']];
        [$forbidden] = $this->http('POST', '/api/admin/visual-editor.php?action=load',
            ['workspace_id' => self::$ws['sec-b']['id'], 'id' => 0], $headers);
        $this->assertEquals(403, $forbidden);
        [$reserved] = $this->http('POST', '/api/admin/visual-editor.php?action=save-draft',
            ['workspace_id' => $ws['id'], 'slug' => 'hub', 'project_data' => [], 'html' => '<h1>x</h1>'], $headers);
        $this->assertEquals(422, $reserved);
    }

    public function testTemplateLibrarySnapshotsAreIndependentAndScoped(): void
    {
        $path = '/api/admin/visual-editor.php?action=';
        $wsId = self::$ws['sec-a']['id'];
        $headers = ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']];
        $otherHeaders = ['Authorization: Bearer adm_' . self::$ws['sec-b']['api_token']];
        $project = ['upscaleFlow' => ['template' => 'funil', 'steps' => [
            ['id' => 'inicio', 'name' => 'Início', 'html' => '<h1>Modelo original</h1>', 'css' => ''],
            ['id' => 'fim', 'name' => 'Fim', 'html' => '<h1>Obrigado</h1>', 'css' => '']
        ]]];
        $payload = ['workspace_id' => $wsId, 'name' => 'Meu modelo', 'category' => 'Funil', 'project_data' => $project];
        [$unauthorized] = $this->http('POST', $path . 'save-template', $payload);
        $this->assertEquals(401, $unauthorized);
        [$savedStatus, $saved] = $this->http('POST', $path . 'save-template', $payload, $headers);
        $this->assertEquals(200, $savedStatus);
        $templateId = $saved['template_id'];
        $copyId = 0;
        try {
            [$status, $loaded] = $this->http('POST', $path . 'load-template', ['workspace_id' => $wsId, 'template_id' => $templateId], $headers);
            $this->assertEquals(200, $status);
            $this->assertEquals($project, $loaded['template']['project_data']);
            [$foreign] = $this->http('POST', $path . 'load-template', ['workspace_id' => self::$ws['sec-b']['id'], 'template_id' => $templateId], $otherHeaders);
            $this->assertEquals(404, $foreign);
            [$forbidden] = $this->http('POST', $path . 'list-templates', ['workspace_id' => $wsId], $otherHeaders);
            $this->assertEquals(403, $forbidden);
            [$listedStatus, $listed] = $this->http('POST', $path . 'list-templates', ['workspace_id' => $wsId], $headers);
            $this->assertEquals(200, $listedStatus);
            $this->assertTrue(in_array($templateId, array_column($listed['templates'], 'id')));
            $copy = $project;
            $copy['upscaleFlow']['steps'][0]['html'] = '<h1>Cópia editada</h1>';
            [$copyStatus, $page] = $this->http('POST', $path . 'save-draft', [
                'workspace_id' => $wsId, 'name' => 'Cópia', 'slug' => 'template-copy-' . bin2hex(random_bytes(4)),
                'project_data' => $copy, 'html' => '<h1>Cópia editada</h1>', 'expected_revision' => 0
            ], $headers);
            $this->assertEquals(200, $copyStatus);
            $copyId = $page['id'];
            [, $unchanged] = $this->http('POST', $path . 'load-template', ['workspace_id' => $wsId, 'template_id' => $templateId], $headers);
            $this->assertEquals($project, $unchanged['template']['project_data']);
            [$deleted] = $this->http('POST', $path . 'delete-template', ['workspace_id' => $wsId, 'template_id' => $templateId], $headers);
            $this->assertEquals(200, $deleted);
            [$copyStillExists, $copyLoaded] = $this->http('POST', $path . 'load', ['workspace_id' => $wsId, 'id' => $copyId], $headers);
            $this->assertEquals(200, $copyStillExists);
            $this->assertEquals($copy, $copyLoaded['project_data']);
        } finally {
            self::$pdo->prepare('DELETE FROM page_templates WHERE id = ?')->execute([$templateId]);
            if ($copyId) self::$pdo->prepare('DELETE FROM funnels WHERE id = ?')->execute([$copyId]);
        }
        [$invalid] = $this->http('POST', $path . 'save-template', array_merge($payload, ['cover_url' => 'javascript:alert(1)']), $headers);
        $this->assertEquals(422, $invalid);
    }

    public function testCampaignPageKeepsOfferScopeAndExistingPublicLink(): void
    {
        $wsId = self::$ws['sec-a']['id'];
        $headers = ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']];
        $campaigns = [];
        try {
            foreach (['Primeira', 'Segunda'] as $title) {
                [$status, $created] = $this->http('POST', '/api/admin/campaigns.php?workspace_id=' . $wsId,
                    ['action' => 'create', 'title' => $title, 'ticket_price' => 30, 'status' => 'draft'], $headers);
                $this->assertEquals(200, $status);
                $campaigns[] = $created['campaign_id'];
            }
            [$firstId, $secondId] = $campaigns;
            [, $a] = $this->http('GET', '/api/admin/offer.php?workspace_id=' . $wsId . '&campaign_id=' . $firstId, null, $headers);
            [, $b] = $this->http('GET', '/api/admin/offer.php?workspace_id=' . $wsId . '&campaign_id=' . $secondId, null, $headers);
            $this->assertTrue($a['offer']['id'] !== $b['offer']['id']);
            $this->assertEquals($firstId, $a['offer']['campaign_id']);
            $this->assertFalse($a['offer']['is_published']);
            [$saved] = $this->http('POST', '/api/admin/offer.php?action=save_draft',
                ['workspace_id' => $wsId, 'campaign_id' => $firstId, 'title' => 'Oferta exclusiva A', 'price' => 47], $headers);
            $this->assertEquals(200, $saved);
            [, $unchanged] = $this->http('GET', '/api/admin/offer.php?workspace_id=' . $wsId . '&campaign_id=' . $secondId, null, $headers);
            $this->assertEquals('Segunda', $unchanged['offer']['title']);
            $path = '/api/admin/visual-editor.php?action=';
            $project = ['upscaleFlow' => ['campaignMechanic' => 'direct', 'steps' => [
                ['id' => 'oferta', 'name' => 'Upsell', 'html' => '<h1>Minha página da campanha</h1>']
            ]]];
            [$pageStatus, $page] = $this->http('POST', $path . 'save-draft', [
                'workspace_id' => $wsId, 'campaign_id' => $firstId, 'name' => 'Página da campanha',
                'slug' => 'pagina-campanha-' . bin2hex(random_bytes(4)), 'project_data' => $project,
                'html' => '<h1>Minha página da campanha</h1><iframe data-campaign-offer src="{{campaign_offer_url}}"></iframe>', 'expected_revision' => 0
            ], $headers);
            $this->assertEquals(200, $pageStatus);
            [$wrongCampaign] = $this->http('POST', $path . 'load', ['workspace_id' => $wsId, 'campaign_id' => $secondId, 'id' => $page['id']], $headers);
            $this->assertEquals(403, $wrongCampaign);
            [$publishedStatus, $published] = $this->http('POST', $path . 'publish',
                ['workspace_id' => $wsId, 'campaign_id' => $firstId, 'id' => $page['id'], 'expected_revision' => 1], $headers);
            $this->assertEquals(200, $publishedStatus);
            $publicPath = parse_url($a['offer']['public_url'], PHP_URL_PATH);
            $this->assertEquals($publicPath, $published['public_url']);
            [$publicStatus, , $html] = $this->http('GET', $publicPath);
            $this->assertEquals(200, $publicStatus);
            $this->assertStringContains('Minha página da campanha', $html);
            $this->assertStringContains($publicPath . '?native=1', $html);
            [, , $native] = $this->http('GET', $publicPath . '?native=1');
            $this->assertStringContains('Oferta exclusiva A', $native);
            $this->assertStringContains('"mechanic":"direct"', $native);
            [, $other] = $this->http('GET', '/api/admin/offer.php?workspace_id=' . $wsId . '&campaign_id=' . $secondId, null, $headers);
            $this->assertFalse($other['offer']['is_published']);
            [$foreign] = $this->http('POST', $path . 'load-campaign',
                ['workspace_id' => self::$ws['sec-b']['id'], 'campaign_id' => $firstId], ['Authorization: Bearer adm_' . self::$ws['sec-b']['api_token']]);
            $this->assertEquals(404, $foreign);
        } finally {
            foreach ($campaigns as $id) {
                self::$pdo->prepare('DELETE FROM funnels WHERE campaign_id = ?')->execute([$id]);
                self::$pdo->prepare('DELETE FROM upsell_offers WHERE campaign_id = ?')->execute([$id]);
                self::$pdo->prepare('DELETE FROM campaigns WHERE id = ?')->execute([$id]);
            }
        }
    }

    public function testClassicPageKeepsPublishedHtmlWhileDraftChanges(): void
    {
        $workspaceId = self::$ws['sec-a']['id'];
        $headers = ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']];
        $slug = 'published-html-' . bin2hex(random_bytes(4));
        $payload = ['workspace_id' => $workspaceId, 'name' => 'Página personalizada', 'slug' => $slug,
            'action' => 'publish', 'html_content' => '<h1>HTML PUBLICADO ORIGINAL</h1>'];
        [$status, $created] = $this->http('POST', '/api/admin/funnels.php', $payload, $headers);
        $this->assertEquals(200, $status);
        $id = $created['funnel_id'];
        try {
            $payload['funnel_id'] = $id;
            $payload['action'] = 'save';
            $payload['html_content'] = '<h1>NOVO RASCUNHO DO EDITOR</h1>';
            [$saved] = $this->http('POST', '/api/admin/funnels.php', $payload, $headers);
            $this->assertEquals(200, $saved);
            foreach (['/f/' . $slug, '/f/' . $slug . '/', '/f/' . $slug . '?slug=pagina-inexistente'] as $path) {
                [$publicStatus, , $html] = $this->http('GET', $path);
                $this->assertEquals(200, $publicStatus);
                $this->assertStringContains('HTML PUBLICADO ORIGINAL', $html);
                $this->assertFalse(str_contains($html, 'NOVO RASCUNHO DO EDITOR'));
                $this->assertFalse(str_contains($html, 'Raspe e descubra'));
            }
            $payload['action'] = 'publish';
            [$published] = $this->http('POST', '/api/admin/funnels.php', $payload, $headers);
            $this->assertEquals(200, $published);
            [, , $updatedHtml] = $this->http('GET', '/f/' . $slug);
            $this->assertStringContains('NOVO RASCUNHO DO EDITOR', $updatedHtml);
        } finally {
            self::$pdo->prepare('DELETE FROM funnels WHERE id = ?')->execute([$id]);
        }
    }

    public function testClassicExperienceSelectionPublishesNativeMechanic(): void
    {
        $wsId = self::$ws['sec-a']['id'];
        $headers = ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']];
        [, $created] = $this->http('POST', '/api/admin/campaigns.php?workspace_id=' . $wsId,
            ['action' => 'create', 'title' => 'Campanha seletor', 'ticket_price' => 23, 'status' => 'draft'], $headers);
        $campaignId = $created['campaign_id'];
        $pageId = 0;
        try {
            foreach (['wheel', 'scratch', 'direct'] as $mechanic) {
                $payload = ['workspace_id' => $wsId, 'campaign_id' => $campaignId, 'funnel_id' => $pageId,
                    'action' => 'publish', 'name' => 'Página personalizada', 'slug' => 'seletor-campanha-' . $campaignId,
                    'html_content' => '<h1>CONTEUDO PERSONALIZADO MANTIDO</h1><iframe data-campaign-offer src="{{campaign_offer_url}}&experience=' . $mechanic . '"></iframe>',
                    'settings_config' => ['experience' => ['mechanic' => $mechanic]]];
                [$status, $page] = $this->http('POST', '/api/admin/funnels.php', $payload, $headers);
                $this->assertEquals(200, $status);
                $pageId = $page['funnel_id'];
                [, $offer] = $this->http('GET', '/api/admin/offer.php?workspace_id=' . $wsId . '&campaign_id=' . $campaignId, null, $headers);
                $this->assertEquals($mechanic, $offer['offer']['published_config']['theme']['mechanic']);
                [$publicStatus, , $html] = $this->http('GET', parse_url($page['public_url'], PHP_URL_PATH));
                $this->assertEquals(200, $publicStatus);
                $this->assertStringContains('CONTEUDO PERSONALIZADO MANTIDO', $html);
                $this->assertStringContains('&experience=' . $mechanic, $html);
                [, , $native] = $this->http('GET', $page['campaign_url'] . '?native=1&experience=' . $mechanic);
                $this->assertStringContains('"mechanic":"' . $mechanic . '"', $native);
            }
            $payload['settings_config']['experience']['mechanic'] = 'invalid';
            [$invalid] = $this->http('POST', '/api/admin/funnels.php', $payload, $headers);
            $this->assertEquals(422, $invalid);
        } finally {
            if ($pageId) self::$pdo->prepare('DELETE FROM funnels WHERE id = ?')->execute([$pageId]);
            self::$pdo->prepare('DELETE FROM upsell_offers WHERE campaign_id = ?')->execute([$campaignId]);
            self::$pdo->prepare('DELETE FROM campaigns WHERE id = ?')->execute([$campaignId]);
        }
    }

    public function testVturbEmbedRoundTripAndPublishedSnapshot(): void
    {
        $wsId = self::$ws['sec-a']['id'];
        $headers = ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']];
        [, $created] = $this->http('POST', '/api/admin/campaigns.php?workspace_id=' . $wsId,
            ['action' => 'create', 'title' => 'Vídeo VTurb', 'ticket_price' => 10, 'status' => 'draft'], $headers);
        $campaignId = $created['campaign_id'];
        $embed = '<vturb-smartplayer id="vid-6ac537378f7efc03346a720e"></vturb-smartplayer><script>var s=document.createElement("script");s.src="https://scripts.converteai.net/4db00053-e2e0-4cfa-821c-3c3cb3bb0632/players/6ac537378f7efc03346a720e/v4/player.js";document.head.appendChild(s);</script>';
        try {
            $payload = ['workspace_id' => $wsId, 'campaign_id' => $campaignId, 'theme' => ['vturb_embed' => $embed]];
            [$saved] = $this->http('POST', '/api/admin/offer.php?action=save_draft', $payload, $headers);
            $this->assertEquals(200, $saved);
            [, $loaded] = $this->http('GET', '/api/admin/offer.php?workspace_id=' . $wsId . '&campaign_id=' . $campaignId, null, $headers);
            $this->assertEquals('6ac537378f7efc03346a720e', $loaded['offer']['vturb_player_id']);
            $this->assertEquals('4db00053-e2e0-4cfa-821c-3c3cb3bb0632', $loaded['offer']['vturb_account_id']);
            $this->assertEquals($embed, $loaded['offer']['draft_config']['theme']['vturb_embed']);
            [$published, $data] = $this->http('POST', '/api/admin/offer.php?action=publish', $payload, $headers);
            $this->assertEquals(200, $published);
            [, , $html] = $this->http('GET', parse_url($data['public_url'], PHP_URL_PATH) . '?native=1');
            $this->assertStringContains('/v4/player.js', $html);
            $this->assertFalse(str_contains($html, 'var s=document.createElement("script");s.src='), 'O script colado deve permanecer como dados codificados.');
            $payload['theme']['vturb_embed'] = '<script src="https://evil.example/player.js"></script>';
            [$invalid] = $this->http('POST', '/api/admin/offer.php?action=publish', $payload, $headers);
            $this->assertEquals(422, $invalid);
            [, $unchanged] = $this->http('GET', '/api/admin/offer.php?workspace_id=' . $wsId . '&campaign_id=' . $campaignId, null, $headers);
            $this->assertEquals($embed, $unchanged['offer']['published_config']['theme']['vturb_embed']);
        } finally {
            self::$pdo->prepare('DELETE FROM upsell_offers WHERE campaign_id = ?')->execute([$campaignId]);
            self::$pdo->prepare('DELETE FROM campaigns WHERE id = ?')->execute([$campaignId]);
        }
    }

}
