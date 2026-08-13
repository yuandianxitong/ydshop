<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

/**
 * 平台回调事件值对象（已通过验签）
 */
class DeliveryCallbackEvent
{
    public function __construct(
        public readonly string $platformOrderId,
        public readonly string $platformStatus,
        public readonly string $riderName = '',
        public readonly string $riderPhone = '',
        public readonly ?float $riderLat = null,
        public readonly ?float $riderLng = null,
        public readonly array $raw = [],
    ) {}
}
