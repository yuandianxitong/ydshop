<?php
declare(strict_types=1);

namespace app\api\controller\v1\region;

use core\base\Controller;
use app\service\region\RegionService;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '地区', description: '省市区地理数据（公开）')]
class RegionController extends Controller
{
    protected RegionService $regionService;

    /**
     * 获取地区树
     */
    #[OA\Get(
        path: '/region/tree',
        summary: '地区树（公开）',
        tags: ['地区'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function tree(): Response
    {
        try {
            $result = $this->regionService->getTree();
            return $this->success(lang('messages.get_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取子级地区列表
     */
    #[OA\Get(
        path: '/region/children',
        summary: '子级地区列表（公开）',
        tags: ['地区'],
        parameters: [
            new OA\Parameter(name: 'parent_id', in: 'query', description: '父级地区 ID（0=省级）', schema: new OA\Schema(type: 'integer', default: 0)),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function children(): Response
    {
        try {
            $parentId = (int) $this->request->param('parent_id', 0);
            $result = $this->regionService->getByParentId($parentId);
            return $this->success(lang('messages.get_success'), $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
