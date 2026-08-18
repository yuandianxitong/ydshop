<?php
declare(strict_types=1);

namespace app\service\diy;

use core\base\Service;
use core\plugin\PluginManager;
use core\plugin\PluginManifest;

/**
 * DIY 链接 / 画布组件 / C 端入口：核心 + 回退表 + 已装 plugin.json。
 */
class DiyCatalogService extends Service
{
    /**
     * @return array{platform: string, links: list<array>, widgets: list<array>, entries: list<array>, public_paths: list<string>}
     */
    public function catalog(string $platform): array
    {
        $platform = $platform === 'pc' ? 'pc' : 'uniapp';

        return [
            'platform'     => $platform,
            'links'        => $this->buildLinks($platform),
            'widgets'      => $this->buildWidgets(),
            'entries'      => $this->buildEntries($platform),
            'public_paths' => $this->buildPublicPaths(),
        ];
    }

    /**
     * @return array{entries: list<array>, public_paths: list<string>}
     */
    public function storefrontExtras(): array
    {
        return [
            'entries'      => array_merge(
                $this->buildEntries('uniapp'),
                $this->buildEntries('pc'),
            ),
            'public_paths' => $this->buildPublicPaths(),
        ];
    }

    /**
     * @return list<array{key: string, label: string, items: list<array>}>
     */
    private function buildLinks(string $platform): array
    {
        $groups = [];
        foreach ($this->coreLinks() as $row) {
            $this->appendLink($groups, $row, $platform);
        }
        foreach ($this->fallbackPluginLinks() as $row) {
            $plugin = (string) ($row['plugin'] ?? '');
            if ($plugin !== '' && !PluginManager::isInstalled($plugin)) {
                continue;
            }
            $this->appendLink($groups, $row, $platform);
        }

        foreach (PluginManager::getAll() as $manifest) {
            foreach ($this->manifestLinks($manifest) as $row) {
                $this->appendLink($groups, $row, $platform);
            }
        }

        $out = [];
        foreach ($this->groupMeta() as $key => $label) {
            if (empty($groups[$key])) {
                continue;
            }
            $out[] = [
                'key'   => $key,
                'label' => $label,
                'items' => array_values($groups[$key]),
            ];
        }
        return $out;
    }

    /**
     * @return list<array{type: string, label: string, icon: string, group: string, default_props: array}>
     */
    private function buildWidgets(): array
    {
        $widgets = [];
        foreach ($this->coreWidgets() as $row) {
            $widgets[$row['type']] = $row;
        }
        foreach ($this->fallbackPluginWidgets() as $row) {
            $plugin = (string) ($row['plugin'] ?? '');
            if ($plugin !== '' && !PluginManager::isInstalled($plugin)) {
                continue;
            }
            unset($row['plugin']);
            $widgets[$row['type']] = $row;
        }
        foreach (PluginManager::getAll() as $manifest) {
            foreach ($this->manifestWidgets($manifest) as $row) {
                $widgets[$row['type']] = $row;
            }
        }
        return array_values($widgets);
    }

    /**
     * @return list<array{slot: string, label: string, path: string, platform: string}>
     */
    private function buildEntries(string $platform): array
    {
        $entries = [];
        foreach ($this->fallbackEntries() as $row) {
            $plugin = (string) ($row['plugin'] ?? '');
            if ($plugin !== '' && !PluginManager::isInstalled($plugin)) {
                continue;
            }
            $path = $row[$platform] ?? '';
            if (!is_string($path) || $path === '') {
                continue;
            }
            $key = $row['slot'] . '|' . $row['label'] . '|' . $platform;
            $entries[$key] = [
                'slot'     => $row['slot'],
                'label'    => $row['label'],
                'path'     => $path,
                'platform' => $platform,
            ];
        }
        foreach (PluginManager::getAll() as $manifest) {
            $cEnd = is_array($manifest->raw['c_end'] ?? null) ? $manifest->raw['c_end'] : [];
            foreach ((array) ($cEnd['entries'] ?? []) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $path = $row[$platform] ?? '';
                if (!is_string($path) || $path === '') {
                    continue;
                }
                $slot  = (string) ($row['slot'] ?? 'user.functions');
                $label = (string) ($row['label'] ?? '');
                if ($label === '') {
                    continue;
                }
                $key = $slot . '|' . $label . '|' . $platform;
                $entries[$key] = [
                    'slot'     => $slot,
                    'label'    => $label,
                    'path'     => $path,
                    'platform' => $platform,
                ];
            }
        }
        return array_values($entries);
    }

