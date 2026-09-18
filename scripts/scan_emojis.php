<?php

$files = [
    'hub/index.html',
    'index.html',
    'documentacao/GUIA_INTEGRACAO_SORTEAMOS.html',
    'teste/simulador_dx.html'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    preg_match_all('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $content, $matches, PREG_OFFSET_CAPTURE);
    if (!empty($matches[0])) {
        echo "Found " . count($matches[0]) . " emoji occurrences in {$file}:\n";
        $seen = [];
        foreach ($matches[0] as $m) {
            $char = $m[0];
            $line = substr_count(substr($content, 0, $m[1]), "\n") + 1;
            if (!isset($seen[$char])) {
                $seen[$char] = [];
            }
            $seen[$char][] = $line;
        }
        foreach ($seen as $emoji => $lines) {
            echo "Emoji: {$emoji} (Count: " . count($lines) . ") - Lines: " . implode(', ', array_slice($lines, 0, 10)) . "\n";
        }
        echo "----------------------------------------\n";
    } else {
        echo "No emojis found in {$file}!\n";
    }
}
