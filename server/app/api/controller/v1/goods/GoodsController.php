<?php
/* ============================================================
 * 项目：元点Shop
 * 官网：https://www.dev007.cn
 * Slogan：提供高质量行业系统源码，帮助中小企业快速搭建专属应用
 * Author：mashanglai Team
 * ============================================================ */
declare(strict_types=1);

namespace app\api\controller\v1\goods;

use app\service\goods\GoodsSpuService;
use core\base\Controller;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '商品', description: 'C 端商品列表与详情（公开）')]
class GoodsController extends Controller
{
    protected GoodsSpuService $goodsSpuService;

    /**
     * 商品列表（前端展示，仅在售商品）
     */
    #[OA\Get(
        path: '/goods',
        summary: '商品列表（公开）',
        tags: ['商品'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', description: 'sales_desc / price_asc / price_desc / new', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function index(): Response
    {
        $params = $this->request->get();
        return $this->success('success', $this->goodsSpuService->getPublicList($params));
    }

    /**
     * 商品详情
     *
     * 允许 on_sale + off_sale 状态的 SPU 被查到。
     * - off_sale（已下架）也返回数据，前端按 status 字段显示"已下架"蒙层并禁用购买按钮，
     *   保证从订单/购物车 / checkout 链路过来的商品（可能在期间被下架）依然能展示
     * - draft（草稿）始终不可见（后台未发布）
     */
    #[OA\Get(
        path: '/goods/{id}',
        summary: '商品详情（公开）',
        tags: ['商品'],
        description: 'on_sale + off_sale 均可查；draft 不可见',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')),
            new OA\Response(response: 400, description: '商品不存在或为草稿', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(): Response
    {
        try {
            $id     = (int)$this->request->param('id');
            $detail = $this->goodsSpuService->getPublicDetail($id);
            return $this->success('success', $detail);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
