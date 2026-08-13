<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\api\controller\v1\goods;

use app\service\goods\GoodsCategoryService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '商品分类', description: '商品分类树（公开）')]
class CategoryController extends Controller
{
    protected GoodsCategoryService $goodsCategoryService;

    #[OA\Get(
        path: '/category/tree',
        summary: '商品分类树（公开）',
        tags: ['商品分类'],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
        ]
    )]
    public function tree(): Response
    {
        $tree = $this->goodsCategoryService->getTree();
        return $this->success('success', $tree);
    }
}
