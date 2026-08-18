<?php
declare(strict_types=1);

namespace plugins\full_discount\api\controller;

use core\base\Controller;
use plugins\full_discount\service\FullDiscountService;
use think\Response;

class FullDiscountController extends Controller
{
    protected FullDiscountService $fullDiscountService;

    /**
     * GET /api/marketing/full-discount/goods/:spuId
     * 查询某商品命中的当前进行中满减活动
     */
    public function getRulesForGoods(): Response
    {
        $spuId = (int)$this->request->param('spuId');
        if ($spuId <= 0) {
            return $this->success('获取成功', []);
        }
        try {
            $result = $this->fullDiscountService->getActiveRulesForSpu($spuId);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
