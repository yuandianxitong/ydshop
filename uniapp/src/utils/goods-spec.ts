import type { GoodsDetail, SkuItem } from '@/api/goods'

// d-spec-selector 期望的规格组结构
export interface SpecGroup {
  name: string
  values: Array<{ id: number; value: string }>
}

// 从 goods detail 构建规格组：兼容 backend 的两种返回（specs 已格式化 / specNames 未格式化）
// 与 modules/goods/pages/detail.vue 的逻辑保持一致
export function buildSpecGroups(goods: GoodsDetail): SpecGroup[] {
  const backendSpecs = (goods as any).specs as
    | Array<{ name: string; values: string[] }>
    | undefined
  if (backendSpecs?.length) {
    return backendSpecs.map((spec, specIdx) => ({
      name: spec.name,
      values: spec.values.map((v, vIdx) => ({
        id: specIdx * 1000 + vIdx + 1,
        value: v,
      })),
    }))
  }

  const specNames = (goods as any).specNames as
    | Array<{ id: number; name: string; values: Array<{ id: number; value: string }> }>
    | undefined
  if (specNames?.length) {
    return specNames.map((spec, specIdx) => ({
      name: spec.name,
      values: (spec.values ?? []).map((v, vIdx) => ({
        id: v.id ?? specIdx * 1000 + vIdx + 1,
        value: v.value,
      })),
    }))
  }

  return []
}

// 给 SKU 补齐 spec_values（d-spec-selector 通过它匹配选中规格）
export function buildSkuItems(goods: GoodsDetail): SkuItem[] {
  const skus = goods.skus ?? []
  if (!skus.length) return []

  const specNames = (goods as any).specNames as
    | Array<{ id: number; name: string; values: Array<{ id: number; value: string }> }>
    | undefined
  const idMap = new Map<number, { specName: string; value: string }>()
  ;(specNames ?? []).forEach(spec => {
    ;(spec.values ?? []).forEach(v => {
      idMap.set(v.id, { specName: spec.name, value: v.value })
    })
  })

  if (idMap.size === 0) return skus

  return skus.map(sku => {
    if (sku.spec_values && Object.keys(sku.spec_values).length) return sku
    const ids = (sku.spec_value_ids ?? []) as number[]
    const specValues: Record<string, string> = {}
    ids.forEach(id => {
      const found = idMap.get(id)
      if (found) specValues[found.specName] = found.value
    })
    return { ...sku, spec_values: specValues }
  })
}
