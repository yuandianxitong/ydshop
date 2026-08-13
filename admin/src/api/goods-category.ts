import type { PageQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

/** GoodsCategory 创建请求 */
export interface GoodsCategoryCreateReq {
  /** 父分类ID */
  parent_id?: number
  /** 分类名称 */
  name?: string
  /** 图标 */
  icon?: string
  /** 排序 */
  sort?: number
  /** 层级 */
  level?: number
  /** 祖先链 */
  path?: string
  /** 状态:1启用,0禁用 */
  status?: number
  /** 是否前端展示 */
  is_show?: number
}

/** GoodsCategory 更新请求 */
export interface GoodsCategoryUpdateReq {
  id: number
  /** 父分类ID */
  parent_id?: number
  /** 分类名称 */
  name?: string
  /** 图标 */
  icon?: string
  /** 排序 */
  sort?: number
  /** 层级 */
  level?: number
  /** 祖先链 */
  path?: string
  /** 状态:1启用,0禁用 */
  status?: number
  /** 是否前端展示 */
  is_show?: number
}

/** GoodsCategory 详情 */
export interface GoodsCategoryInfo {
  /** id */
  id: number
  /** 父分类ID */
  parent_id: number
  /** 分类名称 */
  name: string
  /** 图标 */
  icon: string
  /** 排序 */
  sort: number
  /** 层级 */
  level: number
  /** 祖先链 */
  path: string
  /** 状态:1启用,0禁用 */
  status: number
  /** 是否前端展示 */
  is_show: number
  /** 创建时间 */
  created_at: string
  /** 更新时间 */
  updated_at: string
}
/**
 * GoodsCategory 管理API
 */
export const goodsCategoryApi = {
    /** 获取列表 */
    getList(params: PageQuery & { status?: number; keyword?: string }) {
        return myRequest.get<PageResult<GoodsCategoryInfo>>('/adminapi/goods/goods-category', {
            params
        })
    },

    /** 获取详情 */
    getDetail(id: number) {
        return myRequest.get<GoodsCategoryInfo>(`/adminapi/goods/goods-category/${id}`)
    },

    /** 创建 */
    create(data: GoodsCategoryCreateReq) {
        return myRequest.post<void>('/adminapi/goods/goods-category', data)
    },

    /** 更新 */
    update(id: number, data: Partial<GoodsCategoryUpdateReq>) {
        return myRequest.put<void>(`/adminapi/goods/goods-category/${id}`, data)
    },

    /**
     * 删除
     */
    delete(id: number) {
        return myRequest.delete(`/adminapi/goods/goods-category/${id}`)
    },

    /**
     * 批量删除
     */
    batchDelete(data: { ids: number[] }) {
        return myRequest.post('/adminapi/goods/goods-category/batch-delete', data)
    },

    /**
     * 更新状态
     */
    updateStatus(id: number, data: { status: number }) {
        return myRequest.put(`/adminapi/goods/goods-category/${id}/status`, data)
    },

    /**
     * 获取分类树
     */
    getTree() {
        return myRequest.get('/adminapi/goods/goods-category/tree')
    },

    /** 选择器水合：按 IDs 取轻量字段（id/name/parent_id），顺序与入参一致 */
    getByIds(ids: number[]) {
        return myRequest.get<Array<{
            id: number
            name: string
            parent_id: number
        }>>('/adminapi/goods/goods-category/by-ids', { params: { ids: ids.join(',') } })
    },
}