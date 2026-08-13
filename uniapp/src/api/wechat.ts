import http from '@/utils/request'

export interface MiniCodeResult {
  /** data:image/png;base64,... 直接给 <image src> */
  qrcode: string
  scene: string
  page: string
}

export const wechatApi = {
  /**
   * 生成微信小程序码（永久，wxacodeunlimit）
   * @param scene  ≤ 32 字符；这里通常用 invite_code
   * @param page   不带前导 / 的小程序页面路径，如 'pages/index/index'
   */
  getMiniCode: (params: { scene: string; page?: string; width?: number }) =>
    http.get<MiniCodeResult>('/api/wechat/mini-code', params),
}
