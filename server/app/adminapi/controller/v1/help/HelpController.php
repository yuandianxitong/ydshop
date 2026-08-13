<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\help;

use core\base\Controller;
use core\attribute\Permission;
use app\service\help\HelpService;
use app\adminapi\validate\v1\help\HelpValidate;
use think\Response;

class HelpController extends Controller
{
    protected HelpService $helpService;

    #[Permission('content.help.list')]
    public function list(): Response
    {
        $params = $this->getRequestData([
            'page_no'     => 1, 'page_size' => 20,
            'category_id' => '', 'status' => '', 'keyword' => '',
        ]);
        return $this->paginate($this->helpService->getList($params));
    }

    #[Permission('content.help.list')]
    public function detail(): Response
    {
        $id = (int)$this->request->param('id');
        $row = $this->helpService->detail($id);
        if (!$row) return $this->error('帮助不存在');
        return $this->success('success', $row);
    }

    #[Permission('content.help.create')]
    public function create(): Response
    {
        $data = $this->request->only(['category_id', 'title', 'summary', 'content', 'status']);
        $this->validate($data, HelpValidate::class, [], false, 'create');
        $data['admin_id'] = $this->getUserId();
        $result = $this->helpService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('content.help.update')]
    public function update(): Response
    {
        $id = (int)$this->request->param('id');
        $data = $this->request->only(['category_id', 'title', 'summary', 'content', 'status']);
        $this->validate($data, HelpValidate::class, [], false, 'update');
        $this->helpService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('content.help.delete')]
    public function delete(): Response
    {
        $id = (int)$this->request->param('id');
        $this->helpService->delete($id);
        return $this->success('删除成功');
    }

    #[Permission('content.help.update')]
    public function batchStatus(): Response
    {
        $ids = $this->request->post('ids', []);
        $status = (int)$this->request->post('status');
        $count = $this->helpService->batchStatus(is_array($ids) ? $ids : [], $status);
        return $this->success('已处理 ' . $count . ' 条');
    }
}
