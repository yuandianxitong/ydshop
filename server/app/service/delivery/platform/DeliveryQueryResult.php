<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

class DeliveryQueryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $platformStatus = '',
        public readonly string $riderName = '',
        public readonly string $riderPhone = '',
        public readonly ?float $riderLat = null,
        public readonly ?float $riderLng = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function ok(
        string $platformStatus,
        string $riderName = '',
        string $riderPhone = '',
        ?float $riderLat = null,
        ?float $riderLng = null
    ): self {
        return new self(true, $platformStatus, $riderName, $riderPhone, $riderLat, $riderLng, null);
    }

    public static function fail(string $reason): self
    {
        return new self(false, '', '', '', null, null, $reason);
    }
}
