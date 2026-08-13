<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

class DeliveryPlatformResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function ok(): self
    {
        return new self(true, null);
    }

    public static function fail(string $reason): self
    {
        return new self(false, $reason);
    }
}
