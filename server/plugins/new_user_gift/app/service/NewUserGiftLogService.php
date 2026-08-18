<?php
declare(strict_types=1);

namespace plugins\new_user_gift\service;

use plugins\new_user_gift\repository\NewUserGiftLogRepository;
use core\base\Service;

class NewUserGiftLogService extends Service
{
    protected NewUserGiftLogRepository $logRepository;

    public function getList(array $filters, int $page, int $limit): array
    {
        return $this->logRepository->getPageList($filters, $page, $limit);
    }

    public function getStats(): array
    {
        return $this->logRepository->getStats();
    }
}
