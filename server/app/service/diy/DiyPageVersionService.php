<?php
declare(strict_types=1);

namespace app\service\diy;

use core\base\Service;
use app\repository\diy\DiyPageVersionRepository;
use app\repository\diy\DiyPageRepository;

class DiyPageVersionService extends Service
{
    protected DiyPageVersionRepository $repo;
    protected DiyPageRepository $pageRepo;

    public function snapshot(array $page, ?string $note, ?int $adminId): array
    {
        $versionNo = $this->repo->getNextVersionNo((int) $page['id']);
        $row = $this->repo->create([
            'page_id'       => (int) $page['id'],
            'version_no'    => $versionNo,
            'title'         => $page['title'] ?? '',
            'components'    => $page['components'] ?? [],
            'page_settings' => $page['page_settings'] ?? null,
            'note'          => $note,
            'created_by'    => $adminId,
        ]);
        $this->repo->deleteOldest((int) $page['id'], 50);
        return $row;
    }

    public function getList(int $pageId): array
    {
        return $this->repo->listByPage($pageId);
    }

    public function getDetail(int $pageId, int $versionId): ?array
    {
        return $this->repo->findInPage($pageId, $versionId);
    }

    public function restore(int $pageId, int $versionId): bool
    {
        $version = $this->repo->findInPage($pageId, $versionId);
        if (!$version) {
            $this->throwBusinessException('版本不存在或不属于该页面');
        }
        return $this->pageRepo->update($pageId, [
            'title'         => $version['title'],
            'components'    => $version['components'],
            'page_settings' => $version['page_settings'],
        ]);
    }
}
