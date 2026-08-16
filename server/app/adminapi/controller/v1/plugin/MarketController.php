<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\plugin;

use app\service\plugin\PluginService;
use core\attribute\Permission;
use core\base\Controller;
use core\marketplace\OfficialAccountSession;
use core\marketplace\OfficialCatalogClient;
use think\Response;

class MarketController extends Controller
{
    protected PluginService $pluginService;

    /**
     * 官方商城组件目录（runtime=shop）。
     */
    #[Permission('plugin.market')]
    public function catalog(): Response
    {
        return $this->success('ok', $this->pluginService->marketCatalog($this->request->get()));
    }

    #[Permission('plugin.market')]
    public function session(): Response
    {
        $info = OfficialAccountSession::publicInfo();
        return $this->success('ok', $info ?? ['connected' => false]);
    }

    #[Permission('plugin.market')]
    public function connect(): Response
    {
        $account = trim((string) $this->request->post('account', ''));
        $password = (string) $this->request->post('password', '');
        if ($account === '' || $password === '') {
            return $this->error('请输入官网账号和密码');
        }
        $login = (new OfficialCatalogClient())->login($account, $password);
        OfficialAccountSession::save($login['token'], $login['user_info']);
        $this->pluginService->syncOfficialEntitlements();
        return $this->success('已连接官网账号', OfficialAccountSession::publicInfo());
    }

    #[Permission('plugin.market')]
    public function disconnect(): Response
    {
        OfficialAccountSession::clear();
        return $this->success('已断开官网账号');
    }

    /**
     * 从官网下载已购组件并安装。
     */
    #[Permission('plugin.market')]
    public function install(): Response
    {
        $code = trim((string) $this->request->post('code', ''));
        $version = trim((string) $this->request->post('version', ''));
        if ($code === '') {
            return $this->error('缺少插件 code');
        }
        $installed = $this->pluginService->installFromOfficial($code, $version !== '' ? $version : null);
        return $this->success('安装成功', ['code' => $installed]);
    }

    /**
     * Upload a plugin zip and install it. Multipart field name: `file`.
     */
    #[Permission('plugin.market')]
    public function upload(): Response
    {
        $file = $this->request->file('file');
        if (!$file || strtolower($file->getOriginalExtension()) !== 'zip') {
            return $this->error('请上传 zip 包');
        }

        $tmpZip = runtime_path() . 'plugin_upload_' . uniqid() . '.zip';
        $file->move(dirname($tmpZip), basename($tmpZip));

        try {
            $code = $this->pluginService->uploadAndInstall($tmpZip);
            return $this->success('安装成功', ['code' => $code]);
        } finally {
            @unlink($tmpZip);
        }
    }
}
