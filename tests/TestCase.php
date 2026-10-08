<?php

namespace Tests;

abstract class TestCase
{
    protected int $assertionsCount = 0;

    public function getAssertionsCount(): int
    {
        return $this->assertionsCount;
    }

    protected function setUp(): void
    {
        // Hook for subclasses
    }

    protected function tearDown(): void
    {
        // Hook for subclasses
    }

    public function runTest(string $method): void
    {
        $this->setUp();
        try {
            $this->$method();
        } finally {
            $this->tearDown();
        }
    }

    protected function assertTrue(bool $condition, string $message = 'Failed asserting that condition is true.'): void
    {
        $this->assertionsCount++;
        if (!$condition) {
            throw new \AssertionError($message);
        }
    }

    protected function assertFalse(bool $condition, string $message = 'Failed asserting that condition is false.'): void
    {
        $this->assertionsCount++;
        if ($condition) {
            throw new \AssertionError($message);
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertionsCount++;
        if ($expected !== $actual) {
            $msg = $message ?: sprintf("Failed asserting that %s matches expected %s.", var_export($actual, true), var_export($expected, true));
            throw new \AssertionError($msg);
        }
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        $this->assertionsCount++;
        if (!array_key_exists($key, $array)) {
            $msg = $message ?: sprintf("Failed asserting that array has key '%s'.", $key);
            throw new \AssertionError($msg);
        }
    }

    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertionsCount++;
        if (!str_contains($haystack, $needle)) {
            $msg = $message ?: sprintf("Failed asserting that '%s' contains '%s'.", $haystack, $needle);
            throw new \AssertionError($msg);
        }
    }

    protected function assertGreaterThanOrEqual(int|float $expected, int|float $actual, string $message = ''): void
    {
        $this->assertionsCount++;
        if ($actual < $expected) {
            $msg = $message ?: sprintf("Failed asserting that %s is greater than or equal to %s.", $actual, $expected);
            throw new \AssertionError($msg);
        }
    }
}
