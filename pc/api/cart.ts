import { get, post, put, del } from '~/composables/useRequest'

/** 后端 MemberCartService 在 toArray 上 enrich 出来的字段：
 *  原表字段：id / user_id / sku_id / quantity / selected / created_at / updated_at
 *  附加字段：spu_name / image / spec_text / price / stock / delivery_modes / sku_status / spu_status
 *  with(['sku.spu']) 让 sku.spu 内嵌可访问（用于取 spu_id 跳详情）
 */
export interface CartItem {
  id: number
  user_id: number
  sku_id: number
  quantity: number
  selected: number
  created_at: string
  updated_at?: string

  spu_name: string
  image: string
  spec_text: string
  price: number
  stock: number
  delivery_modes: string[]
  sku_status: number
  spu_status: string

  sku?: { spu?: { id: number; name?: string } | null } | null
}

export const cartApi = {
  getCartList: () =>
    get<CartItem[]>('/api/cart'),

  addToCart: (data: { sku_id: number; quantity: number }) =>
    post('/api/cart/add', data),

  updateCartItem: (id: number | string, data: { quantity: number }) =>
    put(`/api/cart/${id}`, data),

  removeCartItem: (id: number | string) =>
    del(`/api/cart/${id}`),

  toggleSelectItem: (id: number | string) =>
    post(`/api/cart/${id}/toggle-select`),

  selectAllItems: (data: { selected: number }) =>
    post('/api/cart/select-all', data),

  getSelectedItems: () =>
    get<CartItem[]>('/api/cart/selected'),
}
