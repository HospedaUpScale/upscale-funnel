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
    private static string $logDir = '';
    private static ?PDO $pdo = null;
    private static array $ws = [];

    protected function setUp(): void
    {
        if (self::$server !== null) {
            return;
        }

        $root = realpath(__DIR__ . '/../..');
        self::$dbPath = sys_get_temp_dir() . '/upscale_sec_' . getmypid() . '.sqlite';
        self::$logDir = sys_get_temp_dir() . '/upscale_sec_logs_' . getmypid();
        @mkdir(self::$logDir, 0700, true);
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
            'LOG_DIR'          => self::$logDir,
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

    public function testPixTestModeSimulatesPaymentOnlyWhileEnabled(): void
    {
        $wsId = self::$ws['sec-a']['id'];
        $adminA = ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']];
        $adminB = ['Authorization: Bearer adm_' . self::$ws['sec-b']['api_token']];
        $hook = '/api/admin/webhook-info.php?workspace_id=' . $wsId;
        self::$pdo->prepare("INSERT INTO upsell_offers (workspace_id, title, price, quota_count, slug, is_active, is_published, published_config) VALUES (?, 'Oferta teste Pix', 9, 5, ?, 1, 1, ?)")
            ->execute([$wsId, 'pix-teste-' . bin2hex(random_bytes(4)), json_encode(['title' => 'Oferta teste Pix', 'price' => 9, 'quota_count' => 5])]);
        $offerId = (int)self::$pdo->lastInsertId();
        // Cada Pix sai de um card (venda da rifa com token), como no fluxo real.
        $novoPix = function () use ($offerId) {
            [, $sale] = $this->http('POST', '/api/v1/webhook.php', ['order_id' => 'pt_' . bin2hex(random_bytes(4)), 'amount' => 20, 'status' => 'paid', 'customer' => ['name' => 'Comprador Teste']],
                ['Authorization: Bearer ' . self::$ws['sec-a']['api_token']]);
            [$status, $data] = $this->http('POST', '/api/pix-upsell.php', ['offer_id' => $offerId, 'token' => $sale['token']], ['Host: sec-a']);
            $this->assertEquals(201, $status);
            $data['card'] = $sale['token'];
            return $data;
        };
        $simular = fn(array $pix, string $host = 'sec-a', array $extra = [], array $headers = []) =>
            $this->http('POST', '/api/pix-test-pay.php', array_merge(['transaction_id' => $pix['transaction_id'], 'token' => $pix['card']], $extra), array_merge(['Host: ' . $host], $headers));

        try {
            // Desligado (padrão): nada de simular.
            $pix = $novoPix();
            $this->assertFalse($pix['test_mode']);
            [$off] = $simular($pix);
            $this->assertEquals(403, $off);
            $this->assertEquals('pending', $this->txStatus($pix['transaction_id']));

            // Só quem administra o workspace liga.
            [$foreign] = $this->http('POST', $hook, ['action' => 'pix_test', 'enabled' => true], $adminB);
            $this->assertEquals(403, $foreign);
            [$anon] = $this->http('POST', $hook, ['action' => 'pix_test', 'enabled' => true]);
            $this->assertEquals(401, $anon);
            [$on, $onData] = $this->http('POST', $hook, ['action' => 'pix_test', 'enabled' => true, 'workspace_id' => $wsId], $adminA);
            $this->assertEquals(200, $on);
            $this->assertTrue(strtotime($onData['pix_test_until']) > time() + 23 * 3600);
            [, $info] = $this->http('GET', $hook, null, $adminA);
            $this->assertEquals($onData['pix_test_until'], $info['pix_test_until']);

            // Ligado: a janela do Pix recebe test_mode e o botão confirma pelo caminho real.
            $pix = $novoPix();
            $this->assertTrue($pix['test_mode']);
            [$other] = $simular($pix, 'sec-b');
            $this->assertEquals(404, $other);
            // Sem o token do card dono (ou com o card de outra pessoa), ninguém liquida o Pix pelo ID.
            [$noCard] = $simular($pix, 'sec-a', ['token' => '']);
            $this->assertEquals(403, $noCard, 'Sem token do card, não simula.');
            $outro = $novoPix();
            [$wrongCard] = $simular($pix, 'sec-a', ['token' => $outro['card']]);
            $this->assertEquals(403, $wrongCard, 'Card de outro pedido não simula este Pix.');
            [$foreignAdmin] = $simular($outro, 'sec-a', ['token' => ''], $adminB);
            $this->assertEquals(403, $foreignAdmin, 'Admin de outro workspace não simula.');
            [$byAdmin] = $simular($outro, 'sec-a', ['token' => ''], $adminA);
            $this->assertEquals(200, $byAdmin, 'Admin do workspace simula pelo painel.');
            $this->assertEquals('pending', $this->txStatus($pix['transaction_id']));
            [$paid, $paidData] = $simular($pix);
            $this->assertEquals(200, $paid);
            $this->assertTrue($paidData['paid']);
            $this->assertEquals(5, $paidData['quotas_awarded']);
            $this->assertStringContains('Pagamento de teste aprovado', $paidData['message']);
            $this->assertEquals('paid', $this->txStatus($pix['transaction_id']));
            [, $status] = $this->http('GET', '/api/pix-status.php?tx_id=' . $pix['transaction_id'], null, ['Host: sec-a']);
            $this->assertTrue($status['paid']);
            // Reserva para o card: quantidade da oferta (sem rifa avisada, sem números).
            $this->assertEquals(5, $status['quotas']);
            $this->assertEquals([], $status['tickets']);
            $ev = self::$pdo->prepare("SELECT COUNT(*) FROM conversion_events WHERE order_id = ? AND event_name = 'pix_paid'");
            $ev->execute([$pix['order_id']]);
            $pagos = (int)$ev->fetchColumn();
            $ev->closeCursor();
            $this->assertEquals(1, $pagos);
            [$again, $againData] = $simular($pix);
            $this->assertEquals(200, $again);
            $this->assertStringContains('já estava pago', $againData['message']);

            // Pix de gateway real nunca é simulado.
            $real = $this->pendingOrder('sec-a');
            self::$pdo->prepare("UPDATE transactions SET gateway = 'efi' WHERE id = ?")->execute([$real['tx']['id']]);
            [$realStatus] = $this->http('POST', '/api/pix-test-pay.php', ['transaction_id' => $real['tx']['id']], ['Host: sec-a']);
            $this->assertEquals(409, $realStatus);

            // Desliga sozinho depois do prazo e desliga na hora pelo painel.
            $pix = $novoPix();
            self::$pdo->prepare('UPDATE workspaces SET pix_test_until = ? WHERE id = ?')->execute([date('Y-m-d H:i:s', time() - 60), $wsId]);
            [$expired] = $simular($pix);
            $this->assertEquals(403, $expired);
            $this->http('POST', $hook, ['action' => 'pix_test', 'enabled' => true, 'workspace_id' => $wsId], $adminA);
            [$offStatus, $offData] = $this->http('POST', $hook, ['action' => 'pix_test', 'enabled' => false, 'workspace_id' => $wsId], $adminA);
            $this->assertEquals(200, $offStatus);
            $this->assertEquals(null, $offData['pix_test_until']);
            [$afterOff] = $simular($pix);
            $this->assertEquals(403, $afterOff);
        } finally {
            self::$pdo->prepare('UPDATE workspaces SET pix_test_until = NULL WHERE id = ?')->execute([$wsId]);
            self::$pdo->prepare('DELETE FROM upsell_offers WHERE id = ?')->execute([$offerId]);
        }
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

    public function testPresselStaysInCampaignWithoutJoiningUpsell(): void
    {
        $wsId = self::$ws['sec-a']['id'];
        $headers = ['Authorization: Bearer adm_' . self::$ws['sec-a']['api_token']];
        [, $created] = $this->http('POST', '/api/admin/campaigns.php?workspace_id=' . $wsId,
            ['action' => 'create', 'title' => 'Pressel separada', 'ticket_price' => 7, 'status' => 'draft'], $headers);
        $campaignId = $created['campaign_id'];
        $ids = [];
        $offerState = function () use ($campaignId) {
            $q = self::$pdo->prepare("SELECT is_published, theme_config, published_config FROM upsell_offers WHERE campaign_id = ? AND offer_role = 'upsell'");
            $q->execute([$campaignId]);
            return $q->fetchAll()[0] ?? [];
        };
        try {
            $before = $offerState();
            [$status, $pressel] = $this->http('POST', '/api/admin/funnels.php', [
                'workspace_id' => $wsId, 'campaign_id' => $campaignId, 'action' => 'publish',
                'name' => 'Pressel', 'slug' => 'pressel-' . $campaignId, 'template_slug' => 'landing-enxuta-moderna',
                'html_content' => '<h1>PRESSEL DA CAMPANHA</h1>',
                'settings_config' => ['experience' => ['mechanic' => 'wheel']],
            ], $headers);
            $this->assertEquals(200, $status);
            $ids[] = $pressel['funnel_id'];
            $this->assertTrue($pressel['campaign_url'] === null, 'Publicar a pressel não pode publicar o link do upsell.');
            $this->assertEquals($before, $offerState(), 'Publicar a pressel não pode alterar a oferta de upsell.');
            $q = self::$pdo->prepare('SELECT f.campaign_id, c.primary_funnel_id FROM funnels f JOIN campaigns c ON c.id = f.campaign_id WHERE f.id = ?');
            $q->execute([$pressel['funnel_id']]);
            $row = $q->fetchAll()[0] ?? [];
            $this->assertEquals($campaignId, (int)$row['campaign_id'], 'A pressel continua na campanha.');
            $this->assertTrue($row['primary_funnel_id'] === null, 'A pressel não vira a principal do link.');

            [$refused] = $this->http('POST', '/api/admin/funnels.php', ['workspace_id' => $wsId, 'id' => $pressel['funnel_id'], 'action' => 'set_primary'], $headers);
            $this->assertEquals(422, $refused);

            [, $upsell] = $this->http('POST', '/api/admin/funnels.php', [
                'workspace_id' => $wsId, 'campaign_id' => $campaignId, 'action' => 'publish',
                'name' => 'Upsell', 'slug' => 'upsell-' . $campaignId, 'template_slug' => 'funil-direct',
                'html_content' => '<h1>PÁGINA DE UPSELL</h1>',
                'settings_config' => ['experience' => ['mechanic' => 'direct']],
            ], $headers);
            $ids[] = $upsell['funnel_id'];
            [, , $link] = $this->http('GET', $upsell['campaign_url']);
            $this->assertStringContains('PÁGINA DE UPSELL', $link);

            // Mesmo marcada como principal por dados antigos, a pressel não responde pelo link do upsell.
            self::$pdo->prepare('UPDATE campaigns SET primary_funnel_id = ? WHERE id = ?')->execute([$pressel['funnel_id'], $campaignId]);
            [, , $legacy] = $this->http('GET', $upsell['campaign_url']);
            $this->assertFalse(str_contains($legacy, 'PRESSEL DA CAMPANHA'));
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

    public function testLogsLeaveTheWebrootAndNeverKeepTheCardToken(): void
    {
        $root = realpath(__DIR__ . '/../..');
        $oldSales = $root . '/api/logs/upscale-sales-webhooks.log';
        $before = is_file($oldSales) ? [filesize($oldSales), filemtime($oldSales)] : null;

        // Venda válida: o log vai para a pasta de logs (fora do webroot) sem o token completo do card.
        $orderId = 'log_' . bin2hex(random_bytes(4));
        [$ok, $sale] = $this->http('POST', '/api/v1/webhook.php',
            ['order_id' => $orderId, 'amount' => 25, 'status' => 'paid', 'customer' => ['name' => 'Bruna Sigilo Total', 'phone' => '11912345678']],
            ['Authorization: Bearer ' . self::$ws['sec-a']['api_token']]);
        $this->assertEquals(200, $ok);
        $token = (string)$sale['token'];
        $this->assertTrue(str_starts_with($token, 'tk_') && strlen($token) > 20);
        clearstatcache();
        $log = (string)@file_get_contents(self::$logDir . '/upscale-sales-webhooks.log');
        $this->assertStringContains($orderId, $log, 'A venda entra no log da pasta de logs.');
        $this->assertFalse(str_contains($log, $token), 'O token completo do card não pode ir para o log.');
        $this->assertFalse(str_contains($log, 'Sigilo Total'), 'Só o primeiro nome do comprador vai para o log.');
        $this->assertEquals($before, is_file($oldSales) ? [filesize($oldSales), filemtime($oldSales)] : null, 'Nada é gravado em api/logs.');

        // Webhook de pagamento com token inválido: 401 e nada gravado em disco.
        $wh = self::$logDir . '/webhooks.log';
        $size = is_file($wh) ? filesize($wh) : 0;
        [$denied] = $this->http('POST', '/api/webhook.php', ['txid' => 'x', 'status' => 'paid', 'lixo' => str_repeat('A', 500)], ['Authorization: Bearer token-invalido']);
        $this->assertEquals(401, $denied);
        clearstatcache();
        $this->assertEquals($size, is_file($wh) ? filesize($wh) : 0, 'Requisição sem token válido não escreve no log.');

        // O Nginx do deploy recusa api/logs e arquivos .log/.sqlite.
        $conf = (string)file_get_contents($root . '/deploy/nginx.conf');
        $this->assertStringContains('location ^~ /api/logs/ { return 404; }', $conf);
        $this->assertStringContains('location ~* \\.(log|sqlite|sqlite3|db)$ { return 404; }', $conf);
        $this->assertTrue(strpos($conf, '/api/logs/') < strpos($conf, 'location /api/ {'), 'O bloqueio vem antes da rota da API.');
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

    public function testRemovingOrEditingAUserEndsTheirAccessRightAway(): void
    {
        $master = ['Authorization: Bearer ' . self::SUPER_TOKEN];
        $wsId = self::$ws['sec-a']['id'];
        $makeUser = function (string $email) use ($wsId): int {
            self::$pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)")
                ->execute(['Pessoa ' . $email, $email, password_hash('senha-do-cliente-123', PASSWORD_DEFAULT)]);
            $id = (int)self::$pdo->lastInsertId();
            self::$pdo->prepare("INSERT INTO workspace_memberships (workspace_id, user_id, role) VALUES (?, ?, 'merchant_admin')")->execute([$wsId, $id]);
            return $id;
        };
        $login = function (string $email): string {
            [$st, $d] = $this->http('POST', '/api/admin/login.php', ['type' => 'client', 'username' => $email, 'password' => 'senha-do-cliente-123'],
                ['X-Forwarded-For: 10.2.0.' . random_int(1, 250)]);
            $this->assertEquals(200, $st, 'Login do cliente deve funcionar.');
            return (string)$d['token'];
        };
        $status = fn(string $tok) => $this->http('GET', '/api/admin/auth-status.php', null, ['Authorization: Bearer ' . $tok])[0];

        // Editar só nome/telefone não derruba a sessão.
        $u1 = $makeUser('edita.' . bin2hex(random_bytes(3)) . '@example.test');
        $t1 = $login((string)self::$pdo->query("SELECT email FROM users WHERE id = {$u1}")->fetchColumn());
        $this->assertEquals(200, $status($t1));
        [$st] = $this->http('POST', '/api/admin/users.php', ['action' => 'update_global', 'user_id' => $u1, 'name' => 'Nome Novo'], $master);
        $this->assertEquals(200, $st);
        $this->assertEquals(200, $status($t1), 'Trocar só o nome mantém a sessão.');

        // Tirar os vínculos encerra a sessão aberta.
        [$st] = $this->http('POST', '/api/admin/users.php', ['action' => 'update_global', 'user_id' => $u1, 'memberships' => []], $master);
        $this->assertEquals(200, $st, 'Editar vínculos responde 200 (antes dava 500).');
        $this->assertEquals(401, $status($t1), 'Sem vínculo, a sessão antiga deixa de valer.');

        // Excluir remove o usuário e a sessão.
        $email2 = 'exclui.' . bin2hex(random_bytes(3)) . '@example.test';
        $u2 = $makeUser($email2);
        $t2 = $login($email2);
        [$st] = $this->http('POST', '/api/admin/users.php', ['action' => 'delete_global', 'user_id' => $u2], $master);
        $this->assertEquals(200, $st, 'Excluir usuário responde 200 (antes dava 500).');
        $this->assertEquals(0, (int)self::$pdo->query("SELECT COUNT(*) FROM users WHERE id = {$u2}")->fetchColumn(), 'O usuário some do banco.');
        $this->assertEquals(401, $status($t2), 'A sessão do usuário excluído deixa de valer.');

        // Vínculo apagado direto no banco também derruba a sessão (a checagem é a cada requisição).
        $email3 = 'direto.' . bin2hex(random_bytes(3)) . '@example.test';
        $u3 = $makeUser($email3);
        $t3 = $login($email3);
        self::$pdo->prepare('DELETE FROM workspace_memberships WHERE user_id = ?')->execute([$u3]);
        $this->assertEquals(401, $status($t3));
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

        // Verificação (GET): a rifa confere o segredo do retorno pelo HMAC de um texto fixo, sem ver o segredo.
        [$anon] = $this->http('GET', '/api/v1/webhook.php');
        $this->assertEquals(401, $anon);
        [$check, $info, $raw] = $this->http('GET', '/api/v1/webhook.php?token=' . urlencode(self::$ws['sec-b']['api_token']));
        $this->assertEquals(200, $check);
        $this->assertTrue($info['postback_signed']);
        $this->assertEquals(hash_hmac('sha256', \App\Support\RafflePostback::PROBE, self::POSTBACK_SECRET), $info['postback_probe']);
        $this->assertFalse(str_contains($raw, self::POSTBACK_SECRET), 'O segredo nunca aparece na resposta.');
    }

    public function testSaleCanCarryAutomaticReturnAddressAndKey(): void
    {
        $auth = ['Authorization: Bearer ' . self::$ws['sec-a']['api_token']];
        $sale = fn(array $extra) => $this->http('POST', '/api/v1/webhook.php', array_merge(['order_id' => 'cb_' . bin2hex(random_bytes(4)), 'amount' => 19.9, 'status' => 'paid', 'customer' => ['name' => 'Ana Retorno']], $extra), $auth);

        // Endereço precisa ser https público e vir com chave.
        foreach (['http://rifa.exemplo.com/r', 'https://localhost/r', 'https://10.0.0.5/r', 'https://user:pw@rifa.exemplo.com/r'] as $bad) {
            [$status] = $sale(['callback_url' => $bad, 'callback_key' => str_repeat('k', 32)]);
            $this->assertEquals(422, $status, 'Retorno recusado: ' . $bad);
        }
        [$noKey] = $sale(['callback_url' => 'https://rifa.exemplo.com/api/upscale.php?action=return']);
        $this->assertEquals(422, $noKey);

        $key = 'chave_' . bin2hex(random_bytes(12));
        [$ok, $data] = $sale(['callback_url' => 'https://rifa.exemplo.com/api/upscale.php?action=return', 'callback_key' => $key]);
        $this->assertEquals(200, $ok);
        $this->assertEquals('automatic', $data['callback']);

        // O Pix do upsell aberto pelo card usa o retorno da própria venda, com a chave da rifa.
        self::$pdo->prepare("INSERT INTO upsell_offers (workspace_id, title, price, quota_count, slug, is_active, is_published, published_config) VALUES (?, 'Oferta retorno', 5, 3, ?, 1, 1, ?)")
            ->execute([self::$ws['sec-a']['id'], 'retorno-' . bin2hex(random_bytes(4)), json_encode(['title' => 'Oferta retorno', 'price' => 5, 'quota_count' => 3])]);
        $offerId = (int)self::$pdo->lastInsertId();
        try {
            [$pixStatus, $pix] = $this->http('POST', '/api/pix-upsell.php', ['offer_id' => $offerId, 'token' => $data['token']], ['Host: sec-a']);
            $this->assertEquals(201, $pixStatus);
            $order = (new OrderRepository(self::$pdo))->find((int)$pix['order_id']);
            $target = \App\Support\RafflePostback::target(self::$pdo, $order, null);
            $this->assertEquals('https://rifa.exemplo.com/api/upscale.php?action=return', $target['url']);
            $this->assertEquals($key, $target['key']);

            // Clicar de novo em "Ativar" com o Pix ainda pendente devolve o mesmo Pix (sem segunda cobrança).
            [, $again] = $this->http('POST', '/api/pix-upsell.php', ['offer_id' => $offerId, 'token' => $data['token']], ['Host: sec-a']);
            $this->assertEquals($pix['transaction_id'], $again['transaction_id']);
            $this->assertTrue($again['reused']);
            // Pix expirado → novo Pix; o antigo continua ligado à venda (retorno e pedido da rifa).
            self::$pdo->prepare('UPDATE transactions SET expires_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s', time() - 60), $pix['transaction_id']]);
            [, $novo] = $this->http('POST', '/api/pix-upsell.php', ['offer_id' => $offerId, 'token' => $data['token']], ['Host: sec-a']);
            $this->assertTrue((int)$novo['transaction_id'] !== (int)$pix['transaction_id']);
            $this->assertEquals($key, \App\Support\RafflePostback::target(self::$pdo, $order, null)['key']);
            $this->assertEquals($data['order_id'], \App\Support\RafflePostback::payload(self::$pdo, $order, null, null)['raffle_order_id']);
            $novoOrder = (new OrderRepository(self::$pdo))->find((int)$novo['order_id']);
            $this->assertEquals($key, \App\Support\RafflePostback::target(self::$pdo, $novoOrder, null)['key']);
            // Sem retorno na venda, continua valendo o retorno do workspace (sem chave).
            $plain = $this->pendingOrder('sec-a');
            $this->assertEquals(null, \App\Support\RafflePostback::target(self::$pdo, $plain['order'], null)['key']);
        } finally {
            self::$pdo->prepare('DELETE FROM upsell_offers WHERE id = ?')->execute([$offerId]);
        }

        // Em APP_ENV=local o teste local aceita http/localhost.
        $this->assertTrue(\App\Support\RafflePostback::isAllowedCallback('http://127.0.0.1:8105/api/upscale.php?action=return', true));
        $this->assertFalse(\App\Support\RafflePostback::isAllowedCallback('http://127.0.0.1:8105/api/upscale.php?action=return'));
    }

    public function testRaffleReservedTicketsAreReadFromTheReturnResponse(): void
    {
        $t = \App\Support\RafflePostback::ticketsFromResponse(json_encode(['status' => 'success', 'tickets' => ['0012345', 778, 'bad value!', '9988776']]));
        $this->assertEquals(['0012345', '778', '9988776'], $t);
        $this->assertEquals(['A1', 'B2'], \App\Support\RafflePostback::ticketsFromResponse(json_encode(['data' => ['numeros' => ['A1', 'B2']]])));
        $this->assertEquals([], \App\Support\RafflePostback::ticketsFromResponse('ok'));
        $paid = ['status' => 'paid', 'reservation' => json_encode(['quotas' => 110, 'tickets' => ['1', '2']])];
        $this->assertEquals(['quotas' => 110, 'tickets' => ['1', '2']], \App\Support\PaymentSettlement::reservation(self::$pdo, $paid));
        $this->assertEquals(null, \App\Support\PaymentSettlement::reservation(self::$pdo, ['status' => 'pending', 'reservation' => null]));
    }

    public function testBonusIsOnlyReleasedAfterTheUpsellIsPaid(): void
    {
        $wsId = self::$ws['sec-a']['id'];
        $apiToken = self::$ws['sec-a']['api_token'];
        self::$pdo->prepare("INSERT INTO upsell_offers (workspace_id, title, price, quota_count, slug, is_active, is_published, published_config) VALUES (?, 'Oferta bonus', 7, 5, ?, 1, 1, ?)")
            ->execute([$wsId, 'bonus-' . bin2hex(random_bytes(4)), json_encode(['title' => 'Oferta bonus', 'price' => 7, 'quota_count' => 5])]);
        $offerId = (int)self::$pdo->lastInsertId();
        $orderId = 'bonus_' . bin2hex(random_bytes(4));
        [$ok, $sale] = $this->http('POST', '/api/v1/webhook.php', ['order_id' => $orderId, 'amount' => 100, 'status' => 'paid', 'customer' => ['name' => 'Gil Ganhador']],
            ['Authorization: Bearer ' . $apiToken]);
        $this->assertEquals(200, $ok);
        $card = (string)$sale['token'];
        $cardRow = fn() => self::$pdo->query('SELECT upsell_order_id, upsell_converted FROM order_access_tokens WHERE token = ' . self::$pdo->quote($card))->fetch();

        // Outra pessoa gera um Pix com o número do pedido, sem o token do card: o card não muda.
        [$st0] = $this->http('POST', '/api/pix-upsell.php', ['offer_id' => $offerId, 'order_id' => $orderId, 'name' => 'Intruso'], ['Host: sec-a']);
        $this->assertEquals(201, $st0);
        $row = $cardRow();
        $this->assertTrue(empty($row['upsell_order_id']) && !(int)$row['upsell_converted'], 'Pix sem o token do card não se liga ao card.');

        // O comprador gera o Pix e não paga: auditoria NÃO libera o bônus.
        [$st1, $pix] = $this->http('POST', '/api/pix-upsell.php', ['offer_id' => $offerId, 'token' => $card], ['Host: sec-a']);
        $this->assertEquals(201, $st1);
        $this->assertEquals((int)$pix['order_id'], (int)$cardRow()['upsell_order_id'], 'O Pix do card fica ligado ao card.');
        [, $w1] = $this->http('GET', '/api/v1/winner.php?token=' . urlencode($card));
        $this->assertFalse($w1['upsell_converted'], 'Gerar o Pix não é converter.');
        $this->assertFalse(str_contains((string)$w1['veredicto'], 'LIBERADO'), 'Sem pagamento, nada de bônus liberado.');
        $this->assertEquals(0, (int)$cardRow()['upsell_converted']);

        // Pix pago: agora sim.
        $gw = (string)self::$pdo->query('SELECT gateway_tx_id FROM transactions WHERE id = ' . (int)$pix['transaction_id'])->fetchColumn();
        [$paid] = $this->http('POST', '/api/webhook.php?token=' . urlencode($apiToken), ['txid' => $gw, 'status' => 'paid']);
        $this->assertEquals(200, $paid);
        [, $w2] = $this->http('GET', '/api/v1/winner.php?token=' . urlencode($card));
        $this->assertTrue($w2['upsell_converted'], 'Pix pago conta como convertido.');
        $this->assertStringContains('LIBERADO', (string)$w2['veredicto']);
        $this->assertEquals(1, (int)$cardRow()['upsell_converted']);
        $this->assertTrue(($w2['reserva']['pago'] ?? false) === true, 'O card reabre na reserva.');
    }

    public function testPartnerOnlyCreatesClientsInsideItsOwnTree(): void
    {
        $mk = function (string $slug, string $type): array {
            $admin = 'adm_' . $slug . '_' . bin2hex(random_bytes(6));
            self::$pdo->prepare("INSERT INTO workspaces (type, name, slug, api_token_hash, admin_token_hash, status) VALUES (?, ?, ?, ?, ?, 'active')")
                ->execute([$type, $slug, $slug, hash('sha256', 'api_' . $admin), hash('sha256', $admin)]);
            return ['id' => (int)self::$pdo->lastInsertId(), 'admin' => $admin, 'slug' => $slug];
        };
        $p1 = $mk('parceiro-um-' . bin2hex(random_bytes(3)), 'partner_whitelabel');
        $p2 = $mk('parceiro-dois-' . bin2hex(random_bytes(3)), 'partner_whitelabel');
        $auth = ['Authorization: Bearer ' . $p1['admin']];

        $tentativas = [
            ['name' => 'Cliente sob outro', 'partner_option' => 'new', 'new_partner_name' => 'Dois', 'new_partner_slug' => $p2['slug']],
            ['name' => 'Parceiro novo', 'partner_option' => 'new', 'new_partner_name' => 'Parceiro Fantasma ' . bin2hex(random_bytes(3))],
            ['name' => 'Via sorteamos', 'partner_option' => 'sorteamos'],
            ['name' => 'Topo', 'account_type' => 'partner_whitelabel'],
            ['name' => 'Pai escolhido', 'parent_id' => $p2['id'], 'partner_id' => $p2['id']],
        ];
        foreach ($tentativas as $body) {
            [$st, $data] = $this->http('POST', '/api/admin/workspaces.php', $body, $auth);
            $this->assertEquals(201, $st, 'Criação de cliente pelo parceiro continua funcionando: ' . $body['name']);
            $this->assertEquals('merchant', $data['workspace']['type'], 'Parceiro não cria parceiro: ' . $body['name']);
            $this->assertEquals($p1['id'], (int)$data['workspace']['parent_id'], 'Cliente fica sob o próprio parceiro: ' . $body['name']);
            $this->assertTrue(empty($data['partner']['admin_token']) && empty($data['partner']['api_token']), 'Nenhum token de outro parceiro é devolvido.');
        }
        $this->assertEquals(0, (int)self::$pdo->query("SELECT COUNT(*) FROM workspaces WHERE name LIKE 'Parceiro Fantasma%'")->fetchColumn());
        $this->assertEquals(0, (int)self::$pdo->query('SELECT COUNT(*) FROM workspaces WHERE parent_id = ' . $p2['id'])->fetchColumn(), 'Nada foi criado na árvore do outro parceiro.');
    }

    public function testTokensStayOutOfUrlsAndReturnAddressesStayPublic(): void
    {
        // Token de admin na URL não autentica (fica em logs e no Referer); no cabeçalho, sim.
        [$inUrl] = $this->http('GET', '/api/admin/auth-status.php?token=' . urlencode(self::SUPER_TOKEN));
        $this->assertEquals(401, $inUrl);
        [$inUrl2] = $this->http('GET', '/api/admin/auth-status.php?admin_token=' . urlencode(self::SUPER_TOKEN));
        $this->assertEquals(401, $inUrl2);
        [$inHeader] = $this->http('GET', '/api/admin/auth-status.php', null, ['Authorization: Bearer ' . self::SUPER_TOKEN]);
        $this->assertEquals(200, $inHeader);

        // Retorno da rifa cadastrado no painel: só endereço público.
        $wsId = self::$ws['sec-b']['id'];
        $admin = ['Authorization: Bearer adm_' . self::$ws['sec-b']['api_token']];
        foreach (['https://127.0.0.1/r', 'https://localhost/r', 'https://10.1.2.3/r', 'https://192.168.0.10/r', 'http://rifa.exemplo.com/r'] as $bad) {
            [$st] = $this->http('POST', '/api/admin/webhook-info.php?workspace_id=' . $wsId, ['action' => 'save_postback', 'postback_url' => $bad], $admin);
            $this->assertEquals(422, $st, 'Retorno recusado: ' . $bad);
        }
        [$ok] = $this->http('POST', '/api/admin/webhook-info.php?workspace_id=' . $wsId, ['action' => 'save_postback', 'postback_url' => 'https://rifa.exemplo.com/api/upscale.php?action=return'], $admin);
        $this->assertEquals(200, $ok);
        $this->http('POST', '/api/admin/webhook-info.php?workspace_id=' . $wsId, ['action' => 'save_postback', 'postback_url' => ''], $admin);

        // Consulta pública pelo token do card: nome para a saudação, telefone mascarado.
        [, $sale] = $this->http('POST', '/api/v1/webhook.php', ['order_id' => 'tel_' . bin2hex(random_bytes(4)), 'amount' => 10, 'status' => 'paid', 'customer' => ['name' => 'Rita Telefone', 'phone' => '11987654321']],
            ['Authorization: Bearer ' . self::$ws['sec-b']['api_token']]);
        [$wst, $w] = $this->http('GET', '/api/v1/winner.php?token=' . urlencode($sale['token']));
        $this->assertEquals(200, $wst);
        $this->assertEquals('Rita Telefone', $w['customer_name']);
        $this->assertEquals('*******4321', $w['customer_phone']);
    }

    public function testLoginPausesAnAccountAfterTooManyWrongPasswords(): void
    {
        $email = 'forca.' . bin2hex(random_bytes(3)) . '@example.test';
        self::$pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES ('Alvo', ?, ?)")->execute([$email, password_hash('senha-certa-123', PASSWORD_DEFAULT)]);
        $uid = (int)self::$pdo->lastInsertId();
        self::$pdo->prepare("INSERT INTO workspace_memberships (workspace_id, user_id, role) VALUES (?, ?, 'merchant_admin')")->execute([self::$ws['sec-a']['id'], $uid]);
        // Trocar o IP a cada tentativa não ajuda: o limite é da conta.
        for ($i = 1; $i <= 10; $i++) {
            [$st] = $this->http('POST', '/api/admin/login.php', ['type' => 'client', 'username' => $email, 'password' => 'errada-' . $i],
                ['X-Forwarded-For: 45.67.89.' . $i]);
            $this->assertEquals(401, $st);
        }
        [$blocked, $data] = $this->http('POST', '/api/admin/login.php', ['type' => 'client', 'username' => strtoupper($email), 'password' => 'senha-certa-123'],
            ['X-Forwarded-For: 45.67.90.1']);
        $this->assertEquals(429, $blocked, 'Depois de 10 erros a conta pausa, mesmo com a senha certa.');
        $this->assertStringContains('Tente de novo', (string)$data['message']);

        // Outra conta não é afetada.
        $other = 'livre.' . bin2hex(random_bytes(3)) . '@example.test';
        self::$pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES ('Livre', ?, ?)")->execute([$other, password_hash('senha-certa-123', PASSWORD_DEFAULT)]);
        $oid = (int)self::$pdo->lastInsertId();
        self::$pdo->prepare("INSERT INTO workspace_memberships (workspace_id, user_id, role) VALUES (?, ?, 'merchant_admin')")->execute([self::$ws['sec-a']['id'], $oid]);
        [$ok] = $this->http('POST', '/api/admin/login.php', ['type' => 'client', 'username' => $other, 'password' => 'senha-certa-123']);
        $this->assertEquals(200, $ok);
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
            // Link vindo do webhook da rifa: o token do pedido segue para a oferta embutida
            // e a página ganha a ponte de altura/eventos para funcionar dentro do iframe da rifa.
            [$embedStatus, , $embedHtml] = $this->http('GET', $publicPath . '?token=tok_abc123&order_id=77&preview=1&nome=Ana%20Lima');
            $this->assertEquals(200, $embedStatus);
            $this->assertStringContains($publicPath . '?native=1&amp;token=tok_abc123&amp;order_id=77&amp;nome=Ana%20Lima', $embedHtml);
            $this->assertFalse(str_contains($embedHtml, 'preview=1'));
            $this->assertStringContains('data-ups-ponte', $embedHtml);
            $this->assertStringContains("tipo: 'dx:altura'", $embedHtml);
            $this->assertEquals(2, substr_count($embedHtml, 'data-ups-ponte>'));
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
