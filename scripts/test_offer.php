<?php

$payload = [
    'workspace_id'        => 2,
    'title'               => 'Booster 200X Ouro',
    'subtitle'            => 'Kit Bobzão Bitruck + Ford F250 Azul Metálico',
    'price'               => 120.00,
    'quota_count'         => 200,
    'vturb_account_id'    => 'converteai',
    'vturb_player_id'     => '65a1b2c3d4e5f6',
    'legacy_checkout_url' => 'https://sinceropremios.com/campanha/kit-ouro',
    'checkout_mode'       => 'pix_native',
    'theme' => [
        'primary_color'       => '#0229C4',
        'accent_color'        => '#9FE870',
        'card_bg'             => '#05153F',
        'cta_bg'              => '#0229C4',
        'foil_start'          => '#021F96',
        'foil_end'            => '#3A5BE8',
        'eyebrow'             => 'OFERTA EXCLUSIVA DESBLOQUEADA',
        'greeting_pattern'    => 'Parabéns, {nome}! Você desbloqueou uma chance única.',
        'scratch_inst_top'    => 'RASPE COM O DEDO',
        'scratch_inst_sub'    => 'REVELE SUA OFERTA EXCLUSIVA',
        'cta_label'           => 'ATIVAR MEU BOOSTER 200X OURO',
        'cta_subtext'         => '⚡ Liberação imediata via Pix em 1 clique',
        'anchor_price'        => 240.00,
        'timer_minutes'       => 5,
        'scratch_threshold'   => 25,
        'pitch_delay'         => 12,
    ]
];

$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Bearer sincero-admin-2026\r\n",
        'content' => json_encode($payload),
        'ignore_errors' => true
    ]
]);

$resp = file_get_contents('http://127.0.0.1:8000/api/admin/offer.php', false, $ctx);
echo "SAVE RESPONSE:\n" . $resp . "\n\n";

// Now check config.php
$configResp = file_get_contents('http://127.0.0.1:8000/api/config.php?workspace=2');
echo "PUBLIC CONFIG FOR WIDGET:\n" . $configResp . "\n";
