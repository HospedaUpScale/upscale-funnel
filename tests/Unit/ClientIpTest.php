<?php

namespace Tests\Unit;

use App\Security\ClientIp;
use Tests\TestCase;

class ClientIpTest extends TestCase
{
    private function ip(string $remote, ?string $xff = null, ?string $cf = null): string
    {
        $backup = $_SERVER;
        $_SERVER['REMOTE_ADDR'] = $remote;
        unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP']);
        if ($xff !== null) $_SERVER['HTTP_X_FORWARDED_FOR'] = $xff;
        if ($cf !== null) $_SERVER['HTTP_CF_CONNECTING_IP'] = $cf;
        try {
            return ClientIp::get();
        } finally {
            $_SERVER = $backup;
        }
    }

    public function testDirectConnectionIgnoresHeadersChosenByTheClient(): void
    {
        $this->assertEquals('45.67.89.10', $this->ip('45.67.89.10', '1.1.1.1', '8.8.8.8'));
    }

    public function testBehindInternalProxyUsesTheHopTheProxyAdded(): void
    {
        // O cliente forja o começo da lista; o Traefik acrescenta o IP real no fim.
        $this->assertEquals('45.67.89.10', $this->ip('172.18.0.3', '1.1.1.1, 45.67.89.10'));
        $this->assertEquals('45.67.89.10', $this->ip('10.0.0.2', '45.67.89.10, 10.0.0.9'));
        $this->assertEquals('172.18.0.3', $this->ip('172.18.0.3', 'lixo, 10.0.0.9'));
        $this->assertEquals('0.0.0.0', $this->ip(''));
    }
}
