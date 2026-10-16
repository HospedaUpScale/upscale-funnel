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
        $headerLines = array_merge(['Content-Type: application/json', 'Host: 127.0.0.1'], $headers);
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
}
