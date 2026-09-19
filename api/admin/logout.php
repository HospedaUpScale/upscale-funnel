<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth\AdminAuth;
use App\Auth\AuthSessionRepository;
use App\Http\JsonResponse;

if ($request->method() !== 'POST') {
    JsonResponse::error('Method not allowed. Use POST.', 405);
}

$token = AdminAuth::extractBearerToken();
if ($token) {
    try {
        (new AuthSessionRepository($pdo))->revoke($token);
    } catch (\Throwable $e) {
        // Logout é best-effort: o token local é descartado de qualquer forma.
    }
}

JsonResponse::send(['status' => 'success']);
