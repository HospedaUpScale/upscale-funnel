<?php

namespace Tests\Unit;

use App\Support\OfferTheme;
use Tests\TestCase;

class SoundSettingsTest extends TestCase
{
    public function testSoundPreferencesAreAcceptedAndPreserved(): void
    {
        $theme = OfferTheme::fromInput(['theme' => ['sound_enabled' => false, 'sound_volume' => 0.35]], [], '', 0);
        $this->assertFalse($theme['sound_enabled']);
        $this->assertEquals(0.35, $theme['sound_volume']);
        $preserved = OfferTheme::fromInput([], $theme, '', 0);
        $this->assertFalse($preserved['sound_enabled']);
        $this->assertEquals(0.35, $preserved['sound_volume']);
    }

    public function testSoundVolumeIsBounded(): void
    {
        $quiet = OfferTheme::fromInput(['sound_volume' => -5], [], '', 0);
        $loud = OfferTheme::fromInput(['sound_volume' => 20], [], '', 0);
        $this->assertEquals(0.0, $quiet['sound_volume']);
        $this->assertEquals(1.0, $loud['sound_volume']);
    }
}
