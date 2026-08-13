<?php
declare(strict_types=1);

namespace app\service\diy;

use core\base\Service;
use app\repository\diy\DiyTemplateRepository;

class DiyTemplateService extends Service
{
    protected DiyTemplateRepository $repo;

    public function getList(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 50);
        return $this->repo->getList($params, $page, $limit);
    }

    public function getDetail(int $id): ?array
    {
        return $this->repo->find($id);
    }

    public function create(array $data): array
    {
        $data['is_system'] = 0;
        return $this->repo->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $tpl = $this->repo->find($id);
        if (!$tpl) {
            $this->throwBusinessException('模板不存在');
        }
        if (!empty($tpl['is_system'])) {
            $this->throwBusinessException('系统预设模板不允许修改');
        }
        unset($data['is_system']);
        return $this->repo->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $tpl = $this->repo->find($id);
        if (!$tpl) {
            $this->throwBusinessException('模板不存在');
        }
        if (!empty($tpl['is_system'])) {
            $this->throwBusinessException('系统预设模板不允许删除');
        }
        return $this->repo->delete($id);
    }
}
