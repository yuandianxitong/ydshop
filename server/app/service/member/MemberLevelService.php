<?php

declare(strict_types=1);

namespace app\service\member;

use app\repository\member\MemberLevelRepository;
use app\repository\member\MemberGrowthLogRepository;
use app\repository\user\UserRepository;
use core\base\Service;
use core\exception\BusinessException;

class MemberLevelService extends Service
{
    protected MemberLevelRepository $memberLevelRepository;
    protected MemberGrowthLogRepository $memberGrowthLogRepository;
    protected UserRepository $userRepository;

    /**
     * 会员等级分页列表（附带各等级 member_count）
     */
    public function getList(array $params): array
    {
        $page  = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 15);

        // 历史用户 member_level_id 默认为 0，归入默认启用等级后再统计，避免「普通会员」恒为 0
        $this->assignDefaultLevelToUnsetUsers();

        $result = $this->memberLevelRepository->getSearchList($params, $page, $limit);
        $list   = $result['list'] ?? [];
        if ($list === []) {
            return $result;
        }

        $ids    = array_map(static fn ($row) => (int)($row['id'] ?? 0), $list);
        $counts = $this->userRepository->countGroupedByMemberLevelIds($ids);
        foreach ($list as &$row) {
            $row['member_count'] = (int)($counts[(int)($row['id'] ?? 0)] ?? 0);
        }
        unset($row);

        $result['list'] = $list;
        return $result;
    }

    /**
     * 将未分配等级的用户写入默认启用等级（最低 growth_min）
     */
    public function assignDefaultLevelToUnsetUsers(): int
    {
        $default = $this->memberLevelRepository->findDefaultEnabled();
        if (!$default) {
            return 0;
        }

        return $this->userRepository->assignMemberLevelWhereUnset((int)$default['id']);
    }

    /**
     * 获取所有等级（不分页，按 sort 排序）
     */
    public function getAll(): array
    {
        return $this->memberLevelRepository->getAllOrdered();
    }

    /**
     * 获取所有启用的等级（C 端展示用，按成长值升序）
     */
    public function getEnabledList(): array
    {
        return $this->memberLevelRepository->getEnabledList();
    }

    /**
     * 创建会员等级
     */
    public function create(array $data): array
    {
        return $this->memberLevelRepository->create($data);
    }

    /**
     * 更新会员等级
     */
    public function update(int $id, array $data): array
    {
        $existing = $this->memberLevelRepository->find($id);
        if (!$existing) {
            throw new BusinessException('会员等级不存在');
        }

        $this->memberLevelRepository->update($id, $data);
        return $this->memberLevelRepository->find($id) ?? $existing;
    }

    /**
     * 删除会员等级（检查是否有用户在该等级）
     */
    public function delete(int $id): bool
    {
        $existing = $this->memberLevelRepository->find($id);
        if (!$existing) {
            throw new BusinessException('会员等级不存在');
        }

        $userCount = $this->userRepository->countByMemberLevelId($id);
        if ($userCount > 0) {
            throw new BusinessException("该等级下还有 {$userCount} 位会员，不能删除");
        }

        return $this->memberLevelRepository->delete($id);
    }

    /**
     * 增加用户成长值
     */
    public function addGrowthValue(int $userId, int $value, string $source = ''): bool
    {
        if ($value <= 0) {
            return false;
        }

        return $this->runInTransaction(function () use ($userId, $value, $source): bool {
            $user = $this->userRepository->findForUpdate($userId);
            if (!$user) {
                throw new BusinessException('用户不存在');
            }

            if ($source !== '' && $this->memberGrowthLogRepository->existsBySource($userId, $source)) {
                return false;
            }

            $before = (int)($user->growth_value ?? 0);
            $after = $before + $value;
            if (!$this->userRepository->updateGrowthValue($userId, $after)) {
                throw new BusinessException('更新成长值失败');
            }

            $this->memberGrowthLogRepository->create([
                'user_id'       => $userId,
                'value'         => $value,
                'before_growth' => $before,
                'after_growth'  => $after,
                'source'        => $source !== '' ? $source : null,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            return true;
        });
    }

    /**
     * 根据成长值检查并升级用户会员等级
     *
     * 查询所有 status=1 的等级，按 growth_min 降序排列，
     * 找到第一个 growth_min <= user.growth_value 的等级，
     * 若与当前等级不同则更新 member_level_id。
     */
    public function checkAndUpgrade(int $userId): bool
    {
        return $this->recalculateLevel($userId);
    }

    /**
     * 按当前成长值重新计算等级，既允许升级也允许退款冲正后的降级。
     */
    public function recalculateLevel(int $userId): bool
    {
        return $this->runInTransaction(function () use ($userId): bool {
            // 必须与成长值发放/冲正使用同一 users 行锁，避免旧读取最后覆盖新等级。
            $user = $this->userRepository->findForUpdate($userId);
            if (!$user) {
                throw new BusinessException('用户不存在');
            }

            $levels         = $this->memberLevelRepository->getEnabledForUpgrade();
            $matchedLevelId = 0;
            foreach ($levels as $level) {
                if ((int)$level['growth_min'] <= (int)($user->growth_value ?? 0)) {
                    $matchedLevelId = (int)$level['id'];
                    break;
                }
            }

            if ((int)($user->member_level_id ?? 0) !== $matchedLevelId) {
                return $this->userRepository->updateMemberLevel($userId, $matchedLevelId);
            }

            return false;
        });
    }
}
