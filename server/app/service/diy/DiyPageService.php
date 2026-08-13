<?php
declare(strict_types=1);

namespace app\service\diy;

use core\base\Service;
use core\plugin\PluginManager;
use app\repository\diy\DiyPageRepository;
use app\repository\goods\GoodsCategoryRepository;
use app\repository\goods\GoodsSkuRepository;
use app\repository\goods\GoodsSpuRepository;
use app\service\marketing\MarketingAdService;
use think\facade\Db;

class DiyPageService extends Service
{
    protected DiyPageRepository $repo;
    protected GoodsSpuRepository $goodsSpuRepository;
    protected GoodsSkuRepository $goodsSkuRepository;
    protected GoodsCategoryRepository $goodsCategoryRepository;
    protected \app\repository\diy\DiyTemplateRepository $themeRepository;
    protected \app\service\diy\DiyPageVersionService $versionService;
    protected MarketingAdService $marketingAdService;

    public function getPageList(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        return $this->repo->getPageList($params, $page, $limit);
    }

    public function getPageDetail(int $id): ?array
    {
        return $this->repo->find($id);
    }

    public function createPage(array $data, ?int $templateId = null): array
    {
        if ($templateId) {
            $template = $this->themeRepository->find($templateId);
            if ($template) {
                $data['components'] = $template['components'] ?? [];
            }
        }
        $data['components'] = $data['components'] ?? [];
        $data['page_settings'] = $data['page_settings'] ?? null;

        $isFirst = $this->repo->countByTypePlatform($data['page_type'], $data['platform']) === 0;
        $data['is_default'] = ($data['page_type'] !== 'custom' && $isFirst) ? 1 : 0;

        return $this->repo->create($data);
    }

    public function updatePage(int $id, array $data): bool
    {
        $page = $this->repo->find($id);
        if (!$page) {
            $this->throwBusinessException('页面不存在');
        }
        unset($data['is_default']);
        return $this->repo->update($id, $data);
    }

