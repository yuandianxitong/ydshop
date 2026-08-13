<?php

declare(strict_types=1);

namespace app\adminapi\controller\v1\goods;

use app\adminapi\validate\v1\goods\GoodsSpuValidate;
use app\service\goods\GoodsSpuImportService;
use app\service\goods\GoodsSpuService;
use core\attribute\Permission;
use core\base\Controller;
use core\exception\BusinessException;
use core\storage\UploadSecurity;
use think\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: '商品 SPU', description: '商品（SPU）CRUD + 上下架 + 批量操作 + 导入')]
class GoodsSpuController extends Controller
{
    protected GoodsSpuService $goodsSpuService;
    protected GoodsSpuImportService $goodsSpuImportService;

    #[Permission('goods.spu.list')]
    #[OA\Get(
        path: '/goods/spu',
        summary: '商品 SPU 列表',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'keyword', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'on_sale/off_sale/draft', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'brand_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse'))]
    )]
    public function index(): Response
    {
        return $this->paginate($this->goodsSpuService->getList($this->getRequestData()));
    }

    #[Permission('goods.spu.list')]
    #[OA\Get(
        path: '/goods/spu/by-ids',
        summary: '选择器水合（按 IDs 取轻量字段）',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        parameters: [new OA\Parameter(name: 'ids', in: 'query', required: true, description: '逗号分隔 ID 列表，单次 ≤ 100', schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function byIds(): Response
    {
        $idsRaw = (string) $this->request->get('ids', '');
        $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw)), fn ($n) => $n > 0));
        if (count($ids) > 100) {
            throw new BusinessException('ids 数量不可超过 100');
        }
        return $this->success('获取成功', $this->goodsSpuService->getByIds($ids));
    }

    #[Permission('goods.spu.list')]
    #[OA\Get(
        path: '/goods/spu/skus-by-ids',
        summary: '按 SPU IDs 批量取 SKU 列表',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        parameters: [new OA\Parameter(name: 'ids', in: 'query', required: true, description: '逗号分隔 SPU ID，单次 ≤ 100', schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function skusByIds(): Response
    {
        $idsRaw = (string)$this->request->get('ids', '');
        $ids = array_values(array_filter(array_map('intval', explode(',', $idsRaw)), fn ($n) => $n > 0));
        if (count($ids) > 100) {
            throw new BusinessException('ids 数量不可超过 100');
        }
        return $this->success('获取成功', $this->goodsSpuService->getSkusByIds($ids));
    }

    #[Permission('goods.spu.list')]
    #[OA\Get(
        path: '/goods/spu/{id}',
        summary: 'SPU 详情',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '获取成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function show(): Response
    {
        return $this->success('获取成功', $this->goodsSpuService->getDetail((int) $this->request->param('id')));
    }

    #[Permission('goods.spu.create')]
    #[OA\Post(
        path: '/goods/spu',
        summary: '创建 SPU（含 SKU/规格/属性）',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'category_id', type: 'integer'),
                new OA\Property(property: 'brand_id', type: 'integer'),
                new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'skus', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'spec_names', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'attribute_values', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'status', type: 'string', enum: ['on_sale', 'off_sale', 'draft']),
            ])
        ),
        responses: [new OA\Response(response: 200, description: '创建成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function store(): Response
    {
        $data = $this->request->post();
        $this->validate($data, GoodsSpuValidate::class, [], 'create');
        return $this->success('创建成功', $this->goodsSpuService->create($data));
    }

    #[Permission('goods.spu.update')]
    #[OA\Put(
        path: '/goods/spu/{id}',
        summary: '更新 SPU',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function update(): Response
    {
        $id = (int) $this->request->param('id');
        $data = $this->request->post();
        $this->validate($data, GoodsSpuValidate::class, [], 'update');
        return $this->success('更新成功', $this->goodsSpuService->update($id, $data));
    }

    #[Permission('goods.spu.delete')]
    #[OA\Delete(
        path: '/goods/spu/{id}',
        summary: '删除 SPU',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: '删除成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function delete(): Response
    {
        $this->goodsSpuService->delete((int) $this->request->param('id'));
        return $this->success('删除成功');
    }

    #[Permission('goods.spu.update')]
    #[OA\Put(
        path: '/goods/spu/{id}/status',
        summary: '更新 SPU 状态',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', enum: ['on_sale', 'off_sale', 'draft'])])
        ),
        responses: [new OA\Response(response: 200, description: '操作成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function status(): Response
    {
        $id = (int) $this->request->param('id');
        $this->goodsSpuService->updateStatus($id, (string) $this->request->post('status', ''));
        return $this->success('操作成功');
    }

    #[Permission('goods.spu.update')]
    #[OA\Post(
        path: '/goods/spu/batch-on-sale',
        summary: '批量上架',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'))])
        ),
        responses: [new OA\Response(response: 200, description: '上架成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function batchOnSale(): Response
    {
        $count = $this->goodsSpuService->batchOnSale($this->request->post('ids', []));
        return $this->success("成功上架 {$count} 件商品");
    }

    #[Permission('goods.spu.update')]
    #[OA\Put(
        path: '/goods/spu/batch-delivery',
        summary: '批量切换自提模式',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids', 'action'],
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'action', type: 'string', enum: ['enable_pickup', 'disable_pickup']),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: '批量更新成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function batchDelivery(): Response
    {
        $data = $this->request->put();
        $ids = (array)($data['ids'] ?? []);
        $action = (string)($data['action'] ?? '');
        if (empty($ids)) {
            return $this->error('请选择商品');
        }
        if (!in_array($action, ['enable_pickup', 'disable_pickup'], true)) {
            return $this->error('参数错误');
        }
        $this->goodsSpuService->batchDelivery($ids, $action);
        return $this->success('批量更新成功');
    }

    #[Permission('goods.spu.update')]
    #[OA\Post(
        path: '/goods/spu/batch-off-sale',
        summary: '批量下架',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'))])
        ),
        responses: [new OA\Response(response: 200, description: '下架成功', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function batchOffSale(): Response
    {
        $count = $this->goodsSpuService->batchOffSale($this->request->post('ids', []));
        return $this->success("成功下架 {$count} 件商品");
    }

    #[Permission('goods.spu.create')]
    #[OA\Get(
        path: '/goods/spu/import-template',
        summary: '下载批量导入 CSV 模板',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        responses: [new OA\Response(response: 200, description: 'CSV 文件流')]
    )]
    public function importTemplate(): Response
    {
        $content = $this->goodsSpuImportService->getTemplateContent();
        return response($content, 200)
            ->contentType('text/csv; charset=utf-8')
            ->header([
                'Content-Disposition' => 'attachment; filename="goods-spu-import-template.csv"',
                'Content-Length'      => (string)strlen($content),
            ]);
    }

    #[Permission('goods.spu.create')]
    #[OA\Post(
        path: '/goods/spu/import-preview',
        summary: '上传文件预览（不写库）',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(properties: [new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'CSV 文件，最大 10MB')]))
        ),
        responses: [new OA\Response(response: 200, description: '校验完成，含 ok/error 行 + file_path', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function importPreview(): Response
    {
        $file = $this->request->file('file');
        if (!$file) {
            return $this->error('请上传文件');
        }
        $filePath = $this->saveUpload($file);
        try {
            $result = $this->goodsSpuImportService->previewImport($filePath);
            // 仅向客户端返回随机文件令牌，不暴露服务器绝对路径。
            $result['file_path'] = basename($filePath);
            return $this->success('校验完成', $result);
        } catch (\Throwable $e) {
            @unlink($filePath);
            throw $e;
        }
    }

    #[Permission('goods.spu.create')]
    #[OA\Post(
        path: '/goods/spu/import-confirm',
        summary: '确认导入（仅导入 ok 行）',
        security: [['bearerAuth' => []]],
        tags: ['商品 SPU'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [new OA\Property(property: 'file_path', type: 'string', description: 'preview 返回的临时文件令牌')])
        ),
        responses: [new OA\Response(response: 200, description: '导入完成，返回 imported 数量', content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'))]
    )]
    public function importConfirm(): Response
    {
        $fileToken = (string) $this->request->post('file_path', '');
        $filePath = $fileToken === '' ? '' : $this->resolveUploadPath($fileToken);
        if ($fileToken === '') {
            $file = $this->request->file('file');
            if ($file) {
                $filePath = $this->saveUpload($file);
            }
        }
        if ($filePath === '' || !is_file($filePath)) {
            return $this->error('请先上传并校验文件');
        }
        try {
            $result = $this->goodsSpuImportService->confirmImport($filePath);
            return $this->success("成功导入 {$result['imported']} 条", $result);
        } finally {
            @unlink($filePath);
        }
    }

    private function saveUpload(\think\file\UploadedFile $file): string
    {
        $extension = UploadSecurity::fileExtension($file->extension(), $file->getMime());
        if ($extension !== 'csv') {
            throw new BusinessException('仅支持 CSV 文件');
        }
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new BusinessException(lang('business.file_size_10mb'));
        }

        $directory = $this->uploadDirectory();
        $filename = bin2hex(random_bytes(16)) . '.csv';
        $savedFile = $file->move($directory, $filename);
        return $savedFile->getPathname();
    }

    private function resolveUploadPath(string $fileToken): string
    {
        if (!preg_match('/^[a-f0-9]{32}\.csv$/D', $fileToken)) {
            return '';
        }

        return $this->uploadDirectory() . DIRECTORY_SEPARATOR . $fileToken;
    }

    private function uploadDirectory(): string
    {
        return rtrim(runtime_path(), '/\\') . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . 'goods-spu';
    }
}
