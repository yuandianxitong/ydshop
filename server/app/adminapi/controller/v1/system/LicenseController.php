<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\system;

use core\attribute\Permission;
use core\base\Controller;
use core\license\LicenseClient;
use core\license\LicenseGuard;
use core\license\LicenseState;
use think\Response;

/**
 * 产品授权管理（对接官网 Site）
 */
class LicenseController extends Controller
{
    #[Permission('system.license.list')]
    public function status(): Response
    {
        $eval = LicenseGuard::status();
        return $this->success(lang('messages.get_success'), [
            'status'       => $eval['status'],
            'pro_enabled'  => $eval['pro_enabled'],
            'message'      => $eval['message'],
            'product_slug' => config('license.product_slug'),
            'site_base_url'=> config('license.site_base_url'),
            'state'        => $this->maskState($eval['state'] ?? []),
        ]);
    }

    #[Permission('system.license.activate')]
    public function activate(): Response
    {
        $licenseKey = trim((string) $this->request->post('license_key', ''));
        $domain = trim((string) $this->request->post('domain', ''));
        if ($licenseKey === '') {
            return $this->error('请填写授权码');
        }

        try {
            $client = new LicenseClient();
            $data = $client->activate($licenseKey, $domain !== '' ? $domain : null);
            return $this->success('激活成功', [
                'license' => $data['license'] ?? [],
                'status'  => LicenseGuard::status(),
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    #[Permission('system.license.activate')]
    public function heartbeat(): Response
    {
        $state = LicenseState::load();
        $licenseKey = (string) ($state['license_key'] ?? '');
        if ($licenseKey === '') {
            return $this->error('尚未激活授权');
        }

        try {
            $client = new LicenseClient();
            $data = $client->heartbeat($licenseKey, (string) ($state['domain'] ?? ''));
            return $this->success('ok', [
                'license' => $data['license'] ?? [],
                'status'  => LicenseGuard::status(),
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    #[Permission('system.license.activate')]
    public function clear(): Response
    {
        LicenseState::clear();
        return $this->success('已清除本地授权状态');
    }

    protected function maskState(array $state): array
    {
        if (!empty($state['license_key'])) {
            $key = (string) $state['license_key'];
            $state['license_key_masked'] = strlen($key) > 8
                ? substr($key, 0, 4) . str_repeat('*', max(0, strlen($key) - 8)) . substr($key, -4)
                : '****';
            unset($state['license_key']);
        }
        return $state;
    }
}
