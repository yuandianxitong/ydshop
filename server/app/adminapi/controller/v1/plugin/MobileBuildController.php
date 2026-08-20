<?php
declare(strict_types=1);

namespace app\adminapi\controller\v1\plugin;

use app\service\plugin\MobileBuildService;
use app\service\plugin\WechatMiniprogramUploadService;
use core\attribute\Permission;
use core\base\Controller;
use think\Response;

class MobileBuildController extends Controller
{
    protected MobileBuildService $mobileBuildService;
    protected WechatMiniprogramUploadService $uploadService;

    #[Permission('mobile.build')]
    public function list(): Response
    {
        $page = max(1, (int) $this->request->get('page_no', 1));
        $size = min(100, max(1, (int) $this->request->get('page_size', 20)));
        $code = trim((string) $this->request->get('code', ''));
        $ids = $this->request->get('ids', '');
        if ($ids !== '') {
            $idList = array_values(array_filter(array_map('intval', explode(',', (string) $ids))));
            return $this->success('ok', ['list' => $this->mobileBuildService->findByIds($idList), 'total' => 0]);
        }
        return $this->success('ok', $this->mobileBuildService->list($page, $size, $code !== '' ? $code : null));
    }

    #[Permission('mobile.build')]
    public function create(): Response
    {
        $platform = trim((string) $this->request->post('platform', 'mp-weixin'));
        $row = $this->mobileBuildService->enqueue($platform, 'manual', null, $this->getUserId() ?: null);
        return $this->success('已入队', $row);
    }

    #[Permission('mobile.build')]
    public function channel(): Response
    {
        return $this->success('ok', $this->uploadService->publicConfig());
    }

    #[Permission('mobile.build.upload')]
    public function saveChannel(): Response
    {
        $this->uploadService->saveKey(
            (string) $this->request->post('wechat_appid', ''),
            (string) $this->request->post('wechat_upload_key', ''),
            (string) $this->request->post('wechat_upload_version', '')
        );
        return $this->success('已保存');
    }

    #[Permission('mobile.build.upload')]
    public function clearChannel(): Response
    {
        $this->uploadService->clearKey();
        return $this->success('已清除密钥');
    }

    #[Permission('mobile.build.upload')]
    public function upload(int $id): Response
    {
        return $this->success('已上传开发版', $this->uploadService->upload($id));
    }

    #[Permission('mobile.build')]
    public function cancel(int $id): Response
    {
        $this->mobileBuildService->cancel($id);
        return $this->success('已取消');
    }

    #[Permission('mobile.build')]
    public function delete(int $id): Response
    {
        $this->mobileBuildService->delete($id);
        return $this->success('已删除');
    }
}
