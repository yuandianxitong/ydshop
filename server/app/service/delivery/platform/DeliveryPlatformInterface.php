<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

/**
 * 三方同城配送平台统一契约
 *
 * 平台码全局统一：dada / fengniao / uupt / shansong / sfsc
 * 本地状态机：pending / assigned / picking / picked / delivering / completed / cancelled
 */
interface DeliveryPlatformInterface
{
    /** 平台码 */
    public function code(): string;

    /** 发单（创建平台配送订单） */
    public function createOrder(DeliveryDispatchRequest $request): DeliveryDispatchResult;

    /** 取消平台配送订单 */
    public function cancelOrder(string $platformOrderId, string $reason): DeliveryPlatformResult;

    /** 主动查询平台订单状态 */
    public function queryOrder(string $platformOrderId): DeliveryQueryResult;

    /**
     * 解析并验签平台回调
     *
     * @param array $payload 回调 body（post + json 合并）
     * @param array $headers 请求头（键为小写）
     * @return DeliveryCallbackEvent|null 验签失败返回 null
     */
    public function parseCallback(array $payload, array $headers): ?DeliveryCallbackEvent;

    /**
     * 平台状态码 → 本地状态
     *
     * @return string pending/assigned/picking/picked/delivering/completed/cancelled；未知返回 ''
     */
    public function mapStatus(string $platformStatus): string;

    /**
     * 回调应答报文
     *
     * @return array{body: string, httpCode: int, contentType: string}
     */
    public function ackResponse(bool $ok): array;
}
