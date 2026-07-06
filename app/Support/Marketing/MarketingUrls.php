<?php

namespace App\Support\Marketing;

/**
 * Zentrale Liste aller öffentlichen Marketing-URLs — eine Quelle für die
 * Sitemap und für IndexNow-Pings nach dem Deploy.
 */
class MarketingUrls
{
    /**
     * @return array<int, array{loc: string, priority: string, lastmod?: string}>
     */
    public static function all(): array
    {
        return [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('pricing.show'), 'priority' => '0.8'],
            ['loc' => route('kommunen.show'), 'priority' => '0.9'],
            ['loc' => route('nis2-quick-check'), 'priority' => '0.9'],
            ['loc' => route('guides.index'), 'priority' => '0.8'],
            ...array_map(fn (array $guide) => [
                'loc' => route('guides.show', $guide['slug']),
                'priority' => '0.8',
                'lastmod' => $guide['updated'],
            ], array_values(GuideCatalog::all())),
            ...array_map(fn (string $slug) => [
                'loc' => route('feature.show', $slug),
                'priority' => '0.7',
            ], FeatureCatalog::slugs()),
        ];
    }

    /**
     * Nur die URLs, z.B. für IndexNow.
     *
     * @return array<int, string>
     */
    public static function locations(): array
    {
        return array_column(self::all(), 'loc');
    }
}
