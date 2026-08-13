import http from '@/utils/request'

// 与后端 MemberCartService 返回字段对齐
// spu_name / image / spec_text 命名与项目其它模块（OrderItem / Favorite / AI）一致
export interface CartItem {
  id: number
  user_id: number
  sku_id: number
  spu_id: number
  spu_name: string       // 商品（SPU）名
  image: string          // SKU 主图（fallback 到 SPU 第一张）
  spec_text: string      // 规格文本（如 "颜色:红 / 尺寸:M"）
  price: number
  quantity: number
  selected: number
  stock: number
  sku_status?: number
  spu_status?: string
  delivery_modes?: string[]  // 商品支持的配送方式（后端 v2.4.0 起返回，如 ['express','pickup']）
  created_at: string
}

export const cartApi = {
  getCartList: () =>
    http.get<CartItem[]>('/api/cart'),

  addToCart: (data: { sku_id: number; quantity: number }) =>
    http.post('/api/cart/add', data),

  updateCartItem: (id: number | string, data: { quantity: number }) =>
    http.put(`/api/cart/${id}`, data),

  removeCartItem: (id: number | string) =>
    http.delete(`/api/cart/${id}`),

  toggleSelectItem: (id: number | string) =>
    http.post(`/api/cart/${id}/toggle-select`),

  selectAllItems: (data: { selected: number }) =>
    http.post('/api/cart/select-all', data),

  getSelectedItems: () =>
    http.get<CartItem[]>('/api/cart/selected'),
}
