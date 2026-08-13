<?php
declare(strict_types=1);

namespace app\api\controller\v1\diy;

use core\base\Controller;
use app\service\diy\DiyPageService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'DIY 页面', description: '已发布的 DIY 装修页面（公开）')]
class DiyPageController extends Controller
{
    protected DiyPageService $service;

    #[OA\Get(
        path: '/diy/page',
        summary: '获取已发布的 DIY 页面（按 type + platform）',
        tags: ['DIY 页面'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'query', description: '页面类型：home / user / category 等', schema: new OA\Schema(type: 'string', default: 'home')),
            new OA\Parameter(name: 'platform', in: 'query', description: '平台：uniapp / pc', schema: new OA\Schema(type: 'string', default: 'uniapp')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功（无已发布页面时 data 为空）', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function page()
    {
        $type = $this->request->get('type', 'home');
        $platform = $this->request->get('platform', 'uniapp');
        $result = $this->service->getPublishedPage($type, $platform);
        return $this->success('获取成功', $result);
    }

    #[OA\Get(
        path: '/diy/page/{id}',
        summary: '获取自定义 DIY 页面详情',
        tags: ['DIY 页面'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '页面不存在或未发布', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function detail()
    {
        $id = (int) $this->request->param('id');
        $result = $this->service->getCustomPage($id);
        if (!$result) {
            return $this->error('页面不存在或未发布');
        }
        return $this->success('获取成功', $result);
    }
}
