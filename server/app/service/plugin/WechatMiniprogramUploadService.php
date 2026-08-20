<?php
declare(strict_types=1);

namespace app\service\plugin;

use app\model\plugin\MobileBuild;
use app\repository\plugin\MobileBuildRepository;
use app\repository\plugin\MobileChannelConfigRepository;
use core\base\Service;
use core\exception\BusinessException;
use core\plugin\ProcShellExecutor;

class WechatMiniprogramUploadService extends Service
{
    protected MobileBuildRepository $buildRepo;
    protected MobileChannelConfigRepository $configRepo;

    public function saveKey(string $appid, string $privateKey, string $version = ''): void
    {
        $appid = trim($appid);
        $existing = $this->configRepo->singleton() ?? [];
        $keepKey = $privateKey === '' && ($existing['wechat_upload_key'] ?? '') !== '';
        if ($appid === '' || ($privateKey === '' && !$keepKey)) {
            throw new BusinessException('请填写 AppID 与上传密钥', 422);
        }
        $ver = trim($version);
        if ($ver === '') {
            $ver = (string) ($existing['wechat_upload_version'] ?? '1.0.0');
        }
        if (!preg_match('/^\d+\.\d+\.\d+$/', $ver)) {
            throw new BusinessException('版本号格式应为 x.y.z', 422);
        }
        $this->configRepo->upsert([
            'wechat_appid'          => $appid,
            'wechat_upload_key'     => $keepKey ? (string) $existing['wechat_upload_key'] : $this->seal($privateKey),
            'wechat_upload_version' => $ver,
        ]);
    }

    public function clearKey(): void
    {
        $row = $this->configRepo->singleton();
        if ($row) {
            $this->configRepo->update((int) $row['id'], [
                'wechat_upload_key' => '',
            ]);
        }
    }

    public function publicConfig(): array
    {
        $row = $this->configRepo->singleton() ?? [];
        return [
            'wechat_appid'          => (string) ($row['wechat_appid'] ?? ''),
            'has_key'               => ($row['wechat_upload_key'] ?? '') !== '',
            'wechat_upload_version' => (string) ($row['wechat_upload_version'] ?? '1.0.0'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function upload(int $buildId): array
    {
        $build = $this->buildRepo->find($buildId);
        if (!$build) {
            throw new BusinessException('构建不存在', 404);
        }
        if ((string) $build['platform'] !== MobileBuild::PLATFORM_MP_WEIXIN) {
            throw new BusinessException('仅小程序构建可上传', 422);
        }
        $status = (int) $build['status'];
        if (!in_array($status, [MobileBuild::STATUS_SUCCESS, MobileBuild::STATUS_UPLOADED, MobileBuild::STATUS_FAILED], true)) {
            throw new BusinessException('当前状态不可上传', 422);
        }
        $artifact = (string) ($build['artifact_path'] ?? '');
        if ($artifact === '' || !is_dir($artifact)) {
            throw new BusinessException('构建产物不存在', 422);
        }
        $cfg = $this->configRepo->singleton();
        $appid = trim((string) ($cfg['wechat_appid'] ?? ''));
        $key = $this->unseal((string) ($cfg['wechat_upload_key'] ?? ''));
        if ($appid === '' || $key === '') {
            throw new BusinessException('请先配置小程序 AppID 与上传密钥', 422);
        }
        $version = (string) ($cfg['wechat_upload_version'] ?? '1.0.0');
        $keyFile = rtrim((string) runtime_path(), '/\\') . '/mp_ci_' . uniqid('', true) . '.key';
        file_put_contents($keyFile, $key);
        try {
            $ci = $this->ciBinary();
            $cmd = implode(' ', [
                $ci,
                'upload',
                '--project-path', escapeshellarg($artifact),
                '--appid', escapeshellarg($appid),
                '--private-key-path', escapeshellarg($keyFile),
                '--uv', escapeshellarg($version),
                '--ud', escapeshellarg('Shop 客户端发布'),
            ]);
            $exec = new ProcShellExecutor();
            $result = $exec->exec($cmd, $artifact, 600);
            $out = trim($result['stdout'] . "\n" . $result['stderr']);
            if ($result['exitCode'] !== 0 || preg_match('/^\d+\.\d+\.\d+$/', $out)) {
                throw new BusinessException('miniprogram-ci 上传失败：' . $out, 500);
            }
            $this->buildRepo->markUploaded($buildId, $out);
            $this->configRepo->upsert([
                'wechat_appid'          => $appid,
                'wechat_upload_key'     => (string) $cfg['wechat_upload_key'],
                'wechat_upload_version' => $this->nextVersion($version),
            ]);
            return ['ok' => true, 'output' => $out, 'version' => $version];
        } finally {
            @unlink($keyFile);
        }
    }

    private function ciBinary(): string
    {
        $exec = new ProcShellExecutor();
        $which = $exec->exec('command -v miniprogram-ci', getcwd() ?: '/');
        if ($which['exitCode'] === 0 && trim($which['stdout']) !== '') {
            return trim($which['stdout']);
        }
        return 'npx --yes miniprogram-ci';
    }

    private function nextVersion(string $current): string
    {
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $current, $m)) {
            return '1.0.1';
        }
        return $m[1] . '.' . $m[2] . '.' . ((int) $m[3] + 1);
    }

    private function seal(string $plain): string
    {
        return base64_encode($plain);
    }

    private function unseal(string $sealed): string
    {
        $raw = base64_decode($sealed, true);
        return $raw === false ? '' : $raw;
    }
}
