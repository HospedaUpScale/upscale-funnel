<?php

namespace App\Support;

use InvalidArgumentException;

final class VturbEmbed
{
    /** Extrai a configuração sem executar nem publicar scripts colados no painel. */
    public static function parse(string $embed): array
    {
        if (strlen($embed) > 32768) throw new InvalidArgumentException('O código VTurb ultrapassa o limite de 32 KB.');
        $source = str_replace('\\/', '/', html_entity_decode($embed, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        preg_match_all('~https://scripts\.converteai\.net/([a-zA-Z0-9_-]+)/players/([a-zA-Z0-9_-]+)/(?:v[0-9]+/)?player\.js(?=[\s"\'<>?,;)]|$)~', $source, $matches, PREG_SET_ORDER);
        $urls = array_unique(array_column($matches, 0));
        if (count($urls) !== 1) throw new InvalidArgumentException('Cole o embed completo de um único player VTurb ou a URL HTTPS do seu player.js.');
        $player = $matches[0];
        if (preg_match('~<vturb-smartplayer\b[^>]*\bid\s*=\s*["\']vid-([^"\']+)["\']~i', $source, $tag) && $tag[1] !== $player[2]) {
            throw new InvalidArgumentException('O ID do smartplayer difere do ID no endereço player.js. Copie novamente o embed completo.');
        }
        $ratio = 56.25;
        if (preg_match('~padding(?:-top)?\s*:\s*([0-9]+(?:\.[0-9]+)?)%~i', $source, $padding)) $ratio = (float)$padding[1];
        if ($ratio < 1 || $ratio > 400) $ratio = 56.25;
        return ['account_id' => $player[1], 'player_id' => $player[2], 'script_url' => $player[0], 'aspect_ratio' => $ratio];
    }
}
