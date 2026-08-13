<?php
declare(strict_types=1);

namespace app\service\member;

use app\repository\message\MessageTemplateRepository;
use app\repository\user\UserRepository;
use app\service\message\MessageService;
use app\service\user\UserOperationLogService;
use core\base\Service;
use core\exception\BusinessException;

/**
 * 会员详情发短信
 *
 * 复用 MessageService::send（走短信通道），同时把动作写入 user_operation_logs
 * 让会员详情「操作日志 Tab」能看到。
 */
class MemberSmsService extends Service
{
    protected MessageService              $messageService;
    protected MessageTemplateRepository   $templateRepository;
    protected UserRepository              $userRepository;
    protected UserOperationLogService     $opLog;

    /**
     * 可发送的短信模板列表（启用 + 配置了 sms_template_id）
     */
    public function getTemplates(): array
    {
        $res  = $this->templateRepository->getSearchList(['status' => 1], 1, 100);
        $list = $res['list'] ?? [];
        return array_values(array_filter($list, fn ($t) => !empty($t['sms_enabled']) && !empty($t['sms_template_id'])));
    }

    public function send(int $userId, string $code, array $variables): array
    {
        $user = $this->userRepository->findModel($userId);
        if (!$user) {
            throw new BusinessException('用户不存在');
        }
        if (empty($user->mobile)) {
            throw new BusinessException('该用户未绑定手机号');
        }

        $result = $this->messageService->send($code, ['phone' => (string)$user->mobile], $variables);
        if (empty($result['sms']['success'])) {
            $err = $result['sms']['error'] ?? '短信发送失败';
            throw new BusinessException($err);
        }

        $this->opLog->recordService(
            $userId,
            '运营短信',
            sprintf('使用模板「%s」发送短信至 %s', $code, $this->maskMobile((string)$user->mobile)),
            ['template' => $code, 'variables' => $variables]
        );

        return $result;
    }

    private function maskMobile(string $mobile): string
    {
        return preg_replace('/^(\d{3})\d{4}(\d{4})$/', '$1****$2', $mobile);
    }
}
