import http from '@/utils/request'
import type { LoginResult, UserInfo } from '@/types/api'

export interface WechatQuickLoginResult {
  status: 'logged_in' | 'need_bindphone'
  token?: string
  need_profile?: boolean
  temp_token?: string
  user_info?: UserInfo
}

export interface WechatBindPhoneResult {
  status: 'logged_in'
  token?: string
  need_profile?: boolean
  mobile?: string
  user_info?: UserInfo
}

export const authApi = {
  login: (data: { mobile: string; password: string }) =>
    http.post<LoginResult>('/api/auth/login', data),

  smsLogin: (data: { mobile: string; code: string }) =>
    http.post<LoginResult>('/api/auth/sms-login', data),

  sendSmsCode: (data: { mobile: string; scene?: string }) =>
    http.post('/api/common/sms-code', data),

  wechatMiniLogin: (data: { code: string }) =>
    http.post<LoginResult>('/api/auth/wechat-login', data),

  register: (data: { mobile: string; code: string; password: string; password_confirmation: string }) =>
    http.post<LoginResult>('/api/auth/register', data),

  refreshToken: () =>
    http.post<{ token: string }>('/api/auth/refresh-token'),

  getUserInfo: () =>
    http.get<UserInfo>('/api/auth/info'),

  logout: () =>
    http.post('/api/auth/logout'),

  wechatQuickLogin(data: { code: string }) {
    return http.post<WechatQuickLoginResult>('/api/auth/wechat-quick-login', data)
  },
  wechatBindPhone(data: { temp_token: string; phone_code: string; nickname?: string; avatar?: string }) {
    return http.post<WechatBindPhoneResult>('/api/auth/wechat-bindphone', data)
  },
}
