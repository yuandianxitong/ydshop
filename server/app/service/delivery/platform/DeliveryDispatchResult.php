<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

class DeliveryDispatchResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $platformOrderId = null,
        public readonly string $platformStatus = '',
        public readonly ?float $fee = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function ok(string $platformOrderId, string $platformStatus = '', ?float $fee = null): self
    {
        return new self(true, $platformOrderId, $platformStatus, $fee, null);
    }

    public static function fail(string $reason): self
    {
        return new self(false, null, '', null, $reason);
    }
}
