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

/** C 端公开详情：findPublicDetail 直接 toArray，字段与后台 getDetail 不完全相同 */
export interface GoodsSpecName {
  id: number
  name: string
  values: Array<{ id: number; value: string }>
}

export interface GoodsAttributeValue {
  attribute_id: number
  value: string
  attribute?: { id: number; name: string }
}

export interface GoodsDetail {
  id: number
  spu_id?: number
  name: string
  subtitle?: string
  /** 公开详情通常没有 cover，用 images[0] */
  cover?: string
  description: string
  detail: string
  images: string[]
  price?: number
  min_price?: number
  max_price?: number
  original_price?: number
  sales?: number
  sales_count?: number
  stock?: number
  total_stock?: number
  category_id: number
  category_name?: string
  status: string
  delivery_modes?: string[]
  skus: SkuItem[]
  /** 后台 getDetail 格式化后的规格组 */
  specs?: Array<{ name: string; values: string[] }>
  /** 公开详情原始关联 */
  specNames?: GoodsSpecName[]
  attributes?: AttributeItem[]
  attributeValues?: GoodsAttributeValue[]
}

export interface SkuItem {
  id: number
  spu_id: number
  name?: string
  spec_text?: string
  price: number
  original_price?: number
  market_price?: number
  stock: number
  image: string
  spec_value_ids?: number[]
  spec_values?: Record<string, string>
  /** 旧字段，公开详情没有 */
  attributes?: Record<string, string>
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
