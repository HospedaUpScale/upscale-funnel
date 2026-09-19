<?php

namespace App\Http;

class JsonResponse
{
    public static function send(mixed $data, int $status = 200, array $headers = []): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $k => $v) {
            header("{$k}: {$v}");
        }

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message, int $status = 400, array $extra = []): void
    {
        self::send(array_merge([
            'status' => 'error',
            'message' => $message
        ], $extra), $status);
    }

    public static function success(array $data = [], int $status = 200): void
    {
        self::send(array_merge([
            'status' => 'success'
        ], $data), $status);
    }
}
