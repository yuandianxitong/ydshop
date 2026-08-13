import { get } from '~/composables/useRequest'

// C 端公开列表 item shape（与后端 GoodsSpuRepository::getPublicPageList / getByIds / getListByCategory / getListByTag 对齐）
export interface GoodsItem {
  id: number
  name: string
  subtitle?: string
  images: string[]
  min_price: number
  max_price: number
  sales_count: number
  type?: string
  is_new?: 0 | 1
  is_hot?: 0 | 1
  is_recommend?: 0 | 1
}

// 公开列表分页响应（buildPagination 包装）
export interface GoodsListResponse {
  list: GoodsItem[]
  pagination: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

// 详情接口仍按 spu_id 关联，保留更完整的字段（如 cover / price / status 等仍由详情接口提供）
export interface GoodsDetail {
  id: number
  spu_id: number
  name: string
  subtitle?: string
  cover: string
  description: string  // 短描述
  detail: string       // 富文本详情（后台编辑器内容）
  images: string[]
  price: number
  original_price: number
  sales: number
  stock: number
  category_id: number
  category_name?: string
  status: string
  /** SPU 级支持的配送方式（如 ['express','pickup','merchant']），缺省视为 ['express','pickup'] */
  delivery_modes?: string[]
  skus: SkuItem[]
  attributes: AttributeItem[]
}

export interface SkuItem {
  id: number
  spu_id: number
  name: string
  price: number
  original_price: number
  stock: number
  image: string
  attributes: Record<string, string>
}

export interface AttributeItem {
  name: string
  values: string[]
}

export interface CategoryItem {
  id: number
  name: string
  icon?: string
  sort: number
  children?: CategoryItem[]
}

export interface ReviewItem {
  id: number
  spu_id: number
  order_id: number
  user_id: number
  nickname: string
  avatar: string
  rating: number
  content: string
  images: string[]
  created_at: string
}

// 评价列表分页（如有 page_no / page_size shape，按需调整）
import type { PageResult } from '~/composables/useRequest'

export const goodsApi = {
  getGoodsList: (params?: { page_no?: number; page_size?: number; category_id?: number; keyword?: string; sort?: string }) =>
    get<GoodsListResponse>('/api/goods/list', params),

  getGoodsDetail: (id: number | string) =>
    get<GoodsDetail>(`/api/goods/${id}`),

  getCategoryTree: () =>
    get<CategoryItem[]>('/api/category/tree'),

  getGoodsReviews: (spuId: number | string, params?: { page_no?: number; page_size?: number }) =>
    get<PageResult<ReviewItem>>(`/api/order-review/spu/${spuId}`, params),
}
