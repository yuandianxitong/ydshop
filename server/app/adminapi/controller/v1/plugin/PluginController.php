<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\plugin;

use app\service\plugin\PluginService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;

class PluginController extends Controller
{
    protected PluginService $pluginService;

    #[Permission('plugin.installed')]
    public function list(): Response
    {
        return $this->success('ok', $this->pluginService->list());
    }

    /**
     * Uninstall a plugin.
     *
     * Default retains business tables. Pass purge=1 (query or body) to also
     * execute database/uninstall.sql (destructive — admin UI must confirm twice).
     */
    #[Permission('plugin.installed')]
    public function uninstall(string $code): Response
    {
        $purgeRaw = $this->request->param('purge', false);
        $purge    = filter_var($purgeRaw, FILTER_VALIDATE_BOOLEAN);
        $this->pluginService->uninstall($code, $purge);
        return $this->success($purge ? '卸载并清除数据成功' : '卸载成功');
    }

    #[Permission('plugin.installed')]
    public function upgrade(string $code): Response
    {
        $this->pluginService->upgrade($code);
        return $this->success('升级成功');
    }

    #[Permission('plugin.installed')]
    public function enable(string $code): Response
    {
        $this->pluginService->enable($code);
        return $this->success('已启用');
    }

    #[Permission('plugin.installed')]
    public function disable(string $code): Response
    {
        $this->pluginService->disable($code);
        return $this->success('已禁用');
    }

    #[Permission('plugin.installed')]
    public function logs(): Response
    {
        $code = $this->request->param('code');
        $page = (int) $this->request->param('page_no', 1);
        $size = (int) $this->request->param('page_size', 20);
        return $this->success('ok', $this->pluginService->logs($code, $page, $size));
    }
}
