import type { PageQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

/** GoodsBrand 创建请求 */
export interface GoodsBrandCreateReq {
  /** 品牌名称 */
  name?: string
  /** Logo */
  logo?: string
  /** 描述 */
  description?: string
  /** 排序 */
  sort?: number
  /** 状态:1启用,0禁用 */
  status?: number
}

/** GoodsBrand 更新请求 */
export interface GoodsBrandUpdateReq {
  id: number
  /** 品牌名称 */
  name?: string
  /** Logo */
  logo?: string
  /** 描述 */
  description?: string
  /** 排序 */
  sort?: number
  /** 状态:1启用,0禁用 */
  status?: number
}

/** GoodsBrand 详情 */
export interface GoodsBrandInfo {
  /** id */
  id: number
  /** 品牌名称 */
  name: string
  /** Logo */
  logo: string
  /** 描述 */
  description: string
  /** 排序 */
  sort: number
  /** 状态:1启用,0禁用 */
  status: number
  /** 创建时间 */
  created_at: string
  /** 更新时间 */
  updated_at: string
}
/**
 * GoodsBrand 管理API
 */
export const goodsBrandApi = {
    /** 获取列表 */
    getList(params: PageQuery & { status?: number; keyword?: string }) {
        return myRequest.get<PageResult<GoodsBrandInfo>>('/adminapi/goods/goods-brand', { params })
    },

    /** 获取详情 */
    getDetail(id: number) {
        return myRequest.get<GoodsBrandInfo>(`/adminapi/goods/goods-brand/${id}`)
    },

    /** 创建 */
    create(data: GoodsBrandCreateReq) {
        return myRequest.post<void>('/adminapi/goods/goods-brand', data)
    },

    /** 更新 */
    update(id: number, data: Partial<GoodsBrandUpdateReq>) {
        return myRequest.put<void>(`/adminapi/goods/goods-brand/${id}`, data)
    },

    /**
     * 删除
     */
    delete(id: number) {
        return myRequest.delete(`/adminapi/goods/goods-brand/${id}`)
    },

    /**
     * 批量删除
     */
    batchDelete(data: { ids: number[] }) {
        return myRequest.post('/adminapi/goods/goods-brand/batch-delete', data)
    },

    /**
     * 更新状态
     */
    updateStatus(id: number, data: { status: number }) {
        return myRequest.put(`/adminapi/goods/goods-brand/${id}/status`, data)
    },}