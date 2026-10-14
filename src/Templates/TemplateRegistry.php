<?php

declare(strict_types=1);

namespace App\Templates;

class TemplateRegistry
{
    /**
     * Retorna lista de todos os templates cadastrados no sistema.
     */
    public static function getAll(): array
    {
        return [
            [
                'slug' => PresselPremiumTemplate::SLUG,
                'name' => PresselPremiumTemplate::NAME,
                'description' => PresselPremiumTemplate::DESCRIPTION,
                'category' => 'campanhas',
                'badge' => 'Premium',
                'thumbnail' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=500&auto=format&fit=crop&q=80',
                'preview_accent' => '#f5a524',
                'features' => [
                    'Hero imersiva com degradê',
                    'Faixa animada com controle de movimento',
                    'Vídeos verticais de entregas',
                    'CTA fixo sincronizado no mobile',
                    'Acordeão de FAQ acessível',
                ],
            ],
            [
                'slug' => 'default',
                'name' => 'Página Booster Padrão',
                'description' => 'Página de oferta com temporizador regressivo, card de cotas e botão com checkout Pix direto.',
                'category' => 'upsell',
                'badge' => 'Clássico',
                'thumbnail' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=500&auto=format&fit=crop&q=80',
                'preview_accent' => '#175cff',
                'features' => [
                    'Temporizador evergreen',
                    'Card de cotas e benefícios',
                    'Checkout Pix direto',
                ],
            ],
        ];
    }

    /**
     * Busca dados de um template específico pelo slug.
     */
    public static function get(string $slug): ?array
    {
        foreach (self::getAll() as $tpl) {
            if ($tpl['slug'] === $slug) {
                return $tpl;
            }
        }
        return null;
    }

    /**
     * Retorna a configuração padrão inicial para o template.
     */
    public static function getDefaultConfig(string $slug): array
    {
        if ($slug === PresselPremiumTemplate::SLUG) {
            return PresselPremiumTemplate::getDefaultConfig();
        }
        return [];
    }

    /**
     * Renderiza o HTML final com base no template escolhido.
     */
    public static function render(string $slug, array $blocksConfig, array $settingsConfig = [], array $options = []): string
    {
        if ($slug === PresselPremiumTemplate::SLUG) {
            return PresselPremiumTemplate::render($blocksConfig, $settingsConfig, $options);
        }
        return '';
    }
}
