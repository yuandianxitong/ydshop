import type { PageQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

/** 单个规格项 */
export interface GoodsSpecTemplateItem {
  name: string
  values: string[]
}

/** 创建/更新请求体 */
export interface GoodsSpecTemplateReq {
  name: string
  description?: string
  items: GoodsSpecTemplateItem[]
  sort?: number
  status?: number
}

/** 列表/详情返回 */
export interface GoodsSpecTemplateInfo {
  id: number
  name: string
  description: string
  items: GoodsSpecTemplateItem[]
  sort: number
  status: number
  created_at: string
  updated_at: string
}

export const goodsSpecTemplateApi = {
  getList(params: PageQuery & { keyword?: string; status?: number }) {
    return myRequest.get<PageResult<GoodsSpecTemplateInfo>>('/adminapi/goods/goods-spec-template', { params })
  },

  getDetail(id: number) {
    return myRequest.get<GoodsSpecTemplateInfo>(`/adminapi/goods/goods-spec-template/${id}`)
  },

  create(data: GoodsSpecTemplateReq) {
    return myRequest.post<void>('/adminapi/goods/goods-spec-template', data)
  },

  update(id: number, data: Partial<GoodsSpecTemplateReq>) {
    return myRequest.put<void>(`/adminapi/goods/goods-spec-template/${id}`, data)
  },

  delete(id: number) {
    return myRequest.delete(`/adminapi/goods/goods-spec-template/${id}`)
  },

  batchDelete(data: { ids: number[] }) {
    return myRequest.post('/adminapi/goods/goods-spec-template/batch-delete', data)
  },
}
