<?php
declare(strict_types=1);

namespace app\service\goods;

use app\repository\goods\GoodsBrandRepository;
use app\repository\goods\GoodsCategoryRepository;
use app\repository\goods\GoodsUnitRepository;
use core\base\Service;
use core\exception\BusinessException;

/**
 * SPU 批量导入服务
 *
 * 支持流程：
 *   1. 下载 CSV 模板（含字段说明 + 示例数据）
 *   2. 上传文件 → 服务端解析 + 校验 → 返回预览（包含每行 ok / err 状态）
 *   3. 确认导入 → 仅对 ok 行调用 GoodsSpuService::create
 *
 * 模板字段：
 *   - 商品名称   (必填)
 *   - 商品类型   (physical / virtual / combo，默认 physical)
 *   - 副标题
 *   - 分类 ID   (可选；指定时必须存在)
 *   - 品牌 ID   (可选；指定时必须存在)
 *   - 单位 ID   (可选；指定时必须存在)
 *   - 售价      (数字，> 0)
 *   - 划线价
 *   - 成本价
 *   - 库存
 *   - 排序
 *   - 状态      (draft / on_sale / off_sale，默认 draft)
 *
 * 仅支持单 SKU 商品；多规格商品请走前端编辑抽屉。
 */
class GoodsSpuImportService extends Service
{
    protected GoodsSpuService $goodsSpuService;
    protected GoodsCategoryRepository $categoryRepo;
    protected GoodsBrandRepository $brandRepo;
    protected GoodsUnitRepository $unitRepo;

    /** CSV 列定义：[label, key, required] */
    private const COLUMNS = [
        ['商品名称',   'name',         true],
        ['商品类型',   'type',         false],
        ['副标题',     'subtitle',     false],
        ['分类 ID',    'category_id',  false],
        ['品牌 ID',    'brand_id',     false],
        ['单位 ID',    'unit_id',      false],
        ['售价',       'price',        false],
        ['划线价',     'market_price', false],
        ['成本价',     'cost_price',   false],
        ['库存',       'stock',        false],
        ['排序',       'sort',         false],
        ['状态',       'status',       false],
    ];

    private const ALLOWED_TYPES   = ['physical', 'virtual', 'combo'];
    private const ALLOWED_STATUS  = ['draft', 'on_sale', 'off_sale'];
    private const MAX_ROWS        = 1000;

    /**
     * 生成 CSV 模板内容（含 BOM，Excel 可直接打开）
     */
    public function getTemplateContent(): string
    {
        $headers = array_column(self::COLUMNS, 0);
        $sample = [
            ['示例商品 A', 'physical', '示例副标题', '', '', '', '99.00', '128.00', '60.00', '100', '0', 'draft'],
            ['示例商品 B', 'physical', '',           '', '', '', '199.00', '0',     '0',    '50',  '0', 'on_sale'],
        ];

        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, $headers);
        foreach ($sample as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $body = stream_get_contents($fp);
        fclose($fp);

        // UTF-8 BOM，Excel 中文不乱码
        return "\xEF\xBB\xBF" . $body;
    }

    /**
     * 解析 + 校验上传文件，返回预览
     */
    public function previewImport(string $filePath): array
    {
        $rows = $this->readCsv($filePath);
        return $this->buildPreview($rows);
    }

    /**
     * 确认导入（仅导入 ok 行；逐行事务）
     */
    public function confirmImport(string $filePath): array
    {
        $rows = $this->readCsv($filePath);
        $preview = $this->buildPreview($rows);

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($preview['list'] as $item) {
            if (!$item['ok']) {
                $skipped++;
                continue;
            }
            try {
                $payload = $this->toCreatePayload($item);
                $finalStatus = $payload['__final_status'];
                unset($payload['__final_status']);
                $created = $this->goodsSpuService->create($payload);
                if ($finalStatus !== 'draft' && !empty($created['id'])) {
                    $this->goodsSpuService->updateStatus((int)$created['id'], $finalStatus);
                }
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = [
                    'row' => $item['row'],
                    'msg' => $e->getMessage(),
                ];
            }
        }

        return [
            'total'    => $preview['summary']['total'],
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }

    // ========== Private Helpers ==========

    private function readCsv(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new BusinessException('上传文件不存在或已过期');
        }

        $content = file_get_contents($filePath);
        if ($content === false || $content === '') {
            throw new BusinessException('文件内容为空');
        }

        // 去除 UTF-8 BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $content);
        rewind($fp);

        $rows = [];
        while (($cells = fgetcsv($fp)) !== false) {
            $rows[] = $cells;
        }
        fclose($fp);

        if (count($rows) < 2) {
            throw new BusinessException('文件没有数据行');
        }

        return $rows;
    }

