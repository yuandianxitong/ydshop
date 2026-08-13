<?php
declare(strict_types=1);

namespace app\api\controller\v1\version;

use core\base\Controller;
use app\service\version\AppVersionService;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'APP 版本', description: 'APP 版本检查更新')]
class VersionController extends Controller
{
    protected AppVersionService $appVersionService;

    /**
     * 检查版本更新
     */
    #[OA\Get(
        path: '/version/check',
        summary: '检查版本更新',
        tags: ['APP 版本'],
        parameters: [
            new OA\Parameter(name: 'platform', in: 'query', required: true, description: 'ios / android', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'version_code', in: 'query', required: true, description: '当前 APP 版本号', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功，含 has_update / version / download_url', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '参数缺失', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function check(): Response
    {
        try {
            $platform = (string) $this->request->param('platform', '');
            $currentVersion = (int) $this->request->param('version_code', 0);

            if (empty($platform) || $currentVersion <= 0) {
                return $this->error('请提供平台和当前版本号');
            }

            $result = $this->appVersionService->checkUpdate($platform, $currentVersion);
            return $this->success(lang('messages.get_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
