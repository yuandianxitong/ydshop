import type { PageQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

/** GoodsAttributeGroup 创建请求 */
export interface GoodsAttributeGroupCreateReq {
  /** 分组名称 */
  name?: string
  /** 分类ID */
  category_id?: number
  /** 排序 */
  sort?: number
}

/** GoodsAttributeGroup 更新请求 */
export interface GoodsAttributeGroupUpdateReq {
  id: number
  /** 分组名称 */
  name?: string
  /** 分类ID */
  category_id?: number
  /** 排序 */
  sort?: number
}

/** GoodsAttributeGroup 详情 */
export interface GoodsAttributeGroupInfo {
  /** id */
  id: number
  /** 分组名称 */
  name: string
  /** 分类ID */
  category_id: number
  /** 排序 */
  sort: number
  /** 创建时间 */
  created_at: string
  /** 更新时间 */
  updated_at: string
  /** 关联属性列表（getWithAttrs 接口返回） */
  attributes?: any[]
  [key: string]: any
}
/**
 * GoodsAttributeGroup 管理API
 */
export const goodsAttributeGroupApi = {
    /** 获取列表 */
    getList(params: PageQuery & { keyword?: string; category_id?: number }) {
        return myRequest.get<PageResult<GoodsAttributeGroupInfo>>(
            '/adminapi/goods/goods-attribute-group',
            { params }
        )
    },

    /** 获取详情 */
    getDetail(id: number) {
        return myRequest.get<GoodsAttributeGroupInfo>(
            `/adminapi/goods/goods-attribute-group/${id}`
        )
    },

    /** 创建 */
    create(data: GoodsAttributeGroupCreateReq) {
        return myRequest.post<void>('/adminapi/goods/goods-attribute-group', data)
    },

    /** 更新 */
    update(id: number, data: Partial<GoodsAttributeGroupUpdateReq>) {
        return myRequest.put<void>(`/adminapi/goods/goods-attribute-group/${id}`, data)
    },

    /**
     * 删除
     */
    delete(id: number) {
        return myRequest.delete(`/adminapi/goods/goods-attribute-group/${id}`)
    },

    /**
     * 批量删除
     */
    batchDelete(data: { ids: number[] }) {
        return myRequest.post('/adminapi/goods/goods-attribute-group/batch-delete', data)
    },

    /**
     * 获取分组及属性（按分类筛选，一次返回）
     */
    getWithAttrs(params: { category_id: number }) {
        return myRequest.get('/adminapi/goods/goods-attribute-group/with-attrs', { params })
    },
}