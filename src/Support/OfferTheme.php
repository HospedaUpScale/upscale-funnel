<?php

namespace App\Support;

class OfferTheme
{
    /** Cores editáveis no Hub. Valor vazio nas opcionais = "automática" (deriva de outra cor). */
    public const COLOR_KEYS = [
        'primary_color', 'accent_color', 'card_bg', 'card_bg_alt',
        'title_from', 'title_to', 'text_color',
        'cta_bg', 'cta_text', 'badge_bg', 'badge_text', 'border_color',
        'foil_start', 'foil_end',
    ];

    public const OPTIONAL_COLOR_KEYS = ['cta_bg', 'badge_bg', 'badge_text', 'border_color'];

    public static function defaults(): array
    {
        return [
            'primary_color'     => '#0229C4',
            'accent_color'      => '#9FE870',
            'card_bg'           => '#05153F',
            'card_bg_alt'       => '#071C52',
            'title_from'        => '#FFFFFF',
            'title_to'          => '#B9C5FB',
            'text_color'        => '#DADEEC',
            'cta_bg'            => '',
            'cta_text'          => '#FFFFFF',
            'badge_bg'          => '',
            'badge_text'        => '',
            'border_color'      => '',
            'foil_start'        => '#021F96',
            'foil_end'          => '#3A5BE8',
            'eyebrow'           => 'OFERTA EXCLUSIVA DESBLOQUEADA',
            'greeting_pattern'  => 'Parabéns, {nome}! Você desbloqueou uma chance única.',
            'scratch_inst_top'  => 'RASPE COM O DEDO',
            'scratch_inst_sub'  => 'REVELE SUA OFERTA EXCLUSIVA',
            'cta_label'         => 'ATIVAR MEU BOOSTER 110X',
            'cta_subtext'       => 'Liberação imediata via Pix em 1 clique',
            'modal_win_title'   => 'PARABÉNS! VOCÊ DESBLOQUEOU',
            'anchor_price'      => 180.00,
            'timer_minutes'     => 3,
            'scratch_threshold' => 22,
            'pitch_delay'       => 0,
            'video_src'         => '',
        ];
    }

    /** Tema salvo + padrões, com cores saneadas. */
    public static function merge(?array $stored): array
    {
        $theme = array_merge(self::defaults(), $stored ?? []);

        // Antes o CTA era sempre #0229C4 (padrão gravado); agora vazio = segue a cor primária.
        if (strtoupper((string) ($theme['cta_bg'] ?? '')) === '#0229C4') {
            $theme['cta_bg'] = '';
        }

        return self::sanitizeColors($theme);
    }

    /**
     * Monta o tema a partir do payload do Hub. Chaves ausentes preservam o valor já salvo.
     */
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
            $out[$key] = match ($key) {
                'anchor_price', 'timer_minutes' => (float) $val,
                'scratch_threshold', 'pitch_delay' => (int) $val,
                default => is_string($val) ? trim($val) : (string) $val,
            };
        }

        [$hasCta] = $pick('cta_label');
        if (!$hasCta && $title !== '' && empty($stored['cta_label'])) {
            $out['cta_label'] = "ATIVAR MEU {$title}";
        }
        [$hasAnchor] = $pick('anchor_price');
        if (!$hasAnchor && !isset($stored['anchor_price'])) {
            $out['anchor_price'] = $price * 2;
        }

        return self::sanitizeColors($out);
    }

    public static function sanitizeColors(array $theme): array
    {
        $defaults = self::defaults();
        foreach (self::COLOR_KEYS as $key) {
            $theme[$key] = self::color(
                $theme[$key] ?? '',
                $defaults[$key],
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
        if (preg_match('/^#([0-9a-f]{3})$/i', $v, $m)) {
            $v = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
        }
        if (preg_match('/^#[0-9a-f]{6}$/i', $v)) {
            return strtoupper($v);
        }

        return $optional ? '' : $default;
    }
}
