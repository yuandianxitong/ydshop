<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\help;

use core\base\Controller;
use core\attribute\Permission;
use app\service\help\HelpCategoryService;
use app\adminapi\validate\v1\help\HelpCategoryValidate;
use think\Response;

class HelpCategoryController extends Controller
{
    protected HelpCategoryService $helpCategoryService;

    #[Permission('content.help_category.list')]
    public function list(): Response
    {
        $params = $this->getRequestData([
            'page_no' => 1, 'page_size' => 20,
            'status'  => '', 'keyword'  => '',
        ]);
        return $this->paginate($this->helpCategoryService->getList($params));
    }

    #[Permission('content.help_category.list')]
    public function all(): Response
    {
        return $this->success('success', $this->helpCategoryService->getAllActive());
    }

    #[Permission('content.help_category.list')]
    public function detail(): Response
    {
        $id = (int)$this->request->param('id');
        $row = $this->helpCategoryService->detail($id);
        if (!$row) return $this->error('分类不存在');
        return $this->success('success', $row);
    }

    #[Permission('content.help_category.create')]
    public function create(): Response
    {
        $data = $this->request->only(['name', 'icon', 'sort', 'status']);
        $this->validate($data, HelpCategoryValidate::class, [], false, 'create');
        $result = $this->helpCategoryService->create($data);
        return $this->success('创建成功', $result);
    }

    #[Permission('content.help_category.update')]
    public function update(): Response
    {
        $id = (int)$this->request->param('id');
        $data = $this->request->only(['name', 'icon', 'sort', 'status']);
        $this->validate($data, HelpCategoryValidate::class, [], false, 'update');
        $this->helpCategoryService->update($id, $data);
        return $this->success('更新成功');
    }

    #[Permission('content.help_category.delete')]
    public function delete(): Response
    {
        $id = (int)$this->request->param('id');
        $this->helpCategoryService->delete($id);
        return $this->success('删除成功');
    }
}
