<?php
declare(strict_types=1);

namespace plugins\article\hook;

use plugins\article\repository\ArticleRepository;

class ArticleDiyHydrateHook
{
    public string $hook = 'diy.hydrate';
    public int $priority = 10;

    public function handle(array $context, mixed $prev): array
    {
        $comp = is_array($prev) ? $prev : [];
        if (($context['type'] ?? '') !== 'article-list') {
            return $comp;
        }
        $props = (array) ($context['props'] ?? []);
        $limit = (int) ($props['limit'] ?? 5);
        $categoryId = (int) ($props['category_id'] ?? 0);
        $useCategory = !empty($props['source']) && $props['source'] === 'category' && $categoryId > 0;

        $comp['props'] = is_array($comp['props'] ?? null) ? $comp['props'] : [];
        $comp['props']['article_list'] = app(ArticleRepository::class)
            ->getDiyComponentList($limit, $useCategory ? $categoryId : 0);
        return $comp;
    }
}
