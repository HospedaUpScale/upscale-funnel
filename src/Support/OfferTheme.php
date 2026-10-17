<?php

namespace App\Support;

class OfferTheme
{
    /** Cores editáveis no Hub. */
    public const COLOR_KEYS = [
        'page_bg', 'card_bg', 'card_bg_alt', 'primary_color', 'accent_color',
        'title_from', 'title_to', 'text_color', 'text_secondary',
        'cta_bg', 'cta_text', 'badge_bg', 'badge_text', 'border_color',
        'foil_start', 'foil_end'
    ];

    public const OPTIONAL_COLOR_KEYS = ['cta_bg', 'badge_bg', 'badge_text', 'border_color'];

    public static function defaults(): array
    {
        return [
            // Tema Base & Cores (Padrão: Azul Noturno da Referência Visual)
            'base_theme'            => 'dark_navy',
            'page_bg'               => '#06152D',
            'card_bg'               => '#0C2247',
            'card_bg_alt'           => '#06152D',
            'primary_color'         => '#1764FF',
            'accent_color'          => '#9FE870',
            'title_from'            => '#FFFFFF',
            'title_to'              => '#1764FF',
            'text_color'            => '#FFFFFF',
            'text_secondary'        => '#B7C5DE',
            'cta_bg'                => '#1764FF',
            'cta_text'              => '#FFFFFF',
            'badge_bg'              => 'rgba(23, 100, 255, 0.15)',
            'badge_text'            => '#1764FF',
            'border_color'          => 'rgba(23, 100, 255, 0.45)',
            'foil_start'            => '#0C2247',
            'foil_end'              => '#1764FF',
            'foil_texture'          => 'metallic_brushed',

            // Tipografia
            'font_title'            => 'Sora',
            'font_body'             => 'Inter',
            'title_size_desktop'    => 32,
            'title_size_mobile'     => 26,
            'body_size'             => 16,
            'title_weight'          => '800',
            'text_align'            => 'center',
            'letter_spacing'        => '-0.02em',
            'line_height'           => '1.2',

            // Fundo e Imagens
            'bg_type'               => 'gradient', // solid | gradient | image
            'bg_image_desktop'      => '',
            'bg_image_mobile'       => '',
            'bg_overlay_opacity'    => 0.4,
            'bg_blur'               => 0,

            // Logo Opcional da Campanha (Desativada por padrão)
            'campaign_logo_enabled' => false,
            'campaign_logo_url'     => '',
            'campaign_logo_size'    => 48,
            'campaign_logo_align'   => 'center',

            // Botões e Bordas
            'button_radius'         => 12,
            'button_border_width'   => 1,
            'button_shadow'         => 'suave', // nenhuma | suave | destacada
            'button_padding'        => 14,
            'card_radius'           => 16,

            // Mecânica de Revelação ('scratch' = raspadinha | 'wheel' = roleta premiada)
            'mechanic'              => 'scratch',

            // Roleta Premiada (usada quando mechanic = 'wheel')
            'wheel_spin_label'      => 'GIRAR A ROLETA',
            'wheel_result_label'    => 'VOCÊ GANHOU!',
            // Rótulos das fatias. A fatia vencedora é marcada por "is_win": true e
            // sempre cai sob o ponteiro (o giro é teatral, igual à raspadinha).
            'wheel_segments'        => [
                ['label' => 'BÔNUS', 'is_win' => false],
                ['label' => 'BOOSTER', 'is_win' => true],
                ['label' => 'QUASE!', 'is_win' => false],
                ['label' => 'GIRE+', 'is_win' => false],
                ['label' => 'SORTE', 'is_win' => false],
                ['label' => 'PRÊMIO', 'is_win' => false],
            ],

            // Saudação personalizada com o nome do comprador (quando disponível)
            'greeting_enabled'      => true,
            'greeting_template'     => 'Parabéns, {nome}!',

            // Área de Raspagem
            'scratch_width'         => 460,
            'scratch_height'        => 260,
            'brush_size'            => 28,
            'scratch_threshold'     => 35,
            'scratch_glow'          => true,
            'scratch_inst_top'      => 'RASPE AQUI',
            'scratch_inst_sub'      => 'Deslize o dedo para revelar.',
            'accessible_reveal_text'=> 'Revelar sem raspar',

            // Textos da Oferta e Indicadores
            'eyebrow'               => 'KIT BOBZÃO BITRUCK + FORD F250',
            'headline_start'        => 'Raspe e descubra seu',
            'headline_highlight'    => 'Booster',
            'subtitle_text'         => 'Uma condição especial para esta campanha.',
            'cta_label'             => 'ATIVAR MEU BOOSTER AGORA',
            'cta_subtext'           => 'Liberação imediata via Pix em 1 clique',
            'modal_win_title'       => 'PARABÉNS! VOCÊ DESBLOQUEOU',
            'show_steps'            => true,
            'step1_text'            => 'Revele',
            'step2_text'            => 'Confira',
            'step3_text'            => 'Ative',
            'show_conditions_footer'=> true,
            'conditions_footer_text'=> 'Consultar condições da oferta.',

            // Regras Financeiras e Gatilhos
            'anchor_price'          => 240.00,
            'timer_minutes'         => 10,
            'pitch_delay'           => 0,
            'video_src'             => '',
            'vturb_embed'           => '',
            'vturb_script_url'      => '',
            'vturb_aspect_ratio'    => 56.25,

            // Animações
            'reveal_animation'      => 'confetti_glow', // confetti_glow | zoom | fade
            'animation_duration'    => 0.8,
            'reduced_motion'        => false,
        ];
    }

