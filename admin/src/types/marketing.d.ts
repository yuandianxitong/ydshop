import type { PageQuery } from './common'

// ========== 优惠券 ==========
export interface CouponInfo {
    id: number
    name: string
    type: string
    value: number
    min_amount: number
    max_discount?: number
    total_count: number
    used_count: number
    per_user_limit?: number
    use_scope?: string
    scope_ids?: number[]
    start_at?: string
    end_at?: string
    status: number
    created_at?: string
    updated_at?: string
}

export interface CouponReq {
    id?: number
    name: string
    type: string
    value: number
    min_amount?: number
    max_discount?: number
    total_count: number
    per_user_limit?: number
    use_scope?: string
    scope_ids?: number[]
    start_at?: string
    end_at?: string
    status?: number
}

export interface CouponQuery extends PageQuery {
    type?: string
    status?: number
    page_size?: number
}

// ========== 满减活动 ==========
export interface FullDiscountRule {
    min_amount?: number
    value?: number
    discount?: number
    [key: string]: any
}

export interface FullDiscountInfo {
    id: number
    name: string
    type?: string
    rules?: FullDiscountRule[]
    use_scope?: string
    scope_ids?: number[]
    stackable?: boolean
    start_at?: string
    end_at?: string
    status: number
    created_at?: string
    updated_at?: string
    [key: string]: any
}

export interface FullDiscountReq {
    id?: number
    name: string
    type?: string
    rules?: FullDiscountRule[]
    use_scope?: string
    scope_ids?: number[]
    stackable?: boolean
    start_at?: string
    end_at?: string
    status?: number
    [key: string]: any
}

export interface FullDiscountQuery extends PageQuery {
    type?: string
    status?: number
}

// ========== 限时秒杀 ==========
export interface FlashSaleInfo {
    id: number
    name: string
    start_at?: string
    end_at?: string
    start_time?: string
    end_time?: string
    status: number
    items_count?: number
    item_count?: number
    created_at?: string
    updated_at?: string
    [key: string]: any
}

export interface FlashSaleReq {
    id?: number
    name: string
    start_at?: string
    end_at?: string
    start_time?: string
    end_time?: string
    status?: number
    [key: string]: any
}

export interface FlashSaleQuery extends PageQuery {
    status?: number
}

export interface FlashSaleItemInfo {
    id: number
    sale_id: number
    spu_id?: number
    spu_name?: string
    sku_id?: number
    sku_name?: string
    flash_price?: number
    flash_stock?: number
    original_price?: number
    stock?: number
    sold?: number
    sort?: number
    per_user_limit?: number
    [key: string]: any
}

export interface FlashSaleItemReq {
    spu_id?: number
    sku_id?: number
    flash_price?: number
    flash_stock?: number
    stock?: number
    sort?: number
    per_user_limit?: number
    [key: string]: any
}

// ========== 拼团活动 ==========
export interface GroupBuyInfo {
    id: number
    name: string
    spu_id?: number
    spu_name?: string
    sku_id?: number
    group_size: number
    group_price?: number
    original_price?: number
    expire_hours?: number
    time_limit?: number
    per_user_limit?: number
    start_at?: string
    end_at?: string
    start_time?: string
    end_time?: string
    status: number
    created_at?: string
    updated_at?: string
    [key: string]: any
}

export interface GroupBuyReq {
    id?: number
    name: string
    spu_id?: number
    sku_id?: number
    group_size: number
    group_price?: number
    expire_hours?: number
    time_limit?: number
    per_user_limit?: number
    start_at?: string
    end_at?: string
    status?: number
    [key: string]: any
}

export interface GroupBuyQuery extends PageQuery {
    status?: number
}

export interface GroupListQuery extends PageQuery {
    activity_id?: number
    status?: string
}

// ========== 积分商品 ==========
export interface PointsProductInfo {
    id: number
    name: string
    image?: string
    description?: string
    type?: 'physical' | 'virtual' | string
    points?: number
    points_price?: number
    cash_amount?: number
    stock: number
    sold?: number
    sort?: number
    exchange_limit?: number
    status: number
    created_at?: string
    updated_at?: string
    [key: string]: any
}

export interface PointsProductReq {
    id?: number
    name: string
    image?: string
    description?: string
    type?: 'physical' | 'virtual' | string
    points?: number
    points_price?: number
    cash_amount?: number
    stock: number
    sort?: number
    exchange_limit?: number
    status?: number
    [key: string]: any
}

export interface PointsProductQuery extends PageQuery {
    type?: string
    status?: number | string
}

// ========== 积分订单 ==========
export interface PointsOrderInfo {
    id: number
    order_no: string
    user_id: number
    user_nickname?: string
    product_id: number
    product_name?: string
    points: number
    cash_amount?: number
    quantity: number
    address?: string
    receiver?: string
    phone?: string
    express_company?: string
    express_no?: string
    status: string
    created_at: string
    updated_at?: string
}

export interface PointsOrderQuery extends PageQuery {
    status?: string
}

export interface PointsOrderShipReq {
    express_company: string
    express_no: string
}

