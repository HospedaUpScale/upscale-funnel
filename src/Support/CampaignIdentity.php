<?php

namespace App\Support;

use PDO;

/**
 * Identidade visual da campanha: um conjunto de cores escolhido uma vez e
 * aplicado em todos os modelos da campanha (oferta de upsell e downsell, com
 * raspadinha, roleta ou oferta direta; pressel; landing).
 */
final class CampaignIdentity
{
    /** Temas prontos. Cada um descreve só as cores-base; os modelos derivam o resto. */
    public static function presets(): array
    {
        return [
            'upscale' => [
                'name' => 'upScale', 'description' => 'Azul da marca com destaque lima.', 'mode' => 'dark',
                'bg' => '#05153F', 'surface' => '#0A2469', 'surface_alt' => '#071C52', 'line' => 'rgba(138, 157, 247, 0.35)',
                'text' => '#FFFFFF', 'muted' => '#B4BEDB', 'primary' => '#0229C4', 'highlight' => '#8A9DF7',
                'accent' => '#9FE870', 'cta_text' => '#FFFFFF', 'foil_start' => '#0A2469', 'foil_end' => '#3A5BE8', 'foil_texture' => 'metallic_brushed',
            ],
            'azul-noturno' => [
                'name' => 'Azul noturno', 'description' => 'O visual padrão das ofertas.', 'mode' => 'dark',
                'bg' => '#06152D', 'surface' => '#0C2247', 'surface_alt' => '#06152D', 'line' => 'rgba(23, 100, 255, 0.45)',
                'text' => '#FFFFFF', 'muted' => '#B7C5DE', 'primary' => '#1764FF', 'highlight' => '#1764FF',
                'accent' => '#9FE870', 'cta_text' => '#FFFFFF', 'foil_start' => '#0C2247', 'foil_end' => '#1764FF', 'foil_texture' => 'metallic_brushed',
            ],
            'ouro-premium' => [
                'name' => 'Ouro premium', 'description' => 'Grafite com dourado, para prêmios de alto valor.', 'mode' => 'dark',
                'bg' => '#0B0E12', 'surface' => '#141922', 'surface_alt' => '#0F1319', 'line' => '#242C38',
                'text' => '#EEF1F5', 'muted' => '#A1ADBD', 'primary' => '#F5A524', 'highlight' => '#F5A524',
                'accent' => '#FFD66B', 'cta_text' => '#1A1206', 'foil_start' => '#3A2A05', 'foil_end' => '#F5A524', 'foil_texture' => 'metallic_brushed',
            ],
            'amarelo-impacto' => [
                'name' => 'Amarelo impacto', 'description' => 'Preto e amarelo, alto contraste.', 'mode' => 'dark',
                'bg' => '#0A0A0A', 'surface' => '#141414', 'surface_alt' => '#0F0F0F', 'line' => '#232323',
                'text' => '#FFFFFF', 'muted' => '#A3A3A3', 'primary' => '#FFD400', 'highlight' => '#FFD400',
                'accent' => '#FFFFFF', 'cta_text' => '#0A0A0A', 'foil_start' => '#1F1F1F', 'foil_end' => '#FFD400', 'foil_texture' => 'metallic_brushed',
            ],
            'verde-pix' => [
                'name' => 'Verde Pix', 'description' => 'Verde-água do Pix sobre fundo escuro.', 'mode' => 'dark',
                'bg' => '#04140F', 'surface' => '#0A241B', 'surface_alt' => '#061A13', 'line' => 'rgba(50, 188, 173, 0.35)',
                'text' => '#F0FFF9', 'muted' => '#A6C9BD', 'primary' => '#32BCAD', 'highlight' => '#5EEAD4',
                'accent' => '#9FE870', 'cta_text' => '#04140F', 'foil_start' => '#0A241B', 'foil_end' => '#32BCAD', 'foil_texture' => 'metallic_brushed',
            ],
            'vermelho-urgencia' => [
                'name' => 'Vermelho urgência', 'description' => 'Para ações com prazo curto.', 'mode' => 'dark',
                'bg' => '#14070A', 'surface' => '#2A0D13', 'surface_alt' => '#1C090D', 'line' => 'rgba(255, 90, 95, 0.35)',
                'text' => '#FFF5F5', 'muted' => '#E0B4B7', 'primary' => '#E5484D', 'highlight' => '#FF7A7F',
                'accent' => '#FFD400', 'cta_text' => '#FFFFFF', 'foil_start' => '#2A0D13', 'foil_end' => '#E5484D', 'foil_texture' => 'metallic_brushed',
            ],
            'roxo-neon' => [
                'name' => 'Roxo neon', 'description' => 'Roxo vibrante com detalhe ciano.', 'mode' => 'dark',
                'bg' => '#0E0620', 'surface' => '#1C0E3A', 'surface_alt' => '#140A2B', 'line' => 'rgba(167, 139, 250, 0.35)',
                'text' => '#FFFFFF', 'muted' => '#C4B5FD', 'primary' => '#7C3AED', 'highlight' => '#A78BFA',
                'accent' => '#22D3EE', 'cta_text' => '#FFFFFF', 'foil_start' => '#1C0E3A', 'foil_end' => '#7C3AED', 'foil_texture' => 'metallic_brushed',
            ],
            'claro-clean' => [
                'name' => 'Claro clean', 'description' => 'Fundo claro, azul da marca nos botões.', 'mode' => 'light',
                'bg' => '#F7F8FA', 'surface' => '#FFFFFF', 'surface_alt' => '#F2F4F7', 'line' => '#E6E9EF',
                'text' => '#182230', 'muted' => '#667085', 'primary' => '#0229C4', 'highlight' => '#0229C4',
                'accent' => '#12B76A', 'cta_text' => '#FFFFFF', 'foil_start' => '#D0D5DD', 'foil_end' => '#98A2B3', 'foil_texture' => 'silver_steel',
            ],
        ];
    }