    /**
     * @return list<string>
     */
    private function buildPublicPaths(): array
    {
        $paths = [];
        foreach ($this->fallbackPublicPaths() as $row) {
            $plugin = (string) ($row['plugin'] ?? '');
            if ($plugin !== '' && !PluginManager::isInstalled($plugin)) {
                continue;
            }
            $path = (string) ($row['path'] ?? '');
            if ($path !== '') {
                $paths[$path] = $path;
            }
        }
        foreach (PluginManager::getAll() as $manifest) {
            $cEnd = is_array($manifest->raw['c_end'] ?? null) ? $manifest->raw['c_end'] : [];
            foreach ((array) ($cEnd['public_paths'] ?? []) as $path) {
                if (is_string($path) && $path !== '') {
                    $paths[$path] = $path;
                }
            }
        }
        return array_values($paths);
    }

    /**
     * @param array<string, list<array>> $groups
     * @param array<string, mixed>       $row
     */
    private function appendLink(array &$groups, array $row, string $platform): void
    {
        $path = $row[$platform] ?? '';
        if (!is_string($path) || $path === '') {
            return;
        }
        $category = (string) ($row['category'] ?? 'basic');
        $label    = (string) ($row['label'] ?? '');
        if ($label === '') {
            return;
        }
        $item = [
            'label'       => $label,
            'path'        => $path,
            'need_select' => !empty($row['need_select']),
            'select_type' => $row['select_type'] ?? null,
        ];
        $groups[$category] ??= [];
        $groups[$category][$label] = $item;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function manifestLinks(PluginManifest $manifest): array
    {
        $diy = is_array($manifest->raw['diy'] ?? null) ? $manifest->raw['diy'] : [];
        $out = [];
        foreach ((array) ($diy['links'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'category'    => (string) ($row['category'] ?? 'marketing'),
                'label'       => (string) ($row['label'] ?? ''),
                'uniapp'      => (string) ($row['uniapp'] ?? ''),
                'pc'          => (string) ($row['pc'] ?? ''),
                'need_select' => !empty($row['need_select']),
                'select_type' => $row['select_type'] ?? null,
            ];
        }
        return $out;
    }

    /**
     * @return list<array{type: string, label: string, icon: string, group: string, default_props: array}>
     */
    private function manifestWidgets(PluginManifest $manifest): array
    {
        $diy = is_array($manifest->raw['diy'] ?? null) ? $manifest->raw['diy'] : [];
        $out = [];
        foreach ((array) ($diy['widgets'] ?? []) as $row) {
            if (!is_array($row) || empty($row['type'])) {
                continue;
            }
            $out[] = [
                'type'          => (string) $row['type'],
                'label'         => (string) ($row['label'] ?? $row['type']),
                'icon'          => (string) ($row['icon'] ?? 'i-lucide:box'),
                'group'         => (string) ($row['group'] ?? '营销组件'),
                'default_props' => is_array($row['default_props'] ?? null) ? $row['default_props'] : [],
            ];
        }
        return $out;
    }

    /** @return array<string, string> */
    private function groupMeta(): array
    {
        return [
            'basic'     => '基础页面',
            'goods'     => '商品',
            'marketing' => '营销',
            'user'      => '用户中心',
            'content'   => '内容',
            'topic'     => '专题',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function coreLinks(): array
    {
        return [
            ['category' => 'basic', 'label' => '首页', 'uniapp' => '/pages/index/index', 'pc' => '/'],
            ['category' => 'basic', 'label' => '分类页', 'uniapp' => '/pages/category/index', 'pc' => '/category'],
            ['category' => 'basic', 'label' => '购物车', 'uniapp' => '/pages/cart/index', 'pc' => '/cart'],
            ['category' => 'basic', 'label' => '个人中心', 'uniapp' => '/pages/my/index', 'pc' => '/user'],
            ['category' => 'goods', 'label' => '商品详情', 'uniapp' => '/modules/goods/pages/detail?id=', 'pc' => '/goods/', 'need_select' => true, 'select_type' => 'goods'],
            ['category' => 'goods', 'label' => '商品列表', 'uniapp' => '/modules/goods/pages/list', 'pc' => '/goods'],
            ['category' => 'user', 'label' => '我的订单', 'uniapp' => '/modules/order/pages/list', 'pc' => '/order'],
            ['category' => 'user', 'label' => '浏览记录', 'uniapp' => '/modules/user/pages/history', 'pc' => ''],
            ['category' => 'topic', 'label' => '专题页面', 'uniapp' => '/modules/diy/pages/custom?id=', 'pc' => '/diy/custom/', 'need_select' => true, 'select_type' => 'topic'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fallbackPluginLinks(): array
    {
        return [
            ['plugin' => 'flash_sale', 'category' => 'marketing', 'label' => '限时秒杀', 'uniapp' => '/modules/marketing/pages/flash-sale', 'pc' => '/marketing/flash-sale'],
            ['plugin' => 'group_buy', 'category' => 'marketing', 'label' => '拼团活动', 'uniapp' => '/modules/marketing/pages/group-buy', 'pc' => '/marketing/group-buy'],
            ['plugin' => 'points_product', 'category' => 'marketing', 'label' => '积分商城', 'uniapp' => '/modules/marketing/pages/points-mall', 'pc' => '/marketing/points-mall'],
            ['plugin' => 'lottery', 'category' => 'marketing', 'label' => '抽奖活动', 'uniapp' => '/modules/marketing/pages/lottery', 'pc' => ''],
            ['plugin' => 'coupon', 'category' => 'marketing', 'label' => '优惠券中心', 'uniapp' => '/modules/marketing/pages/coupon', 'pc' => '/marketing/coupon'],
            ['plugin' => 'sign', 'category' => 'user', 'label' => '签到', 'uniapp' => '/modules/user/pages/sign', 'pc' => '/user/sign'],
            ['plugin' => 'distribution', 'category' => 'user', 'label' => '分销中心', 'uniapp' => '/modules/distribution/pages/index', 'pc' => '/user/distribution'],
            ['plugin' => 'article', 'category' => 'content', 'label' => '文章详情', 'uniapp' => '/modules/article/pages/article-detail?id=', 'pc' => '/article/', 'need_select' => true, 'select_type' => 'article'],
            ['plugin' => 'article', 'category' => 'content', 'label' => '文章列表', 'uniapp' => '', 'pc' => '/article'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function coreWidgets(): array
    {
        return [
            ['type' => 'banner', 'label' => '轮播图', 'icon' => 'i-svg:diy-banner', 'group' => '基础组件', 'default_props' => []],
            ['type' => 'image-ad', 'label' => '图片广告', 'icon' => 'i-svg:diy-image-ad', 'group' => '基础组件', 'default_props' => []],
            ['type' => 'rich-text', 'label' => '富文本', 'icon' => 'i-svg:diy-rich-text', 'group' => '基础组件', 'default_props' => []],
            ['type' => 'title-bar', 'label' => '标题栏', 'icon' => 'i-svg:diy-title-bar', 'group' => '基础组件', 'default_props' => []],
            ['type' => 'divider', 'label' => '辅助分割', 'icon' => 'i-svg:diy-divider', 'group' => '基础组件', 'default_props' => []],
            ['type' => 'video', 'label' => '视频', 'icon' => 'i-svg:diy-video', 'group' => '基础组件', 'default_props' => []],
            ['type' => 'image-cube', 'label' => '图片魔方', 'icon' => 'i-svg:diy-image-cube', 'group' => '基础组件', 'default_props' => []],
            ['type' => 'goods-grid', 'label' => '商品组', 'icon' => 'i-svg:diy-goods-grid', 'group' => '商品组件', 'default_props' => []],
            ['type' => 'category-nav', 'label' => '分类导航', 'icon' => 'i-svg:diy-category-nav', 'group' => '商品组件', 'default_props' => []],
            ['type' => 'search-bar', 'label' => '搜索框', 'icon' => 'i-svg:diy-search-bar', 'group' => '商品组件', 'default_props' => []],
            ['type' => 'ad-slot', 'label' => '广告位', 'icon' => 'i-lucide:image', 'group' => '营销组件', 'default_props' => []],
            ['type' => 'notice', 'label' => '公告', 'icon' => 'i-svg:diy-notice', 'group' => '营销组件', 'default_props' => []],
            ['type' => 'nav-grid', 'label' => '图文导航', 'icon' => 'i-svg:diy-nav-grid', 'group' => '导航组件', 'default_props' => []],
            ['type' => 'float-button', 'label' => '悬浮按钮', 'icon' => 'i-svg:diy-float-button', 'group' => '导航组件', 'default_props' => []],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fallbackPluginWidgets(): array
    {
        return [
            ['plugin' => 'article', 'type' => 'article-list', 'label' => '文章列表', 'icon' => 'i-svg:diy-article-list', 'group' => '基础组件', 'default_props' => []],
            ['plugin' => 'coupon', 'type' => 'coupon-list', 'label' => '优惠券', 'icon' => 'i-svg:diy-coupon', 'group' => '营销组件', 'default_props' => []],
            ['plugin' => 'flash_sale', 'type' => 'seckill', 'label' => '秒杀', 'icon' => 'i-svg:diy-seckill', 'group' => '营销组件', 'default_props' => []],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function fallbackEntries(): array
    {
        return [
            ['plugin' => 'points_product', 'slot' => 'user.functions', 'label' => '积分商城', 'uniapp' => '/modules/marketing/pages/points-mall', 'pc' => '/marketing/points-mall'],
            ['plugin' => 'distribution', 'slot' => 'user.functions', 'label' => '分销中心', 'uniapp' => '/modules/distribution/pages/index', 'pc' => '/user/distribution'],
            ['plugin' => 'sign', 'slot' => 'user.functions', 'label' => '签到', 'uniapp' => '/modules/user/pages/sign', 'pc' => '/user/sign'],
            ['plugin' => 'coupon', 'slot' => 'user.functions', 'label' => '优惠券', 'uniapp' => '/modules/marketing/pages/coupon', 'pc' => '/marketing/coupon'],
        ];
    }

    /** @return list<array{plugin: string, path: string}> */
    private function fallbackPublicPaths(): array
    {
        return [
            ['plugin' => 'coupon', 'path' => '/modules/marketing/pages/coupon'],
            ['plugin' => 'flash_sale', 'path' => '/modules/marketing/pages/flash-sale'],
            ['plugin' => 'group_buy', 'path' => '/modules/marketing/pages/group-buy'],
            ['plugin' => 'points_product', 'path' => '/modules/marketing/pages/points-mall'],
            ['plugin' => 'lottery', 'path' => '/modules/marketing/pages/lottery'],
            ['plugin' => 'lottery', 'path' => '/modules/marketing/pages/lottery-detail'],
        ];
    }
}
