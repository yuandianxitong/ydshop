<?php
declare(strict_types=1);

namespace app\service\diy;

use core\base\Service;
use core\plugin\HookManager;
use app\repository\diy\DiyPageRepository;
use app\repository\goods\GoodsCategoryRepository;
use app\repository\goods\GoodsSpuRepository;
use app\service\marketing\MarketingAdService;
use think\facade\Db;

class DiyPageService extends Service
{
    protected DiyPageRepository $repo;
    protected GoodsSpuRepository $goodsSpuRepository;
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
                default:
                    $comp = HookManager::apply('diy.hydrate', [
                        'type'  => (string) ($comp['type'] ?? ''),
                        'props' => $props,
                    ], $comp);
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
}
