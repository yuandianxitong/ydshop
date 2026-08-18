<?php
declare(strict_types=1);

namespace plugins\coupon\api\controller;

use core\base\Controller;
use plugins\coupon\service\CouponService;
use think\Response;

class CouponController extends Controller
{
    protected CouponService $couponService;

    /**
     * 获取结算页可用优惠券列表
     */
    public function available(): Response
    {
        $userId      = $this->getUserId();
        $orderAmount = (float)$this->request->param('order_amount', 0);
        $spuIdsRaw   = $this->request->param('spu_ids', []);
        $spuIds      = is_array($spuIdsRaw)
            ? $spuIdsRaw
            : array_filter(array_map('intval', explode(',', (string)$spuIdsRaw)));

        $result = $this->couponService->getAvailableCoupons($userId, $orderAmount, $spuIds);
        return $this->success('获取成功', $result);
    }

    /**
     * GET /api/marketing/coupon/receivable
     * 用户中心优惠券页"可领取"列表（不依赖订单上下文）
     */
    public function receivable(): Response
    {
        $userId = $this->getUserId();
        $result = $this->couponService->getReceivableCoupons($userId);
        return $this->success('获取成功', $result);
    }

    /**
     * 领取优惠券
     */
    public function claim(): Response
    {
        $userId   = $this->getUserId();
        $couponId = (int)$this->request->post('coupon_id');
        $result   = $this->couponService->claim($userId, $couponId);
        return $this->success('领取成功', $result);
    }

    /**
     * 我的优惠券列表
     */
    public function my(): Response
    {
        $userId = $this->getUserId();
        $result = $this->couponService->getUserCoupons($userId, [
            'status' => (string)$this->request->get('status', ''),
            'page' => (int)$this->request->get('page', $this->request->get('page_no', 1)),
            'limit' => (int)$this->request->get('limit', $this->request->get('page_size', 20)),
        ]);
        return $this->success('获取成功', $result);
    }
}
