<?php
declare(strict_types=1);

namespace app\service\delivery\waybill;

interface WaybillProviderInterface
{
    /**
     * 生成单张电子面单
     *
     * $orderData 结构：
     *   order_no:     string,
     *   sender:       { name, phone, province, city, district, detail },
     *   receiver:     { name, phone, province, city, district, detail },
     *   goods_name:   string,
     *   weight:       float (kg, 可为 0),
     *   express_code: string (kdniao 标准 ShipperCode，如 SF/ZTO/YTO)
     */
    public function generate(array $orderData): WaybillResult;
}