    /** Predefinições de temas prontos. */
    public static function preset(string $name): array
    {
        $d = self::defaults();
        if ($name === 'light') {
            return array_merge($d, [
                'base_theme'    => 'light',
                'page_bg'       => '#F7F8FA',
                'card_bg'       => '#FFFFFF',
                'card_bg_alt'   => '#F2F4F7',
                'primary_color' => '#175CFF',
                'accent_color'  => '#12B76A',
                'title_from'    => '#182230',
                'title_to'      => '#175CFF',
                'text_color'    => '#182230',
                'text_secondary'=> '#667085',
                'cta_bg'        => '#175CFF',
                'cta_text'      => '#FFFFFF',
                'border_color'  => '#E6E9EF',
                'foil_start'    => '#D0D5DD',
                'foil_end'      => '#98A2B3',
                'foil_texture'  => 'silver_steel',
            ]);
        }

        // Padrão: dark_navy da referência visual
        return $d;
    }

    /** Tema salvo + padrões, com cores saneadas. */
    public static function merge(?array $stored): array
    {
        $theme = array_merge(self::defaults(), $stored ?? []);
        return self::sanitizeColors($theme);
    }

    /** Monta o tema a partir do payload recebido. */
    public static function fromInput(array $input, array $stored, string $title, float $price): array
    {
        $base = self::merge($stored);
        $out = $base;

        $pick = function (string $key) use ($input) {
            if (isset($input['theme']) && is_array($input['theme']) && array_key_exists($key, $input['theme'])) {
                return [true, $input['theme'][$key]];
            }
            if (array_key_exists($key, $input)) {
                return [true, $input[$key]];
            }
            return [false, null];
        };

        foreach (array_keys(self::defaults()) as $key) {
            [$has, $val] = $pick($key);
            if (!$has) {
                continue;
            }

            if (is_array($base[$key])) {
                // Campos estruturados (ex.: wheel_segments): aceita só arrays válidos.
                $out[$key] = is_array($val) ? array_values($val) : $base[$key];
            } elseif (is_bool($base[$key])) {
                $out[$key] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_int($base[$key])) {
                $out[$key] = (int)$val;
            } elseif (is_float($base[$key])) {
                $out[$key] = (float)$val;
            } else {
                $out[$key] = is_string($val) ? trim($val) : (string)$val;
            }
        }

        if ($title !== '' && empty($out['headline_highlight'])) {
            $out['headline_highlight'] = $title;
        }

        return self::sanitizeColors($out);
    }

    public static function sanitizeColors(array $theme): array
    {
        $defaults = self::defaults();
        foreach (self::COLOR_KEYS as $key) {
            $theme[$key] = self::color(
                $theme[$key] ?? '',
                $defaults[$key] ?? '#1764FF',
                in_array($key, self::OPTIONAL_COLOR_KEYS, true)
            );
        }

        return $theme;
    }

    public static function color(mixed $value, string $default, bool $optional): string
    {
        $v = is_string($value) ? trim($value) : '';
        if ($v === '') {
            return $optional ? '' : $default;
        }
        if (str_starts_with($v, 'rgba') || str_starts_with($v, 'rgb')) {
            return $v;
        }
        if (preg_match('/^#([0-9a-f]{3})$/i', $v, $m)) {
            $v = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
        }
        if (preg_match('/^#[0-9a-f]{6}$/i', $v)) {
            return strtoupper($v);
        }

        return $optional ? '' : $default;
    }
}