    /** Lista pública dos temas prontos (para o hub). */
    public static function presetList(): array
    {
        $out = [];
        foreach (self::presets() as $id => $p) {
            $out[] = ['id' => $id] + $p;
        }
        return $out;
    }

    /**
     * Normaliza a escolha recebida: um tema pronto ({preset}) ou personalizado
     * ({preset:'custom', mode, primary, accent}).
     */
    public static function fromInput(array $input): array
    {
        $presets = self::presets();
        $id = strtolower(trim((string)($input['preset'] ?? '')));
        if (isset($presets[$id])) {
            return ['preset' => $id] + $presets[$id];
        }
        if ($id !== 'custom') {
            throw new \InvalidArgumentException('Escolha um dos temas prontos ou "Personalizado".');
        }

        $mode = ($input['mode'] ?? 'dark') === 'light' ? 'light' : 'dark';
        $primary = OfferTheme::color($input['primary'] ?? '', '', true);
        $accent = OfferTheme::color($input['accent'] ?? '', '', true);
        if ($primary === '') {
            throw new \InvalidArgumentException('Informe a cor principal no formato #RRGGBB.');
        }
        $base = $presets[$mode === 'light' ? 'claro-clean' : 'azul-noturno'];
        $base['name'] = 'Personalizado';
        $base['description'] = 'Cores definidas para esta campanha.';
        $base['primary'] = $primary;
        $base['highlight'] = $primary;
        $base['accent'] = $accent !== '' ? $accent : $base['accent'];
        $base['cta_text'] = self::inkFor($primary);
        $base['line'] = self::rgba($primary, $mode === 'light' ? 0.25 : 0.4);
        if ($mode === 'dark') $base['foil_end'] = $primary;
        return ['preset' => 'custom'] + $base;
    }