    public function publishPage(int $id, bool $publish, ?string $note = null, ?int $adminId = null): bool
    {
        $page = $this->repo->find($id);
        if (!$page) {
            $this->throwBusinessException('页面不存在');
        }
        Db::startTrans();
        try {
            $this->repo->update($id, ['is_published' => $publish ? 1 : 0]);
            if ($publish) {
                $latest = $this->repo->find($id);
                $this->versionService->snapshot($latest, $note, $adminId);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        return true;
    }

    public function deletePage(int $id): bool
    {
        $page = $this->repo->find($id);
        if (!$page) {
            $this->throwBusinessException('页面不存在');
        }
        if (!empty($page['is_default'])) {
            $this->throwBusinessException('当前生效首页不可删除,请先把另一个页面设为默认');
        }
        return $this->repo->delete($id);
    }

    public function setDefault(int $id): bool
    {
        $page = $this->repo->find($id);
        if (!$page) {
            $this->throwBusinessException('页面不存在');
        }
        if ($page['page_type'] === 'custom') {
            $this->throwBusinessException('专题页面不支持设为默认');
        }
        Db::startTrans();
        try {
            $this->repo->clearDefault($page['page_type'], $page['platform']);
            $this->repo->update($id, ['is_default' => 1]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        $this->trigger('diy.page.default_changed', [
            'page_id'   => $id,
            'page_type' => $page['page_type'],
            'platform'  => $page['platform'],
        ]);
        return true;
    }

    public function getPublishedPage(string $pageType, string $platform): ?array
    {
        $page = $this->repo->getPublishedPage($pageType, $platform);
        if (!$page) {
            return null;
        }
        $page['components'] = $this->hydrateComponents($page['components'] ?? []);
        return $page;
    }

    public function getCustomPage(int $id): ?array
    {
        $page = $this->repo->find($id);
        if (!$page || $page['is_published'] != 1 || $page['status'] != 1) {
            return null;
        }
        $page['components'] = $this->hydrateComponents($page['components'] ?? []);
        return $page;
    }

    public function hydrateComponents(array $components): array
    {
        foreach ($components as &$comp) {
            $props = $comp['props'] ?? [];
            switch ($comp['type'] ?? '') {
                case 'goods-grid':
                    $comp['props']['goods_list'] = $this->getGoodsForComponent($props);
                    break;
                case 'category-nav':
                    $comp['props']['category_list'] = $this->getCategoriesForComponent($props);
                    break;
                case 'article-list':
                    $comp['props']['article_list'] = $this->getArticlesForComponent($props);
                    break;
                case 'seckill':
                    $comp['props']['seckill_data'] = $this->getSeckillForComponent($props);
                    break;
                case 'coupon-list':
                    $comp['props']['coupon_list'] = $this->getCouponsForComponent($props);
                    break;
                case 'ad-slot':
                    $code = $props['position_code'] ?? null;
                    if ($code) {
                        try {
                            $comp['props']['ad_list'] = $this->marketingAdService->getPublicByPositionCode($code);
                        } catch (\Throwable $e) {
                            $comp['props']['ad_list'] = ['position' => null, 'ads' => []];
                        }
                    } else {
                        $comp['props']['ad_list'] = ['position' => null, 'ads' => []];
                    }
                    break;
            }
        }
        return $components;
    }

    private function getGoodsForComponent(array $props): array
    {
        $source = $props['source'] ?? 'manual';
        $limit = (int) ($props['limit'] ?? 8);

        if ($source === 'manual' && !empty($props['goods_ids'])) {
            return $this->goodsSpuRepository->getByIds($props['goods_ids'], $limit);
        }
        if ($source === 'category' && !empty($props['category_id'])) {
            return $this->goodsSpuRepository->getListByCategory((int) $props['category_id'], $limit);
        }
        if ($source === 'tag' && !empty($props['tag'])) {
            return $this->goodsSpuRepository->getListByTag($props['tag'], $limit);
        }
        return [];
    }

    private function getCategoriesForComponent(array $props): array
    {
        // 编辑器配置了自定义项目时直接返回
        if (!empty($props['items'])) {
            return $props['items'];
        }
        if (!empty($props['category_ids'])) {
            return $this->goodsCategoryRepository->findActiveByIds((array)$props['category_ids']);
        }
        $limit = (int) (($props['rows'] ?? 2) * ($props['columns'] ?? 5));
        return $this->goodsCategoryRepository->findActiveTopLevel($limit);
    }

    private function getArticlesForComponent(array $props): array
    {
        if (!PluginManager::isInstalled('article')
            || !class_exists('\plugins\article\repository\ArticleRepository')
        ) {
            return [];
        }
        $limit      = (int) ($props['limit'] ?? 5);
        $categoryId = (int) ($props['category_id'] ?? 0);
        $useCategory = !empty($props['source']) && $props['source'] === 'category' && $categoryId > 0;

        return app(\plugins\article\repository\ArticleRepository::class)
            ->getDiyComponentList($limit, $useCategory ? $categoryId : 0);
    }

    /**
     * 秒杀组件：拉指定活动或当前进行中的第一个活动，扁平化 items → goods，
     * 派生「{H}点场」场次 tag。无可用活动返回 null（前端走空态）。
     */
    private function getSeckillForComponent(array $props): ?array
    {
        if (!PluginManager::isInstalled('flash_sale')
            || !class_exists('\plugins\flash_sale\service\FlashSaleService')
        ) {
            return null;
        }
        /** @var \plugins\flash_sale\service\FlashSaleService $flashSaleService */
        $flashSaleService = app(\plugins\flash_sale\service\FlashSaleService::class);

        $limit      = (int) ($props['limit'] ?? 4);
        $activityId = (int) ($props['activity_id'] ?? 0);

        $sale = null;
        if ($activityId > 0) {
            $now = date('Y-m-d H:i:s');
            try {
                $candidate = $flashSaleService->getDetail($activityId);
            } catch (\Throwable) {
                $candidate = null;
            }
            if ($candidate
                && (int)($candidate['status'] ?? 0) === 1
                && ($candidate['start_at'] ?? '') <= $now
                && ($candidate['end_at'] ?? '') >= $now
            ) {
                // getDetail 没有 enrich SKU，手动补一次以保持商品字段齐整
                $sale = $this->enrichSaleItemsWithSku($candidate);
            }
        } else {
            $active = $flashSaleService->getActiveFlashSales();
            $sale   = $active[0] ?? null;
        }

        if (!$sale) {
            return null;
        }

        $goods = [];
        foreach (($sale['items'] ?? []) as $item) {
            $sku = $item['sku'] ?? null;
            if (!$sku) {
                continue;
            }
            $spu     = $sku['spu'] ?? [];
            $cover   = $sku['image'] ?? '';
            if (!$cover && !empty($spu['images'])) {
                $imgs  = is_array($spu['images']) ? $spu['images'] : [];
                $cover = $imgs[0] ?? '';
            }
            $goods[] = [
                'item_id'        => (int) $item['id'],
                'sku_id'         => (int) $item['sku_id'],
                'spu_id'         => (int) ($sku['spu_id'] ?? 0),
                'name'           => $spu['name'] ?? '',
                'cover'          => $cover,
                'flash_price'    => (float) ($item['flash_price'] ?? 0),
                'original_price' => (float) ($sku['price'] ?? 0),
                'stock'          => (int) ($item['flash_stock'] ?? 0),
                'sold_count'     => (int) ($item['sold_count'] ?? 0),
            ];
            if (count($goods) >= $limit) {
                break;
            }
        }

        return [
            'id'            => (int) $sale['id'],
            'name'          => $sale['name'] ?? '',
            'start_at'      => $sale['start_at'] ?? '',
            'end_at'        => $sale['end_at'] ?? '',
            'session_label' => $this->deriveSessionLabel($sale['start_at'] ?? ''),
            'goods'         => $goods,
        ];
    }

    /**
     * 优惠券组件：返回归一化的可领取优惠券列表。
     * - 显式 coupon_ids 时按 id 筛选，否则取当前所有可领取
     * - amount_text / threshold_text 在后端按 type 归一化，前端不分支
     * - has_claimed 这里不计算（home 页公开路由没有稳定 userId 上下文，
     *   领取按钮点击时再由 claim API 拦截已领状态）
     */
    private function getCouponsForComponent(array $props): array
    {
        if (!PluginManager::isInstalled('coupon')
            || !class_exists('\plugins\coupon\service\CouponService')
        ) {
            return [];
        }

        $limit     = (int) ($props['limit'] ?? 8);
        $couponIds = (array) ($props['coupon_ids'] ?? []);

        $rows = app(\plugins\coupon\service\CouponService::class)->getReceivableCoupons(0);
        if (!empty($couponIds)) {
            $couponIds = array_map('intval', $couponIds);
            $byId      = array_column($rows, null, 'id');
            $rows      = [];
            foreach ($couponIds as $cid) {
                if (isset($byId[$cid])) {
                    $rows[] = $byId[$cid];
                }
            }
        }

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'             => (int) $r['id'],
                'name'           => (string) ($r['name'] ?? ''),
                'type'           => (string) ($r['type'] ?? ''),
                'value'          => (float) ($r['value'] ?? 0),
                'min_amount'     => (float) ($r['min_amount'] ?? 0),
                'amount_text'    => $this->formatCouponAmount($r),
                'threshold_text' => $this->formatCouponThreshold($r),
                'start_at'       => (string) ($r['start_at'] ?? ''),
                'end_at'         => (string) ($r['end_at'] ?? ''),
            ];
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    private function formatCouponAmount(array $coupon): string
    {
        $type  = (string) ($coupon['type'] ?? '');
        $value = (float) ($coupon['value'] ?? 0);
        if ($type === 'percent') {
            // value 存 0.8 → 显示 "8折"；用 fmod 去除多余 0
            $tenths = $value * 10;
            $str    = fmod($tenths, 1) === 0.0 ? (string) (int) $tenths : (string) $tenths;
            return $str . '折';
        }
        // fixed / no_threshold 都按整数减
        return '¥' . (fmod($value, 1) === 0.0 ? (string) (int) $value : (string) $value);
    }

    private function formatCouponThreshold(array $coupon): string
    {
        $type = (string) ($coupon['type'] ?? '');
        $min  = (float) ($coupon['min_amount'] ?? 0);
        if ($type === 'no_threshold' || $min <= 0) {
            return '无门槛';
        }
        return '满' . (fmod($min, 1) === 0.0 ? (string) (int) $min : (string) $min) . '可用';
    }

    private function deriveSessionLabel(string $startAt): string
    {
        if (!$startAt) {
            return '';
        }
        $ts = strtotime($startAt);
        return $ts ? (int) date('G', $ts) . '点场' : '';
    }

    /**
     * getActiveFlashSales 自带 SKU enrich，但 getDetail (指定 activity_id 路径) 不带；
     * 这里复制 getActiveFlashSales 的批量加载逻辑，避免 N+1。
     */
    private function enrichSaleItemsWithSku(array $sale): array
    {
        if (empty($sale['items'])) {
            return $sale;
        }
        $skuIds = array_unique(array_filter(array_map(
            fn($it) => (int) ($it['sku_id'] ?? 0),
            $sale['items']
        )));
        if (!$skuIds) {
            return $sale;
        }
        $skuMap = $this->goodsSkuRepository->findWithSpuByIds($skuIds);
        foreach ($sale['items'] as &$item) {
            $item['sku'] = $skuMap[(int) ($item['sku_id'] ?? 0)] ?? null;
        }
        unset($item);
        return $sale;
    }
}
