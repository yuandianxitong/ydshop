<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\delivery;

use app\adminapi\validate\v1\delivery\WaybillTemplateValidate;
use app\service\delivery\WaybillTemplateService;
use core\attribute\Permission;
use core\attribute\PermissionSkip;
use core\base\Controller;
use think\Response;

class WaybillTemplateController extends Controller
{
    protected WaybillTemplateService $waybillTemplateService;

    #[PermissionSkip]
    public function catalog(): Response
    {
        return $this->success('获取成功', $this->waybillTemplateService->getCatalog());
    }

    #[PermissionSkip]
    public function options(): Response
    {
        return $this->success('获取成功', $this->waybillTemplateService->getOptions());
    }

    #[Permission('delivery')]
    public function index(): Response
    {
        return $this->paginate($this->waybillTemplateService->getList($this->getRequestData()));
    }

    #[Permission('delivery')]
    public function show(): Response
    {
        $id = (int)$this->request->param('id');
        return $this->success('获取成功', $this->waybillTemplateService->getDetail($id));
    }

    #[Permission('delivery')]
    public function store(): Response
    {
        $data = $this->request->post();
        $this->validate($data, WaybillTemplateValidate::class, [], false, 'create');
        $row = $this->waybillTemplateService->create($data);
        return $this->success('创建成功', $row);
    }

    #[Permission('delivery')]
    public function update(): Response
    {
        $id = (int)$this->request->param('id');
        $data = $this->request->put();
        $this->validate($data, WaybillTemplateValidate::class, [], false, 'update');
        $this->waybillTemplateService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('delivery')]
    public function status(): Response
    {
        $id = (int)$this->request->param('id');
        $data = $this->request->put();
        $this->validate($data, WaybillTemplateValidate::class, [], false, 'status');
        $this->waybillTemplateService->updateStatus($id, (int)($data['status'] ?? 0));
        return $this->success('状态已更新');
    }

    #[Permission('delivery')]
    public function delete(): Response
    {
        $id = (int)$this->request->param('id');
        $this->waybillTemplateService->delete($id);
        return $this->success('删除成功');
    }
}
