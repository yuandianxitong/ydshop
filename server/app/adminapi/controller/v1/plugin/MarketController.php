<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\plugin;

use app\service\plugin\PluginService;
use core\attribute\Permission;
use core\base\Controller;
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
        $client = new OfficialCatalogClient();
        $data = $client->listShopComponents($this->request->get());
        $list = $data['list'] ?? [];
        foreach ($list as &$row) {
            $code = (string) ($row['code'] ?? '');
            if ($code !== '') {
                $row['buy_url'] = $client->buyUrl($code);
            }
        }
        unset($row);
        $data['list'] = $list;
        $data['site_base'] = $client->siteBase();
        return $this->success('ok', $data);
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
