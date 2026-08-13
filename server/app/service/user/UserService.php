<?php
declare(strict_types=1);

namespace app\service\user;

use app\repository\user\UserRepository;
use core\base\Service;
use core\exception\BusinessException;

/**
 * 用户资料 Service —— 档案 / 密码 / 公众号 openid 绑定
 *
 * 登录/注册/微信授权各通道已搬到 AuthService。
 */
class UserService extends Service
{
    protected UserRepository $userRepository;

    /**
     * 检查手机号是否已注册
     */
    public function mobileExists(string $mobile): bool
    {
        return $this->userRepository->findByMobile($mobile) !== null;
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo(int $userId): array
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new BusinessException(lang('business.user_not_found'));
        }
        return $user;
    }

    /**
     * 获取用户档案（含会员等级），用于 C 端 profile 接口
     */
    public function getProfile(int $userId): array
    {
        $profile = $this->userRepository->findProfile($userId);
        if (!$profile) {
            throw new BusinessException(lang('business.user_not_found'));
        }
        return $profile;
    }

    /**
     * 更新用户资料
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new BusinessException(lang('business.user_not_found'));
        }

        $allowFields = ['nickname', 'avatar', 'gender', 'birthday', 'email'];
        $updateData  = array_intersect_key($data, array_flip($allowFields));

        // 空字符串字段需转 null（DATE / 唯一索引列对空串严格）
        foreach (['birthday', 'email'] as $nullableField) {
            if (array_key_exists($nullableField, $updateData) && $updateData[$nullableField] === '') {
                $updateData[$nullableField] = null;
            }
        }

        if (empty($updateData)) {
            throw new BusinessException(lang('business.no_updatable_fields'));
        }

        return $this->userRepository->update($userId, $updateData);
    }

    /**
     * 修改密码
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findModelWithPassword($userId);
        if (!$user) {
            throw new BusinessException(lang('business.user_not_found'));
        }

        if ($user->password && !password_verify($oldPassword, $user->password)) {
            throw new BusinessException(lang('business.user_old_password_error'));
        }

        return $this->userRepository->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * 绑定手机号到当前账号
     *
     * 微信端（H5/小程序自动注册）账号没有手机号，这里作为补绑通道。
     * 手机号被其他账号占用时直接拒绝，不做账号合并。
     */
    public function bindMobile(int $userId, string $mobile): bool
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new BusinessException(lang('business.user_not_found'));
        }

        if (!empty($user['mobile'])) {
            throw new BusinessException($user['mobile'] === $mobile ? '该手机号已是当前账号' : '当前账号已绑定手机号');
        }

        $owner = $this->userRepository->findByMobile($mobile);
        if ($owner) {
            throw new BusinessException('该手机号已绑定其他账号');
        }

        return $this->userRepository->update($userId, ['mobile' => $mobile]);
    }

    /**
     * 绑定公众号 openid 到指定用户
     */
    public function bindOaOpenid(int $userId, string $oaOpenid): bool
    {
        return $this->userRepository->updateOaOpenid($userId, $oaOpenid);
    }

    /**
     * 绑定公众号 openid 和 unionid 到指定用户
     */
    public function bindOaOpenidAndUnionid(int $userId, string $oaOpenid, ?string $unionid): bool
    {
        return $this->userRepository->updateOaOpenidAndUnionid($userId, $oaOpenid, $unionid);
    }
}
