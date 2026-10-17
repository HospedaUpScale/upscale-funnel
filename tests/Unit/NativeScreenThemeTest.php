<?php

namespace Tests\Unit;

use App\Support\OfferTheme;
use Tests\TestCase;

class NativeScreenThemeTest extends TestCase
{
    public function testRealScreenCopyIsSavedAndPreserved(): void
    {
        $copy = ['headline_start' => 'Raspe sua condição', 'headline_highlight' => 'Campanha real',
            'wheel_heading' => 'Gire para conferir', 'wheel_highlight' => 'Sua oferta',
            'wheel_subtitle' => 'Veja os detalhes depois de girar.', 'wheel_spin_label' => 'GIRAR AGORA',
            'offer_headline_start' => 'Seu Booster está', 'offer_headline_highlight' => 'pronto para ativar.'];
        $saved = OfferTheme::fromInput(['theme' => $copy], [], '', 0);
        $preserved = OfferTheme::fromInput([], $saved, '', 0);
        foreach ($copy as $key => $value) $this->assertEquals($value, $preserved[$key]);
    }
}
