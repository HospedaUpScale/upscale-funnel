<?php
$target = 'DESIGN/ui_kits/dashboard/index.html';
if (file_exists($target)) {
    $content = file_get_contents($target);
    preg_match_all('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $content, $m);
    echo "Emojis in {$target}: " . count($m[0]) . "\n";
}
