import type { PageQuery, PageResult } from '@/types/api'
import { myRequest } from '@/utils/request'

/** GoodsSpu 列表查询 */
export interface GoodsSpuQuery extends PageQuery {
  category_id?: number
  brand_id?: number
  status?: string
  is_recommend?: number
  is_new?: number
  is_hot?: number
}

/** 批量配送方式请求 */
export interface GoodsSpuBatchDeliveryReq {
  ids: number[]
  action: 'enable_pickup' | 'disable_pickup'
}

/** GoodsSpu 创建请求 */
export interface GoodsSpuCreateReq {
  /** 商品名称 */
  name?: string
  /** 副标题 */
  subtitle?: string
  /** 商品类型: physical/virtual/combo */
  type?: string
  /** 分类ID */
  category_id?: number
  /** 品牌ID */
  brand_id?: number
  /** 单位ID */
  unit_id?: number
  /** 运费模板ID */
  freight_template_id?: number
  /** 商品图片(JSON数组) */
  images?: string[]
  /** 排序 */
  sort?: number
  /** 是否推荐 */
  is_recommend?: number
  /** 是否新品 */
  is_new?: number
  /** 是否热卖 */
  is_hot?: number
  /** 状态: draft/on_sale/off_sale */
  status?: string
  /** 规格列表 */
  specs?: SpuSpec[]
  /** SKU列表 */
  skus?: SpuSku[]
  /** 属性列表 */
  attrs?: SpuAttr[]
  /** 商品详情(富文本) */
  detail?: string
  /** 支持的配送方式 */
  delivery_modes?: string[]
}

/** GoodsSpu 更新请求 */
export interface GoodsSpuUpdateReq extends GoodsSpuCreateReq {
  id: number
}

/** SPU规格 */
export interface SpuSpec {
  name: string
  values: string[]
}

/** SPU SKU */
export interface SpuSku {
  spec_values?: Record<string, string>
  price?: number
  cost_price?: number
  market_price?: number
  stock?: number
  weight?: number
  sku_no?: string
  image?: string
  [key: string]: any
}

/** SPU属性 */
export interface SpuAttr {
  attr_id: number
  value: string
}

/** GoodsSpu 详情 */
export interface GoodsSpuInfo {
  /** id */
  id: number
  /** SPU编号 */
  spu_no: string
  /** 商品名称 */
  name: string
  /** 副标题 */
  subtitle: string
  /** 商品类型 */
  type: string
  /** 分类ID */
  category_id: number
  /** 品牌ID */
  brand_id: number
  /** 单位ID */
  unit_id: number
  /** 运费模板ID */
  freight_template_id: number
  /** 商品图片 */
  images: string[]
  /** 最低价 */
  min_price: number
  /** 最高价 */
  max_price: number
  /** 总库存 */
  total_stock: number
  /** 销量 */
  sales_count: number
  /** 排序 */
  sort: number
  /** 是否推荐 */
  is_recommend: number
  /** 是否新品 */
  is_new: number
  /** 是否热卖 */
  is_hot: number
  /** 状态 */
  status: string
  /** 商品详情 */
  detail: string
  /** 支持的配送方式 */
  delivery_modes?: string[]
  /** 创建时间 */
  created_at: string
  /** 更新时间 */
  updated_at: string
  /** 规格列表 */
  specs?: SpuSpec[]
  /** SKU列表 */
  skus?: SpuSku[]
  /** 属性列表 */
  attrs?: SpuAttr[]
}

/**
 * GoodsSpu 管理API
 */
export const goodsSpuApi = {
    /** 获取列表 */
    getList(params: GoodsSpuQuery) {
        return myRequest.get<PageResult<GoodsSpuInfo>>('/adminapi/goods/goods-spu', { params })
    },

    /** 获取详情 */
    getDetail(id: number) {
        return myRequest.get<GoodsSpuInfo>(`/adminapi/goods/goods-spu/${id}`)
    },

    /** 选择器水合：按 IDs 取轻量字段（id/name/thumb/min_price/status），顺序与入参一致 */
    getByIds(ids: number[]) {
        return myRequest.get<Array<{
            id: number
            name: string
            thumb?: string
            min_price?: number | string
            status: string
        }>>('/adminapi/goods/goods-spu/by-ids', { params: { ids: ids.join(',') } })
    },

    /** SKU 选择器水合：按 IDs 取轻量字段（id/spu_id/sku_no/spec_text/image/price），顺序与入参一致 */
    getSkusByIds(ids: number[]) {
        return myRequest.get<Array<{
            id: number
            spu_id: number
            sku_no: string
            spec_text: string
            image?: string
            price?: number | string
        }>>('/adminapi/goods/goods-spu/skus/by-ids', { params: { ids: ids.join(',') } })
    },

    /** 创建 */
    create(data: GoodsSpuCreateReq) {
        return myRequest.post<void>('/adminapi/goods/goods-spu', data)
    },

    /** 更新 */
    update(id: number, data: Partial<GoodsSpuCreateReq>) {
        return myRequest.put<void>(`/adminapi/goods/goods-spu/${id}`, data)
    },

    /**
     * 删除
     */
    delete(id: number) {
        return myRequest.delete(`/adminapi/goods/goods-spu/${id}`)
    },

    /**
     * 更新状态
     */
    updateStatus(id: number, data: { status: string }) {
        return myRequest.put(`/adminapi/goods/goods-spu/${id}/status`, data)
    },

    /**
     * 批量上架
     */
    batchOnSale(data: { ids: number[] }) {
        return myRequest.post('/adminapi/goods/goods-spu/batch-on-sale', data)
    },

    /**
     * 批量下架
     */
    batchOffSale(data: { ids: number[] }) {
        return myRequest.post('/adminapi/goods/goods-spu/batch-off-sale', data)
    },

    /**
     * 批量更新配送方式（enable_pickup / disable_pickup）
     */
    batchDelivery(data: GoodsSpuBatchDeliveryReq) {
        return myRequest.put('/adminapi/goods/goods-spu/batch-delivery', data)
    },

    /**
     * 批量导入：模板下载 URL（直接 <a> 跳转或 window.open 打开）
     */
    importTemplateUrl: '/adminapi/goods/goods-spu/import/template',

    /**
     * 批量导入：上传文件 → 校验预览
     */
    importPreview(file: File) {
        const fd = new FormData()
        fd.append('file', file)
        return myRequest.post('/adminapi/goods/goods-spu/import/preview', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
    },

    /**
     * 批量导入：确认导入（基于校验阶段保留的服务端临时文件）
     */
    importConfirm(data: { file_path: string }) {
        return myRequest.post('/adminapi/goods/goods-spu/import/confirm', data)
    },
}
