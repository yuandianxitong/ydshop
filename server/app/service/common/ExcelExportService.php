<?php
declare(strict_types=1);

namespace app\service\common;

use core\base\Service;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\Response;

/**
 * Excel xlsx 导出公共服务
 *
 * 流式生成 xlsx 文件并返回下载 Response。超过 maxRows 时抛 RuntimeException。
 */
class ExcelExportService extends Service
{
    public const MAX_ROWS = 50000;

    /**
     * 生成 xlsx 并返回下载 Response
     *
     * @param string   $filename  含中文，无扩展名（最终输出 .xlsx）
     * @param string[] $headers   表头列名
     * @param iterable $rows      每行有序值数组
     * @param int      $maxRows   超过此行数则抛 RuntimeException
     */
    public function streamXlsx(string $filename, array $headers, iterable $rows, int $maxRows = self::MAX_ROWS): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 表头加粗
        $col = 1;
        foreach ($headers as $h) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . '1', $h);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
            $col++;
        }

        // 数据行
        $rowIdx = 2;
        $count = 0;
        foreach ($rows as $row) {
            if ($count >= $maxRows) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                throw new \RuntimeException('超过 ' . $maxRows . ' 行限制，请缩小筛选条件');
            }
            $cellCol = 1;
            foreach ($row as $v) {
                $cellLetter = Coordinate::stringFromColumnIndex($cellCol);
                $sheet->setCellValue($cellLetter . $rowIdx, $v);
                $cellCol++;
            }
            $rowIdx++;
            $count++;
        }

        // 列宽自动（getColumnDimensionByColumn 在 2.x 仍存在）
        for ($i = 1; $i < $col; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        // 写到临时文件
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        (new Xlsx($spreadsheet))->save($tmp);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $content = file_get_contents($tmp);
        @unlink($tmp);

        $encodedName = rawurlencode($filename) . '.xlsx';

        return Response::create($content)
            ->code(200)
            ->header([
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename*=UTF-8''{$encodedName}",
                'Content-Length'      => (string) strlen($content),
                'Cache-Control'       => 'no-cache, no-store, must-revalidate',
                'Pragma'              => 'no-cache',
            ]);
    }
}
