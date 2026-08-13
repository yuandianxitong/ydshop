import type { PageQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

/** GoodsFreightTemplate 创建请求 */
export interface GoodsFreightTemplateCreateReq {
  /** 模板名称 */
  name?: string
  /** 计费方式 */
  charge_type?: string
  /** 是否免运费 */
  is_free?: number
  /** 排序 */
  sort?: number
}

/** GoodsFreightTemplate 更新请求 */
export interface GoodsFreightTemplateUpdateReq {
  id: number
  /** 模板名称 */
  name?: string
  /** 计费方式 */
  charge_type?: string
  /** 是否免运费 */
  is_free?: number
  /** 排序 */
  sort?: number
}

/** GoodsFreightTemplate 详情 */
export interface GoodsFreightTemplateInfo {
  /** id */
  id: number
  /** 模板名称 */
  name: string
  /** 计费方式 */
  charge_type: string
  /** 是否免运费 */
  is_free: number
  /** 排序 */
  sort: number
  /** 计费规则 */
  rules?: any[]
  /** 包邮规则 */
  free_rules?: any[]
  /** 不配送地区 ID 列表 */
  no_delivery_region_ids?: number[]
  /** 创建时间 */
  created_at: string
  /** 更新时间 */
  updated_at: string
  [key: string]: any
}
/**
 * GoodsFreightTemplate 管理API
 */
export const goodsFreightTemplateApi = {
    /** 获取列表 */
    getList(params: PageQuery & { keyword?: string; status?: number }) {
        return myRequest.get<PageResult<GoodsFreightTemplateInfo>>(
            '/adminapi/goods/goods-freight-template',
            { params }
        )
    },

    /** 获取详情 */
    getDetail(id: number) {
        return myRequest.get<GoodsFreightTemplateInfo>(
            `/adminapi/goods/goods-freight-template/${id}`
        )
    },

    /** 创建 */
    create(data: GoodsFreightTemplateCreateReq) {
        return myRequest.post<void>('/adminapi/goods/goods-freight-template', data)
    },

    /** 更新 */
    update(id: number, data: Partial<GoodsFreightTemplateUpdateReq>) {
        return myRequest.put<void>(`/adminapi/goods/goods-freight-template/${id}`, data)
    },

    /**
     * 删除
     */
    delete(id: number) {
        return myRequest.delete(`/adminapi/goods/goods-freight-template/${id}`)
    },

    /**
     * 批量删除
     */
    batchDelete(data: { ids: number[] }) {
        return myRequest.post('/adminapi/goods/goods-freight-template/batch-delete', data)
    },
}