    /** Cores da oferta (raspadinha, roleta e oferta direta usam o mesmo tema). */
    public static function toOfferTheme(array $id): array
    {
        return [
            'base_theme'     => $id['mode'] === 'light' ? 'light' : 'custom',
            'page_bg'        => $id['bg'],
            'card_bg'        => $id['surface'],
            'card_bg_alt'    => $id['surface_alt'],
            'primary_color'  => $id['primary'],
            'accent_color'   => $id['accent'],
            'title_from'     => $id['text'],
            'title_to'       => $id['highlight'],
            'text_color'     => $id['text'],
            'text_secondary' => $id['muted'],
            'cta_bg'         => $id['primary'],
            'cta_text'       => $id['cta_text'],
            'badge_bg'       => self::rgba($id['primary'], 0.15),
            'badge_text'     => $id['highlight'],
            'border_color'   => $id['line'],
            'foil_start'     => $id['foil_start'],
            'foil_end'       => $id['foil_end'],
            'foil_texture'   => $id['foil_texture'],
        ];
    }

    /** Cores da Pressel Premium (settings_config.theme e variáveis do HTML). */
    public static function toPresselTheme(array $id): array
    {
        return [
            'bg'         => $id['bg'],
            'surface'    => $id['surface'],
            'line'       => $id['line'],
            'fg'         => $id['text'],
            'muted'      => $id['muted'],
            'accent'     => $id['primary'],
            'accent_ink' => $id['cta_text'],
        ];
    }

