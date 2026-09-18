<?php

namespace App\Http;

class Request
{
    private array $data;
    private array $headers;

    public function __construct()
    {
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];

        $rawBody = file_get_contents('php://input');
        $json = [];
        if (!empty($rawBody)) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        $this->data = array_merge($_GET, $_POST, $json);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        foreach ($this->headers as $k => $v) {
            if (strcasecmp($k, $name) === 0) {
                return $v;
            }
        }
        return $default;
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
}
