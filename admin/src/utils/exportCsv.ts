/**
 * 前端 CSV 导出工具
 *
 * 用法：
 * ```ts
 * exportCsv('balance-log-2026.csv', rows, [
 *     { label: 'ID', key: 'id' },
 *     { label: '昵称', key: 'user_nickname' },
 *     { label: '金额', key: (r) => parseFloat(r.amount).toFixed(2) },
 * ])
 * ```
 *
 * - 自动加 UTF-8 BOM，Excel 直接打开中文不乱码
 * - 单元格内容如含逗号 / 引号 / 换行，自动加引号并转义
 * - 支持 key 为函数（计算字段）
 */
export interface ExportColumn<T = any> {
    /** 表头标签 */
    label: string
    /** 行数据字段名，或返回单元格内容的函数 */
    key: keyof T | ((row: T) => string | number | null | undefined)
}

function escapeCell(value: unknown): string {
    if (value === null || value === undefined) return ''
    const str = String(value)
    if (/[",\n\r]/.test(str)) {
        return `"${str.replace(/"/g, '""')}"`
    }
    return str
}

export function exportCsv<T = any>(
    filename: string,
    rows: T[],
    columns: ExportColumn<T>[]
): void {
    const header = columns.map((c) => escapeCell(c.label)).join(',')
    const body = rows
        .map((row) =>
            columns
                .map((c) => {
                    const value = typeof c.key === 'function' ? c.key(row) : (row as any)[c.key]
                    return escapeCell(value)
                })
                .join(',')
        )
        .join('\n')

    // \uFEFF 是 UTF-8 BOM，确保 Excel 正确识别编码
    const blob = new Blob(['\uFEFF' + header + '\n' + body], {
        type: 'text/csv;charset=utf-8;',
    })

    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
}

/**
 * 把树形数据拍平为列表
 * @param tree 树
 * @param childrenKey 子节点字段名
 * @param parentKey 注入"父级名称"的字段名（可选，方便导出时显示层级）
 */
export function flattenTree<T extends Record<string, any>>(
    tree: T[],
    childrenKey: string = 'children',
    parentNameField: string | null = null,
    parentName: string = ''
): T[] {
    const result: T[] = []
    for (const node of tree) {
        const flat: any = { ...node }
        if (parentNameField) flat[parentNameField] = parentName
        result.push(flat)
        if (Array.isArray(node[childrenKey]) && node[childrenKey].length) {
            const currentName = (node.name ?? node.title ?? '') as string
            result.push(
                ...flattenTree<T>(
                    node[childrenKey],
                    childrenKey,
                    parentNameField,
                    currentName
                )
            )
        }
    }
    return result
}