    /**
     * Aplica a identidade em todos os modelos da campanha e grava a escolha.
     * Devolve quantos modelos foram atualizados por tipo.
     */
    public static function apply(PDO $pdo, int $workspaceId, int $campaignId, array $identity): array
    {
        $stored = $identity;
        $stored['applied_at'] = date('Y-m-d H:i:s');
        $offerColors = self::toOfferTheme($identity);
        $presselColors = self::toPresselTheme($identity);
        $summary = ['offers' => 0, 'pressel' => 0, 'landing' => 0, 'other_pages' => 0];

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE campaigns SET brand_identity = ? WHERE id = ? AND workspace_id = ?')
                ->execute([json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $campaignId, $workspaceId]);

            // 1. Ofertas (upsell e downsell): tema base, rascunho e versão publicada
            $offers = $pdo->prepare('SELECT id, theme_config, draft_config, published_config FROM upsell_offers WHERE workspace_id = ? AND campaign_id = ?');
            $offers->execute([$workspaceId, $campaignId]);
            $upOffer = $pdo->prepare('UPDATE upsell_offers SET theme_config = ?, draft_config = ?, published_config = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            foreach ($offers->fetchAll(PDO::FETCH_ASSOC) as $o) {
                $theme = self::decode($o['theme_config']);
                $theme = OfferTheme::sanitizeColors(array_merge($theme, $offerColors));
                $draft = self::decode($o['draft_config']);
                if ($draft) $draft['theme'] = OfferTheme::sanitizeColors(array_merge($draft['theme'] ?? [], $offerColors));
                $published = self::decode($o['published_config']);
                if ($published) $published['theme'] = OfferTheme::sanitizeColors(array_merge($published['theme'] ?? [], $offerColors));
                $upOffer->execute([
                    self::encode($theme),
                    $draft ? self::encode($draft) : $o['draft_config'],
                    $published ? self::encode($published) : $o['published_config'],
                    (int)$o['id'],
                ]);
                $summary['offers']++;
            }

            // 2. Páginas da campanha
            $pages = $pdo->prepare('SELECT id, template_slug, settings_config, html_content, draft_content, published_content FROM funnels WHERE workspace_id = ? AND campaign_id = ?');
            $pages->execute([$workspaceId, $campaignId]);
            $upPage = $pdo->prepare('UPDATE funnels SET settings_config = ?, html_content = ?, draft_content = ?, published_content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            foreach ($pages->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $slug = (string)$f['template_slug'];
                if ($slug === 'pressel-premium') {
                    $settings = self::decode($f['settings_config']);
                    $settings['theme'] = array_merge($settings['theme'] ?? [], $presselColors);
                    $upPage->execute([
                        self::encode($settings),
                        self::presselHtml($f['html_content'], $presselColors),
                        self::presselHtml($f['draft_content'], $presselColors),
                        self::presselHtml($f['published_content'], $presselColors),
                        (int)$f['id'],
                    ]);
                    $summary['pressel']++;
                } elseif (str_starts_with($slug, 'landing-enxuta')) {
                    $upPage->execute([
                        $f['settings_config'],
                        self::landingHtml($f['html_content'], $identity),
                        self::landingHtml($f['draft_content'], $identity),
                        self::landingHtml($f['published_content'], $identity),
                        (int)$f['id'],
                    ]);
                    $summary['landing']++;
                } else {
                    // Páginas de etapas (oferta, raspadinha, roleta, downsell) leem as cores da oferta.
                    $summary['other_pages']++;
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        return $summary;
    }

    /** Troca as variáveis de cor no HTML gerado da Pressel Premium. */
    public static function presselHtml(?string $html, array $c): ?string
    {
        if ($html === null || $html === '') return $html;
        foreach (['bg', 'surface', 'line', 'fg', 'muted', 'accent', 'accent-ink'] as $var) {
            $key = $var === 'accent-ink' ? 'accent_ink' : $var;
            $html = preg_replace('/(--' . preg_quote($var, '/') . '\s*:\s*)[^;]+;/', '${1}' . $c[$key] . ';', $html, 1);
        }
        $html = preg_replace('/(<meta name="theme-color" content=")[^"]*(")/', '${1}' . $c['bg'] . '${2}', $html, 1);
        // brilho do botão principal, que repete a cor de destaque
        if (preg_match('/^#[0-9A-F]{6}$/i', $c['accent'])) {
            $html = preg_replace('/(0 10px 30px -10px )#[0-9a-fA-F]{6}99/', '${1}' . $c['accent'] . '99', $html);
        }
        return $html;
    }

    /** Troca a cor de destaque no bloco <style id="lx-theme"> da Landing Enxuta. */
    public static function landingHtml(?string $html, array $id): ?string
    {
        if ($html === null || $html === '' || !str_contains($html, 'lx-theme')) return $html;
        return preg_replace_callback('/(<style id="lx-theme">)(.*?)(<\/style>)/s', function ($m) use ($id) {
            $css = preg_replace('/(--accent\s*:\s*)[^;]+;/', '${1}' . $id['primary'] . ';', $m[2], 1);
            $css = preg_replace('/(--accent-ink\s*:\s*)[^;]+;/', '${1}' . $id['cta_text'] . ';', $css, 1);
            return $m[1] . $css . $m[3];
        }, $html, 1);
    }

    /** Identidade gravada na campanha, ou null. */
    public static function stored(?string $json): ?array
    {
        $d = self::decode($json);
        return $d ?: null;
    }

    public static function rgba(string $hex, float $alpha): string
    {
        if (!preg_match('/^#([0-9a-f]{6})$/i', $hex, $m)) return $hex;
        $n = hexdec($m[1]);
        return sprintf('rgba(%d, %d, %d, %s)', ($n >> 16) & 255, ($n >> 8) & 255, $n & 255, rtrim(rtrim(number_format($alpha, 2, '.', ''), '0'), '.'));
    }

    /** Texto escuro ou branco sobre a cor, o que der mais contraste. */
    public static function inkFor(string $hex): string
    {
        if (!preg_match('/^#([0-9a-f]{6})$/i', $hex, $m)) return '#FFFFFF';
        $n = hexdec($m[1]);
        $lin = array_map(function ($c) {
            $c /= 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, [($n >> 16) & 255, ($n >> 8) & 255, $n & 255]);
        $l = 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
        return (($l + 0.05) / 0.0533) >= (1.05 / ($l + 0.05)) ? '#0A0A0A' : '#FFFFFF';
    }

    private static function decode(mixed $v): array
    {
        if (is_array($v)) return $v;
        if (!is_string($v) || $v === '') return [];
        $d = json_decode($v, true);
        return is_array($d) ? $d : [];
    }

    private static function encode(array $v): string
    {
        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
