<?php

namespace Tests\Unit;

use App\Support\CampaignIdentity;
use App\Support\OfferTheme;
use Tests\TestCase;

class CampaignIdentityTest extends TestCase
{
    public function testEveryPresetProducesCompleteOfferTheme(): void
    {
        $keys = array_keys(OfferTheme::defaults());
        foreach (CampaignIdentity::presets() as $id => $preset) {
            $theme = CampaignIdentity::toOfferTheme(CampaignIdentity::fromInput(['preset' => $id]));
            foreach (['page_bg', 'card_bg', 'primary_color', 'cta_bg', 'cta_text', 'foil_start', 'foil_end'] as $k) {
                $this->assertTrue(in_array($k, $keys, true), "chave {$k} não existe em OfferTheme");
                $this->assertTrue(($theme[$k] ?? '') !== '', "preset {$id} sem {$k}");
            }
        }
        $this->assertTrue(count(CampaignIdentity::presetList()) >= 6, 'deveria ter temas prontos');
    }

    public function testCustomIdentityUsesChosenColors(): void
    {
        $id = CampaignIdentity::fromInput(['preset' => 'custom', 'mode' => 'dark', 'primary' => '#ff5500', 'accent' => '#00ff88']);
        $theme = CampaignIdentity::toOfferTheme($id);
        $this->assertEquals('custom', $id['preset']);
        $this->assertEquals(strtoupper('#ff5500'), strtoupper($theme['cta_bg']));
        $this->assertEquals(strtoupper('#00ff88'), strtoupper($theme['accent_color']));
    }

    public function testInvalidInputIsRejected(): void
    {
        foreach ([['preset' => 'nao-existe'], ['preset' => 'custom', 'primary' => 'azul']] as $input) {
            $failed = false;
            try {
                CampaignIdentity::fromInput($input);
            } catch (\InvalidArgumentException $e) {
                $failed = true;
            }
            $this->assertTrue($failed, 'entrada inválida deveria falhar: ' . json_encode($input));
        }
    }

    public function testPresselHtmlVariablesAreReplaced(): void
    {
        $html = '<meta name="theme-color" content="#000000"><style>:root{--bg:#111111;--surface:#222222;--line:#333333;'
            . '--fg:#ffffff;--muted:#999999;--accent:#ff0000;--accent-ink:#ffffff;}'
            . '.cta{box-shadow:0 10px 30px -10px #ff000099}</style>';
        $c = CampaignIdentity::toPresselTheme(CampaignIdentity::fromInput(['preset' => 'upscale']));
        $out = CampaignIdentity::presselHtml($html, $c);
        $this->assertStringContains('--bg:#05153F;', $out);
        $this->assertStringContains('--accent:#0229C4;', $out);
        $this->assertStringContains('content="#05153F"', $out);
        $this->assertStringContains('0 10px 30px -10px #0229C499', $out);
    }

    public function testLandingOnlyChangesAccentInsideThemeBlock(): void
    {
        $html = '<style id="lx-theme">:root{--accent:#123456;--accent-ink:#000000;}</style><style>.x{--accent:#abcdef;}</style>';
        $id = CampaignIdentity::fromInput(['preset' => 'upscale']);
        $out = CampaignIdentity::landingHtml($html, $id);
        $this->assertStringContains('<style id="lx-theme">:root{--accent:#0229C4;--accent-ink:' . $id['cta_text'] . ';}', $out);
        $this->assertStringContains('.x{--accent:#abcdef;}', $out);
        $this->assertEquals('<p>sem tema</p>', CampaignIdentity::landingHtml('<p>sem tema</p>', $id));
    }
}
