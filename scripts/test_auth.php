<?php

function testToken($label, $token) {
    echo "========================================\n";
    echo "TESTING: {$label}\n";
    echo "========================================\n";
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$token}\r\n",
            'ignore_errors' => true
        ]
    ]);
    $resp = file_get_contents('http://127.0.0.1:8000/api/admin/auth-status.php', false, $ctx);
    $data = json_decode($resp, true);

    if (!$data || ($data['status'] ?? '') !== 'success') {
        echo "FAILED: " . ($resp ?: 'no response') . "\n";
        return;
    }

    $auth = $data['auth'];
    echo "Role: " . $auth['role'] . " (" . $auth['role_label'] . ")\n";
    echo "Workspace: " . ($auth['workspace']['name'] ?? 'GLOBAL MASTER') . " (ID: " . ($auth['workspace']['id'] ?? 'NONE') . ")\n";
    echo "Permissions:\n";
    print_r($auth['permissions']);
    echo "Accessible Workspaces (" . count($auth['accessible_workspaces']) . "):\n";
    foreach ($auth['accessible_workspaces'] as $w) {
        $sub = $w['is_subaccount'] ? " [SUBCONTA de {$w['parent_name']}]" : " [PRINCIPAL]";
        echo " - #{$w['id']} {$w['name']} ({$w['type']}){$sub}\n";
    }
}

testToken('SUPER ADMIN (UPSCALE MASTER)', 'super_admin_sincero_secret_2026');
testToken('PARTNER ADMIN (PLAY55)', 'play55-partner-admin-token');
testToken('MERCHANT ADMIN (VENDEDOR SINCERO - SUBCONTA)', 'sincero-admin-2026');