    private function buildPreview(array $rows): array
    {
        $header = array_shift($rows);
        $colMap = $this->resolveHeaderMap($header);

        if (count($rows) > self::MAX_ROWS) {
            throw new BusinessException('单次最多导入 ' . self::MAX_ROWS . ' 行');
        }

        $list = [];
        $okCount = 0;
        foreach ($rows as $i => $cells) {
            $rowNo = $i + 2; // 1 = header
            $rec = $this->mapRow($cells, $colMap);
            $errs = $this->validateRow($rec);
            $ok = empty($errs);
            if ($ok) $okCount++;

            $list[] = [
                'row'  => $rowNo,
                'name' => $rec['name'] ?? '',
                'type' => $rec['type'] ?? '',
                'subtitle'     => $rec['subtitle'] ?? '',
                'category_id'  => $rec['category_id'] ?? '',
                'brand_id'     => $rec['brand_id'] ?? '',
                'unit_id'      => $rec['unit_id'] ?? '',
                'price'        => $rec['price'] ?? '',
                'market_price' => $rec['market_price'] ?? '',
                'cost_price'   => $rec['cost_price'] ?? '',
                'stock'        => $rec['stock'] ?? '',
                'sort'         => $rec['sort'] ?? '',
                'status'       => $rec['status'] ?? '',
                'ok'           => $ok,
                'err'          => implode('、', $errs),
            ];
        }

        return [
            'list'    => $list,
            'summary' => [
                'total' => count($list),
                'ok'    => $okCount,
                'err'   => count($list) - $okCount,
            ],
        ];
    }

    private function resolveHeaderMap(array $header): array
    {
        $map = [];
        foreach (self::COLUMNS as $col) {
            [$label, $key] = $col;
            $idx = array_search($label, $header, true);
            $map[$key] = $idx === false ? null : $idx;
        }
        if ($map['name'] === null) {
            throw new BusinessException('模板表头缺少必填列「商品名称」，请使用最新模板');
        }
        return $map;
    }

    private function mapRow(array $cells, array $colMap): array
    {
        $rec = [];
        foreach ($colMap as $key => $idx) {
            $rec[$key] = $idx === null ? '' : trim((string)($cells[$idx] ?? ''));
        }
        return $rec;
    }

    private function validateRow(array $rec): array
    {
        $errs = [];

        if ($rec['name'] === '') {
            $errs[] = '商品名称必填';
        }

        $type = $rec['type'] !== '' ? $rec['type'] : 'physical';
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $errs[] = '类型必须是 physical / virtual / combo';
        }

        $status = $rec['status'] !== '' ? $rec['status'] : 'draft';
        if (!in_array($status, self::ALLOWED_STATUS, true)) {
            $errs[] = '状态必须是 draft / on_sale / off_sale';
        }

        if ($rec['category_id'] !== '') {
            if (!ctype_digit($rec['category_id']) || !$this->categoryRepo->exists(['id' => (int)$rec['category_id']])) {
                $errs[] = '分类 ID 不存在';
            }
        }
        if ($rec['brand_id'] !== '') {
            if (!ctype_digit($rec['brand_id']) || !$this->brandRepo->exists(['id' => (int)$rec['brand_id']])) {
                $errs[] = '品牌 ID 不存在';
            }
        }
        if ($rec['unit_id'] !== '') {
            if (!ctype_digit($rec['unit_id']) || !$this->unitRepo->exists(['id' => (int)$rec['unit_id']])) {
                $errs[] = '单位 ID 不存在';
            }
        }

        foreach (['price', 'market_price', 'cost_price'] as $f) {
            if ($rec[$f] !== '' && !is_numeric($rec[$f])) {
                $errs[] = $this->colLabel($f) . '必须是数字';
            }
        }
        foreach (['stock', 'sort'] as $f) {
            if ($rec[$f] !== '' && !ctype_digit($rec[$f])) {
                $errs[] = $this->colLabel($f) . '必须是非负整数';
            }
        }

        if ($type === 'combo') {
            $errs[] = '组合商品请通过编辑抽屉创建';
        }

        return $errs;
    }

    private function colLabel(string $key): string
    {
        foreach (self::COLUMNS as $col) {
            if ($col[1] === $key) return $col[0];
        }
        return $key;
    }

    private function toCreatePayload(array $item): array
    {
        $type = $item['type'] !== '' ? $item['type'] : 'physical';
        $payload = [
            'name'         => $item['name'],
            'type'         => $type,
            'subtitle'     => $item['subtitle'] ?? '',
            'description'  => '',
            'detail'       => '',
            'sort'         => $item['sort'] !== '' ? (int)$item['sort'] : 0,
            'is_recommend' => 0,
            'is_new'       => 0,
            'is_hot'       => 0,
            'images'       => [],
            'video'        => '',
        ];
        if ($item['category_id'] !== '') $payload['category_id'] = (int)$item['category_id'];
        if ($item['brand_id'] !== '')    $payload['brand_id']    = (int)$item['brand_id'];
        if ($item['unit_id'] !== '')     $payload['unit_id']     = (int)$item['unit_id'];

        // 单 SKU（无规格）
        $payload['specs'] = [];
        $payload['skus'] = [[
            'spec_values'  => [],
            'price'        => $item['price'] !== ''        ? (float)$item['price']        : 0,
            'market_price' => $item['market_price'] !== '' ? (float)$item['market_price'] : 0,
            'cost_price'   => $item['cost_price'] !== ''   ? (float)$item['cost_price']   : 0,
            'stock'        => $item['stock'] !== ''        ? (int)$item['stock']          : 0,
            'image'        => '',
            'weight'       => 0,
            'volume'       => 0,
        ]];

        // create() 内部会先把 status 强制设为 draft；如导入要求其他状态，确认后再切换
        $finalStatus = $item['status'] !== '' ? $item['status'] : 'draft';
        $payload['__final_status'] = $finalStatus; // 临时字段，由调用方处理

        return $payload;
    }
}
