<?php
declare(strict_types=1);

namespace app\service\delivery\waybill;

class WaybillResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $waybillNo = null,
        public readonly ?string $printTemplateHtml = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function ok(string $waybillNo, string $html): self
    {
        return new self(true, $waybillNo, $html, null);
    }

    public static function fail(string $reason): self
    {
        return new self(false, null, null, $reason);
    }
}
