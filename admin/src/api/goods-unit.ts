import { myRequest } from '@/utils/request'

/** 计量单位详情 */
export interface GoodsUnitInfo {
  id: number
  code: string
  name: string
  name_en?: string
  group_id: number
  decimal_places: number
  is_base: number
  ratio: number
  sort: number
  status: number
  ratio_text?: string
  linked_spu_count?: number
  group?: GoodsUnitGroupInfo
  created_at: string
  updated_at: string
}

/** 计量单位分组 */
export interface GoodsUnitGroupInfo {
  id: number
  code: string
  name: string
  tone: string
  sort: number
  status: number
  units_count?: number
  created_at?: string
  updated_at?: string
}

/** 计量单位概览统计 */
export interface GoodsUnitStats {
  unit_total: number
  conv_total: number
  linked_total: number
  group_total: number
}

/** 换算关系（从基准单位推导） */
export interface GoodsUnitConversion {
  id: number
  from_id: number
  from_code: string
  from_name: string
  to_id: number
  to_code: string
  to_name: string
  group_id: number
  group_name: string
  group_tone: string
  ratio: number
}

/**
 * 计量单位 API
 */
export const goodsUnitApi = {
    getList(params: Record<string, any>) {
        return myRequest.get('/adminapi/goods/goods-unit', { params })
    },
    getDetail(id: number) {
        return myRequest.get(`/adminapi/goods/goods-unit/${id}`)
    },
    create(data: Record<string, any>) {
        return myRequest.post('/adminapi/goods/goods-unit', data)
    },
    update(id: number, data: Record<string, any>) {
        return myRequest.put(`/adminapi/goods/goods-unit/${id}`, data)
    },
    delete(id: number) {
        return myRequest.delete(`/adminapi/goods/goods-unit/${id}`)
    },
    batchDelete(data: { ids: number[] }) {
        return myRequest.post('/adminapi/goods/goods-unit/batch-delete', data)
    },
    updateStatus(id: number, data: { status: number }) {
        return myRequest.put(`/adminapi/goods/goods-unit/${id}/status`, data)
    },
    getStats() {
        return myRequest.get('/adminapi/goods/goods-unit/stats')
    },
    getConversions(params: Record<string, any> = {}) {
        return myRequest.get('/adminapi/goods/goods-unit/conversions', { params })
    },
}

/**
 * 计量单位分组 API
 */
export const goodsUnitGroupApi = {
    getList() {
        return myRequest.get('/adminapi/goods/goods-unit-group')
    },
    create(data: Record<string, any>) {
        return myRequest.post('/adminapi/goods/goods-unit-group', data)
    },
    update(id: number, data: Record<string, any>) {
        return myRequest.put(`/adminapi/goods/goods-unit-group/${id}`, data)
    },
    delete(id: number) {
        return myRequest.delete(`/adminapi/goods/goods-unit-group/${id}`)
    },
}
