<?php
declare(strict_types=1);

namespace app\service\delivery;

use app\repository\delivery\ExpressCompanyRepository;
use core\base\Service;
use core\exception\BusinessException;
use think\facade\Log;

class ExpressCompanyService extends Service
{
    protected ExpressCompanyRepository $expressCompanyRepo;

    public function getList(array $params): array
    {
        $where = [];
        if (!empty($params['keyword'])) {
            $where[] = ['name', 'like', '%' . $params['keyword'] . '%'];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', (int)$params['status']];
        }
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);
        return $this->expressCompanyRepo->getPageList($where, $page, $limit, 'sort asc, id asc');
    }

    public function getOptions(): array
    {
        return $this->expressCompanyRepo->getAll([['status', '=', 1]], 'sort asc, id asc');
    }

    public function getDetail(int $id): array
    {
        $result = $this->expressCompanyRepo->find($id);
        if (!$result) {
            throw new BusinessException('物流公司不存在');
        }
        return $result;
    }

    public function create(array $data): array
    {
        return $this->expressCompanyRepo->create([
            'name'   => $data['name'] ?? '',
            'code'   => $data['code'] ?? '',
            'logo'   => $data['logo'] ?? '',
            'sort'   => $data['sort'] ?? 0,
            'status' => $data['status'] ?? 1,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->expressCompanyRepo->find($id);
        if (!$record) {
            throw new BusinessException('物流公司不存在');
        }
        $updateData = array_filter([
            'name'   => $data['name'] ?? null,
            'code'   => $data['code'] ?? null,
            'logo'   => $data['logo'] ?? null,
            'sort'   => $data['sort'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn($v) => $v !== null);
        return $this->expressCompanyRepo->update($id, $updateData);
    }

    public function updateStatus(int $id, int $status): bool
    {
        $record = $this->expressCompanyRepo->find($id);
        if (!$record) {
            throw new BusinessException('物流公司不存在');
        }
        return $this->expressCompanyRepo->update($id, ['status' => $status]);
    }

    public function delete(int $id): bool
    {
        $record = $this->expressCompanyRepo->find($id);
        if (!$record) {
            throw new BusinessException('物流公司不存在');
        }
        return $this->expressCompanyRepo->delete($id);
    }

    public function batchDelete(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->delete((int)$id);
                $count++;
            } catch (\Exception $e) {
                Log::warning('批量删除物流公司失败', ['id' => $id, 'message' => $e->getMessage()]);
            }
        }
        return $count;
    }
}
