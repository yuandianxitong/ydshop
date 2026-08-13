<?php
declare(strict_types=1);

namespace plugins\full_discount\controller\admin;

use core\base\Controller;
use plugins\full_discount\service\FullDiscountService;
use think\Response;

/**
 * 满减活动管理 Controller（Admin）
 */
class FullDiscountController extends Controller
{
    protected FullDiscountService $fullDiscountService;

    /**
     * 活动列表（分页）
     */
    public function index(): Response
    {
        $params = $this->getRequestData();
        $result = $this->fullDiscountService->getList($params);
        return $this->paginate($result);
    }

    /**
     * 活动详情
     */
    public function show(): Response
    {
        $id     = (int)$this->request->param('id');
        $result = $this->fullDiscountService->getDetail($id);
        return $this->success('获取成功', $result);
    }

    /**
     * 创建活动
     */
    public function store(): Response
    {
        $data   = $this->request->post();
        $result = $this->fullDiscountService->create($data);
        return $this->success('创建成功', $result);
    }

    /**
     * 更新活动
     */
    public function update(): Response
    {
        $id     = (int)$this->request->param('id');
        $data   = $this->request->post();
        $result = $this->fullDiscountService->update($id, $data);
        return $this->success('更新成功', $result);
    }

    /**
     * 删除活动（软删除）
     */
    public function delete(): Response
    {
        $id = (int)$this->request->param('id');
        $this->fullDiscountService->delete($id);
        return $this->success('删除成功');
    }

    /**
     * 切换状态（启用/禁用）
     */
    public function status(): Response
    {
        $id     = (int)$this->request->param('id');
        $status = (int)$this->request->post('status', 1);
        $this->fullDiscountService->updateStatus($id, $status);
        return $this->success('操作成功');
    }
}
