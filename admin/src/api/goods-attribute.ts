import type { PageQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

/** GoodsAttribute 创建请求 */
export interface GoodsAttributeCreateReq {
  /** 分组ID */
  group_id?: number
  /** 属性名称 */
  name?: string
  /** input/select/multi_select */
  type?: string
  /** 预设值 */
  options?: any
  /** 排序 */
  sort?: number
}

/** GoodsAttribute 更新请求 */
export interface GoodsAttributeUpdateReq {
  id: number
  /** 分组ID */
  group_id?: number
  /** 属性名称 */
  name?: string
  /** input/select/multi_select */
  type?: string
  /** 预设值 */
  options?: any
  /** 排序 */
  sort?: number
}

/** GoodsAttribute 详情 */
export interface GoodsAttributeInfo {
  /** id */
  id: number
  /** 分组ID */
  group_id: number
  /** 属性名称 */
  name: string
  /** input/select/multi_select */
  type: string
  /** 预设值 */
  options: any
  /** 排序 */
  sort: number
  /** 创建时间 */
  created_at: string
  /** 更新时间 */
  updated_at: string
}
/**
 * GoodsAttribute 管理API
 */
export const goodsAttributeApi = {
    /** 获取列表 */
    getList(params: PageQuery & { group_id?: number; type?: string; keyword?: string }) {
        return myRequest.get<PageResult<GoodsAttributeInfo>>('/adminapi/goods/goods-attribute', {
            params
        })
    },

    /** 获取详情 */
    getDetail(id: number) {
        return myRequest.get<GoodsAttributeInfo>(`/adminapi/goods/goods-attribute/${id}`)
    },

    /** 创建 */
    create(data: GoodsAttributeCreateReq) {
        return myRequest.post<void>('/adminapi/goods/goods-attribute', data)
    },

    /** 更新 */
    update(id: number, data: Partial<GoodsAttributeUpdateReq>) {
        return myRequest.put<void>(`/adminapi/goods/goods-attribute/${id}`, data)
    },

    /**
     * 删除
     */
    delete(id: number) {
        return myRequest.delete(`/adminapi/goods/goods-attribute/${id}`)
    },

    /**
     * 批量删除
     */
    batchDelete(data: { ids: number[] }) {
        return myRequest.post('/adminapi/goods/goods-attribute/batch-delete', data)
    },
}