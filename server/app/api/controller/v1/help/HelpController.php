<?php
declare(strict_types=1);

namespace app\api\controller\v1\help;

use core\base\Controller;
use app\service\help\HelpService;
use app\service\help\HelpCategoryService;
use think\Response;
use think\facade\Log;

class HelpController extends Controller
{
    protected HelpService $helpService;
    protected HelpCategoryService $helpCategoryService;

    public function categories(): Response
    {
        try {
            $list = $this->helpCategoryService->getAllActive();
            $list = array_map(fn($c) => [
                'id'   => (int)$c['id'],
                'name' => $c['name'],
                'icon' => $c['icon'] ?? '',
                'sort' => (int)($c['sort'] ?? 0),
            ], $list);
        } catch (\Throwable $e) {
            $list = [];
        }
        return $this->success('success', $list);
    }

    public function list(): Response
    {
        $params = $this->getRequestData([
            'page_no'     => 1, 'page_size'  => 20,
            'category_id' => 0, 'keyword'   => '',
        ]);
        try {
            $result = $this->helpService->getPublicList($params);
        } catch (\Throwable $e) {
            $result = ['list' => [], 'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'last_page' => 0]];
        }
        return $this->success('success', $result);
    }

    public function detail(): Response
    {
        $id = (int)$this->request->param('id');
        try {
            $row = $this->helpService->getPublicDetail($id);
        } catch (\Throwable $e) {
            Log::warning('HelpController.detail error: ' . $e->getMessage(), ['id' => $id]);
            $row = null;
        }
        if (!$row) return $this->error('帮助不存在');
        return $this->success('success', $row);
    }
}
