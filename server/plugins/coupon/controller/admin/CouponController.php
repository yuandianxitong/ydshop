<?php
declare(strict_types=1);

namespace plugins\coupon\controller\admin;

use core\base\Controller;
use plugins\coupon\service\CouponService;
use think\Response;

class CouponController extends Controller
{
    protected CouponService $couponService;

    /**
     * 优惠券列表
     */
    public function index(): Response
    {
        $params = $this->getRequestData();
        $result = $this->couponService->getList($params);
        return $this->paginate($result);
    }

    /**
     * 创建优惠券
     */
    public function store(): Response
    {
        $data = $this->request->post();
        $result = $this->couponService->create($data);
        return $this->success('创建成功', $result);
    }

    /**
     * 更新优惠券
     */
    public function update(): Response
    {
        $id   = (int)$this->request->param('id');
        $data = $this->request->put();
        $result = $this->couponService->update($id, $data);
        return $this->success('更新成功', $result);
    }

    /**
     * 删除优惠券
     */
    public function delete(): Response
    {
        $id = (int)$this->request->param('id');
        $this->couponService->delete($id);
        return $this->success('删除成功');
    }
}
