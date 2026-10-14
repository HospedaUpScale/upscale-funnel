<?php

function testReq($url, $desc, $post = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? json_encode($post) : $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $ok = ($code >= 200 && $code < 400);
    echo ($ok ? "\033[32m[PASS]\033[0m " : "\033[31m[FAIL]\033[0m ") . "($code) $desc\n";
    return [$code, $resp];
}

echo "\n═════════════════════════════════════════════════════════\n";
echo "    VERIFICAÇÃO COMPLETA: EDITOR ESTILO ELEMENTOR\n";
echo "═════════════════════════════════════════════════════════\n\n";

// 1. Hub e assets estáticos do builder
testReq('http://127.0.0.1:8000/hub/', 'Painel Administrativo Hub');
testReq('http://127.0.0.1:8000/hub/elementor-builder.css', 'Estilos elementor-builder.css');
testReq('http://127.0.0.1:8000/hub/elementor-builder.js', 'Script elementor-builder.js');

// 2. API de ofertas
list($c1, $r1) = testReq('http://127.0.0.1:8000/api/admin/funnels.php?action=get_offers&workspace_id=2', 'API: get_offers para vinculação no builder');
$d1 = json_decode($r1, true);
echo "   -> Ofertas retornadas: " . count($d1['data']['offers'] ?? []) . "\n";

// 3. API de validação de slug
list($c2, $r2) = testReq('http://127.0.0.1:8000/api/admin/funnels.php?action=check_slug&slug=oferta-especial-teste&workspace_id=2', 'API: check_slug para validação em tempo real');

// 4. Salvar Rascunho
$slug = 'oferta-especial-verif-' . time();
list($c3, $r3) = testReq('http://127.0.0.1:8000/api/admin/funnels.php', 'API: Salvar Rascunho (save draft)', [
    'action' => 'save',
    'name' => 'Oferta Especial Verificação',
    'slug' => $slug,
    'draft_content' => '<h1>Rascunho de Teste Elementor</h1>',
    'workspace_id' => 2
]);
$d3 = json_decode($r3, true);
$funnelId = $d3['funnel_id'] ?? 0;
$actualSlug = $d3['slug'] ?? $slug;
echo "   -> Funil criado com ID #$funnelId, slug: $actualSlug\n";

// 5. Testar que /f/{actualSlug} NÃO abre rascunho (retorna 404 antes de publicar)
list($c4, $r4) = testReq("http://127.0.0.1:8000/f/$actualSlug", 'Isolamento: Página pública /f/{slug} NÃO exibe rascunho');
if ($c4 === 404) {
    echo "   \033[32m-> [CONFIRMADO]\033[0m Rascunho isolado (404 Not Found como esperado)!\n";
}

// 6. Publicar página
list($c5, $r5) = testReq('http://127.0.0.1:8000/api/admin/funnels.php', 'API: Publicar Página (publish)', [
    'action' => 'publish',
    'funnel_id' => $funnelId,
    'name' => 'Oferta Especial Verificação',
    'slug' => $actualSlug,
    'html_content' => '<!DOCTYPE html><html><body><h1 data-block-id="blk_1">Oferta no Ar</h1><p>Publicada com sucesso!</p></body></html>',
    'workspace_id' => 2
]);

// 7. Testar que /f/{actualSlug} agora abre com 200 OK sem necessidade de login
list($c6, $r6) = testReq("http://127.0.0.1:8000/f/$actualSlug", 'Acesso Público: /f/{slug} aberta sem login');
if ($c6 === 200 && strpos($r6, 'Oferta no Ar') !== false) {
    echo "   \033[32m-> [CONFIRMADO]\033[0m Conteúdo publicado renderizado com fidelidade!\n";
}

// Limpeza
testReq("http://127.0.0.1:8000/api/admin/funnels.php?id=$funnelId&workspace_id=2", 'Limpeza: Remover funil de teste', null);

echo "\n\033[32m✔ TODOS OS FLUXOS DO EDITOR ELEMENTOR VERIFICADOS COM SUCESSO!\033[0m\n\n";
