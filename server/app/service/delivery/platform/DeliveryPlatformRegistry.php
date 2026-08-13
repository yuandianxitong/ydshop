<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

use core\exception\BusinessException;

/**
 * 三方同城配送平台注册表
 */
class DeliveryPlatformRegistry
{
    /** 平台码 => 中文名（全局统一，勿改动键名） */
    public const LABELS = [
        'dada'     => '达达配送',
        'fengniao' => '蜂鸟配送',
        'uupt'     => 'UU跑腿',
        'shansong' => '闪送',
        'sfsc'     => '顺丰同城',
    ];

    public function resolve(string $code): DeliveryPlatformInterface
    {
        return match ($code) {
            'dada'     => app()->make(DadaAdapter::class),
            'fengniao' => app()->make(FengniaoAdapter::class),
            'uupt'     => app()->make(UuptAdapter::class),
            'shansong' => app()->make(ShansongAdapter::class),
            'sfsc'     => app()->make(SfscAdapter::class),
            default    => throw new BusinessException('未知配送平台：' . $code),
        };
    }
}
