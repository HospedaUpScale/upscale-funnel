<?php

namespace Tests\Integration;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function testHealthFileExists(): void
    {
        $healthFile = __DIR__ . '/../../api/health.php';
        $this->assertTrue(file_exists($healthFile), 'api/health.php must exist.');
    }

    public function testHealthResponseStructure(): void
    {
        $healthFile = __DIR__ . '/../../api/health.php';
        if (!file_exists($healthFile)) {
            $this->assertTrue(false, 'api/health.php not yet implemented');
            return;
        }

        // Execute health check in isolated sub-process or direct inclusion capture
        ob_start();
        $GLOBALS['TEST_RUN'] = true;
        require $healthFile;
        $output = ob_get_clean();

        $data = json_decode((string) $output, true);
        $this->assertTrue(is_array($data), 'Health endpoint must output valid JSON.');
        $this->assertEquals('ok', $data['status'] ?? null);
        $this->assertArrayHasKey('database', $data);
        $this->assertEquals('connected', $data['database']['status'] ?? null);
        $this->assertArrayHasKey('disk', $data);
        $this->assertArrayHasKey('php', $data);
    }
}
