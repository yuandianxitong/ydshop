<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\plugin;

use app\service\plugin\PluginBuildService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;

class PluginBuildController extends Controller
{
    protected PluginBuildService $pluginBuildService;

    #[Permission('plugin.build')]
    public function list(): Response
    {
        $page = max(1, (int) $this->request->get('page_no', 1));
        $size = min(100, max(1, (int) $this->request->get('page_size', 20)));
        $code = trim((string) $this->request->get('code', ''));
        $ids = $this->request->get('ids', '');
        if ($ids !== '') {
            $idList = array_values(array_filter(array_map('intval', explode(',', (string) $ids))));
            return $this->success('ok', ['list' => $this->pluginBuildService->findByIds($idList), 'total' => 0]);
        }
        return $this->success('ok', $this->pluginBuildService->list($page, $size, $code !== '' ? $code : null));
    }

    #[Permission('plugin.build.rebuild')]
    public function rebuild(): Response
    {
        $target = trim((string) $this->request->post('target', 'admin'));
        $row = $this->pluginBuildService->rebuild($target, $this->getUserId() ?: null);
        return $this->success('已入队', $row);
    }
}
