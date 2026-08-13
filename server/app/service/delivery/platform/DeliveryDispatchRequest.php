<?php
declare(strict_types=1);

namespace app\service\delivery\platform;

/**
 * 发单请求值对象
 *
 * sender / receiver 结构：
 *   { name, phone, province, city, district, address, lat, lng }
 */
class DeliveryDispatchRequest
{
    /**
     * @param array{name:string,phone:string,province:string,city:string,district:string,address:string,lat:float,lng:float} $sender
     * @param array{name:string,phone:string,province:string,city:string,district:string,address:string,lat:float,lng:float} $receiver
     */
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNo,
        public readonly string $cargoName,
        public readonly float $cargoPrice,
        public readonly float $cargoWeight,
        public readonly float $tips,
        public readonly array $sender,
        public readonly array $receiver,
        public readonly string $remark,
        public readonly string $notifyUrl,
    ) {}
}
