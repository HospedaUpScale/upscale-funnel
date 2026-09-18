<?php
$html = file_get_contents('hub/index.html');
preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $matches, PREG_OFFSET_CAPTURE);

foreach ($matches[1] as $idx => $scriptMatch) {
    $code = $scriptMatch[0];
    $offset = $scriptMatch[1];
    $line = substr_count(substr($html, 0, $offset), "\n") + 1;
    echo "Script #{$idx} starting at line {$line}\n";
    file_put_contents("scripts/test_script_{$idx}.js", $code);
}
echo "Wrote test scripts.\n";
