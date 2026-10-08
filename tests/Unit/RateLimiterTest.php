<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Security\RateLimiter;

class RateLimiterTest extends TestCase
{
    private string $testStorageDir;
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        $this->testStorageDir = sys_get_temp_dir() . '/upscale_test_cache_' . uniqid();
        if (!is_dir($this->testStorageDir)) {
            mkdir($this->testStorageDir, 0777, true);
        }
        $this->limiter = new RateLimiter($this->testStorageDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testStorageDir)) {
            foreach (glob($this->testStorageDir . '/*') as $f) {
                unlink($f);
            }
            rmdir($this->testStorageDir);
        }
    }

    public function testStartsAtZeroAttempts(): void
    {
        $key = 'ip_127_0_0_1';
        $this->assertEquals(0, $this->limiter->attempts($key));
        $this->assertFalse($this->limiter->isTooManyAttempts($key, 5, 60));
    }

    public function testIncrementsAttempts(): void
    {
        $key = 'login_user_test';
        $this->limiter->hit($key);
        $this->limiter->hit($key);
        $this->assertEquals(2, $this->limiter->attempts($key));
        $this->assertFalse($this->limiter->isTooManyAttempts($key, 3, 60));
    }

    public function testBlocksWhenExceedingMaxAttempts(): void
    {
        $key = 'brute_force_attacker';
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->hit($key);
        }

        $this->assertTrue($this->limiter->isTooManyAttempts($key, 5, 60));
        $this->assertGreaterThanOrEqual(1, $this->limiter->availableIn($key, 60));
    }

    public function testClearsAttemptsOnSuccess(): void
    {
        $key = 'valid_user_eventual_success';
        $this->limiter->hit($key);
        $this->limiter->hit($key);
        $this->limiter->clear($key);

        $this->assertEquals(0, $this->limiter->attempts($key));
        $this->assertFalse($this->limiter->isTooManyAttempts($key, 3, 60));
    }
}
