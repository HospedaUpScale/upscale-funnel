<?php

namespace Tests\Unit;

use App\Support\DomainProbe;
use Tests\TestCase;

class DomainProbeTest extends TestCase
{
    public function testOnlyTheUpscaleHealthResponseCounts(): void
    {
        $this->assertEquals(DomainProbe::UPSCALE, DomainProbe::classify(200, '{"status":"ok","app":"upScale Platform"}'));
        // Banco fora do ar: o health responde 503, mas continua sendo o upScale.
        $this->assertEquals(DomainProbe::UPSCALE, DomainProbe::classify(503, '{"status":"error","app":"upScale Platform"}'));

        // Outro site respondendo no domínio (a página da rifa, um 404 de outra hospedagem): não é o upScale.
        $this->assertEquals(DomainProbe::OTHER, DomainProbe::classify(404, '<h1>This Page Does Not Exist</h1>'));
        $this->assertEquals(DomainProbe::OTHER, DomainProbe::classify(200, '<!doctype html><title>TrevoSorte</title>'));
        $this->assertEquals(DomainProbe::OTHER, DomainProbe::classify(200, '{"status":"ok","app":"Outro sistema"}'));

        // Sem resposta conclusiva: decide o DNS.
        $this->assertEquals(DomainProbe::UNKNOWN, DomainProbe::classify(0, ''));
        $this->assertEquals(DomainProbe::UNKNOWN, DomainProbe::classify(301, ''));
        $this->assertEquals(DomainProbe::UNKNOWN, DomainProbe::classify(502, 'Bad gateway'));
    }

    public function testProbeTellsUpscaleFromAnotherSite(): void
    {
        $root = dirname(__DIR__, 2);
        $other = sys_get_temp_dir() . '/ups-other-site-' . bin2hex(random_bytes(4));
        mkdir($other);
        file_put_contents($other . '/router.php', "<?php http_response_code(404); echo 'This Page Does Not Exist';");

        $upPort = $this->freePort();
        $otherPort = $this->freePort();
        $up = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $upPort, '-t', $root], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes1);
        $site = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $otherPort, $other . '/router.php'], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes2);
        try {
            $this->waitFor($upPort);
            $this->waitFor($otherPort);
            $this->assertEquals(DomainProbe::UPSCALE, DomainProbe::probe('127.0.0.1:' . $upPort)['result']);
            $res = DomainProbe::probe('127.0.0.1:' . $otherPort);
            $this->assertEquals(DomainProbe::OTHER, $res['result']);
            $this->assertEquals(404, $res['code']);
        } finally {
            proc_terminate($up);
            proc_terminate($site);
            @unlink($other . '/router.php');
            @rmdir($other);
        }
    }

    private function freePort(): int
    {
        $s = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int)substr(strrchr(stream_socket_get_name($s, false), ':'), 1);
        fclose($s);
        return $port;
    }

    private function waitFor(int $port): void
    {
        for ($i = 0; $i < 50; $i++) {
            $c = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($c) { fclose($c); return; }
            usleep(100000);
        }
    }
}
