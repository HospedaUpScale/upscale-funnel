<?php

namespace Tests\Unit;

use App\Support\VturbEmbed;
use Tests\TestCase;

class VturbEmbedTest extends TestCase
{
    public function testParsesModernSmartplayerEmbed(): void
    {
        $embed = '<vturb-smartplayer id="vid-6ac537378f7efc03346a720e"><div style="padding:56.25% 0 0"></div></vturb-smartplayer><script>var s=document.createElement("script");s.src="https://scripts.converteai.net/4db00053-e2e0-4cfa-821c-3c3cb3bb0632/players/6ac537378f7efc03346a720e/v4/player.js";document.head.appendChild(s);</script>';
        $parsed = VturbEmbed::parse($embed);
        $this->assertEquals('4db00053-e2e0-4cfa-821c-3c3cb3bb0632', $parsed['account_id']);
        $this->assertEquals('6ac537378f7efc03346a720e', $parsed['player_id']);
        $this->assertStringContains('/v4/player.js', $parsed['script_url']);
        $this->assertEquals(56.25, $parsed['aspect_ratio']);
    }

    public function testAcceptsLegacyAndPortraitPlayers(): void
    {
        $parsed = VturbEmbed::parse('<div style="padding-top:177.78%"></div><script src="https://scripts.converteai.net/account/players/player/player.js"></script>');
        $this->assertEquals(177.78, $parsed['aspect_ratio']);
        $this->assertEquals('https://scripts.converteai.net/account/players/player/player.js', $parsed['script_url']);
    }

    public function testRejectsInvalidHostMultiplePlayersAndMismatchedIds(): void
    {
        foreach ([
            '<script src="https://evil.example/player.js"></script>',
            'https://scripts.converteai.net/account/players/one/v4/player.js https://scripts.converteai.net/account/players/two/v4/player.js',
            '<vturb-smartplayer id="vid-other"></vturb-smartplayer><script src="https://scripts.converteai.net/account/players/player/v4/player.js"></script>',
        ] as $embed) {
            $rejected = false;
            try { VturbEmbed::parse($embed); } catch (\InvalidArgumentException $e) { $rejected = true; }
            $this->assertTrue($rejected, 'Embed inválido deve ser rejeitado.');
        }
    }
}
