<?php

namespace Tests\Unit;

use App\AI\GenerationQuota;
use PDO;
use Tests\TestCase;

class GenerationQuotaTest extends TestCase
{
    private function db(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(file_get_contents(__DIR__ . '/../../migrations/0022_ai_generation_quota.sqlite.sql'));
        return $pdo;
    }

    public function testDailyLimitRollsBackTotalReservationAndSurvivesNewInstance(): void
    {
        $pdo = $this->db();
        $quota = new GenerationQuota($pdo, 3, 1);
        $this->assertEquals(2, $quota->reserve()['total_remaining']);
        try {
            (new GenerationQuota($pdo, 3, 1))->reserve();
            $this->assertTrue(false);
        } catch (\RuntimeException $e) {
            $this->assertEquals(429, $e->getCode());
        }
        $this->assertEquals(2, $quota->status()['total_remaining']);
        $this->assertEquals(0, $quota->status()['daily_remaining']);
    }

    public function testTotalQuotaCannotResetWithDateOrMigration(): void
    {
        $pdo = $this->db();
        $quota = new GenerationQuota($pdo, 1, 10);
        $quota->reserve();
        $pdo->exec("DELETE FROM ai_generation_quota WHERE bucket != 'total'");
        $pdo->exec(file_get_contents(__DIR__ . '/../../migrations/0022_ai_generation_quota.sqlite.sql'));
        try {
            (new GenerationQuota($pdo, 1, 10))->reserve();
            $this->assertTrue(false);
        } catch (\RuntimeException $e) {
            $this->assertEquals(429, $e->getCode());
        }
        $this->assertEquals(0, $quota->status()['total_remaining']);
        $this->assertEquals(10, $quota->status()['daily_remaining']);
    }

    public function testZeroLimitDisablesGenerationWithoutConsumingDailyQuota(): void
    {
        $quota = new GenerationQuota($this->db(), 0, 20);
        try {
            $quota->reserve();
            $this->assertTrue(false);
        } catch (\RuntimeException $e) {
            $this->assertEquals(429, $e->getCode());
        }
        $this->assertEquals(20, $quota->status()['daily_remaining']);
    }
}
