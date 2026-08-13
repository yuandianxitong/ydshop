import http from '@/utils/request'
import type { PageResult } from '@/types/api'

export interface GoodsItem {
  id: number
  name: string
  subtitle?: string
  images: string[]
  min_price: number
  max_price: number
  sales_count: number
  type?: string
  category_id?: number
  status?: string
}

export interface GoodsDetail extends GoodsItem {
  detail: string
  description: string
  video?: string
  total_stock: number
  view_count: number
  brand?: { id: number; name: string; logo?: string }
  skus: SkuItem[]
  specNames?: any[]
  attributeValues?: any[]
  // v2.0.0 stage1: optional fields returned by backend
  original_price?: number
  promo_tag?: string
  promo_full_off?: string
  promo_installments?: number | string
  delivery_text?: string
  delivery_address?: string
  delivery_eta?: string
  delivery_modes?: string[]
  spu_id?: number
}

export interface SkuItem {
  id: number
  spu_id: number
  sku_no: string
  price: number
  market_price: number
  stock: number
  sales_count: number
  image: string
  spec_value_ids: number[]
  spec_text: string
  spec_values?: Record<string, string>
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

export const goodsApi = {
  getGoodsList: (params?: { page_no?: number; page_size?: number; category_id?: number; keyword?: string; sort?: string }) =>
    http.get<PageResult<GoodsItem>>('/api/goods/list', params),

  getGoodsDetail: (id: number | string) =>
    http.get<GoodsDetail>(`/api/goods/${id}`),

  getCategoryTree: () =>
    http.get<CategoryItem[]>('/api/category/tree'),

  getGoodsReviews: (spuId: number | string, params?: { page_no?: number; page_size?: number }) =>
    http.get<PageResult<ReviewItem>>(`/api/order-review/spu/${spuId}`, params),
}
