/**
 * 金额工具：所有求和 / 比较一律使用「分」整数运算，避免浮点误差。
 * 后端接口金额字段单位为「元」，仅在输入 / 提交边界做转换。
 */

/** 元 → 分（整数），Math.round 防浮点误差（如 19.9 * 100 = 1989.9999...） */
export function yuanToFen(yuan: number | string | null | undefined): number {
    const n = Number(yuan)
    if (!Number.isFinite(n)) return 0
    return Math.round(n * 100)
}

/** 分 → 元（数字） */
export function fenToYuan(fen: number | null | undefined): number {
    const n = Number(fen)
    if (!Number.isFinite(n)) return 0
    return Math.round(n) / 100
}

/** 分 → "0.00" 格式的元字符串（用于展示） */
export function formatFen(fen: number | null | undefined): string {
    return fenToYuan(fen).toFixed(2)
}
