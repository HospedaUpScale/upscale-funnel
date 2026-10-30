<?php

namespace App\Security;

class RateLimiter
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?: (__DIR__ . '/../../storage/cache/rate_limits');
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0770, true);
        }
    }

    private function getFilePath(string $key): string
    {
        $hash = sha1($key);
        return $this->storageDir . DIRECTORY_SEPARATOR . 'limiter_' . $hash . '.json';
    }

    private function read(string $key): array
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return ['attempts' => 0, 'first_hit' => 0, 'last_hit' => 0];
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) {
            return ['attempts' => 0, 'first_hit' => 0, 'last_hit' => 0];
        }

        return $data;
    }

    private function write(string $key, array $data): void
    {
        $file = $this->getFilePath($key);
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    public function hit(string $key): int
    {
        // Ler e gravar sob trava: requisições em paralelo não podem perder contagens.
        $fh = @fopen($this->getFilePath($key), 'c+');
        if ($fh === false) {
            return 0;
        }
        try {
            flock($fh, LOCK_EX);
            $data = json_decode((string) stream_get_contents($fh), true);
            if (!is_array($data)) {
                $data = ['attempts' => 0, 'first_hit' => 0, 'last_hit' => 0];
            }
            $now = time();
            if ((int) $data['first_hit'] === 0) {
                $data['first_hit'] = $now;
            }
            $data['last_hit'] = $now;
            $data['attempts'] = (int) $data['attempts'] + 1;
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($data));
            fflush($fh);
            return $data['attempts'];
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    public function attempts(string $key): int
    {
        $data = $this->read($key);
        return (int) ($data['attempts'] ?? 0);
    }

    public function isTooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $data = $this->read($key);
        if ($data['attempts'] < $maxAttempts) {
            return false;
        }

        $now = time();
        $elapsed = $now - ($data['first_hit'] ?: $now);

        if ($elapsed >= $decaySeconds) {
            $this->clear($key);
            return false;
        }

        return true;
    }

    public function availableIn(string $key, int $decaySeconds): int
    {
        $data = $this->read($key);
        $now = time();
        $elapsed = $now - ($data['first_hit'] ?: $now);
        $remaining = $decaySeconds - $elapsed;

        return max(0, $remaining);
    }

    public function clear(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}
