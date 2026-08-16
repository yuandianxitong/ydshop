import { ofetch } from 'ofetch'

const TOKEN_KEY = 'pc_token'
const USER_KEY = 'pc_user'

/** 登录刚写入时优先走内存，避免只读 localStorage / import.meta.server 漏带 Authorization */
let memoryToken = ''

export function getToken(): string | null {
  if (memoryToken) return memoryToken
  if (import.meta.server) return null
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string) {
  memoryToken = normalizeJwt(token) || token.trim()
  if (import.meta.client) {
    localStorage.setItem(TOKEN_KEY, memoryToken)
  }
}

export function removeToken() {
  memoryToken = ''
  if (import.meta.client) {
    localStorage.removeItem(TOKEN_KEY)
  }
}

export function getCachedUser<T = Record<string, unknown>>(): T | null {
  if (import.meta.server) return null
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? JSON.parse(raw) as T : null
  } catch {
    return null
  }
}

export function setCachedUser(user: unknown) {
  if (!import.meta.client || user == null) return
  localStorage.setItem(USER_KEY, JSON.stringify(user))
}

export function removeCachedUser() {
  if (import.meta.client) {
    localStorage.removeItem(USER_KEY)
  }
}

function resolveToken(): string {
  return normalizeJwt(getToken() || '')
}

/** 去掉 Bearer 前缀、重复拼接，只保留三段式 JWT */
function normalizeJwt(raw: string): string {
  let token = raw.trim()
  if (!token) return ''
  while (/^bearer\s+/i.test(token)) {
    token = token.replace(/^bearer\s+/i, '').trim()
  }
  if (token.includes(',')) {
    token = token.split(',')[0].trim()
    while (/^bearer\s+/i.test(token)) {
      token = token.replace(/^bearer\s+/i, '').trim()
    }
  }
  return token.split('.').length === 3 ? token : ''
}

function readHeader(headers: HeadersInit | undefined, name: string): string {
  if (!headers) return ''
  const target = name.toLowerCase()
  if (headers instanceof Headers) {
    return headers.get(name) || ''
  }
  if (Array.isArray(headers)) {
    const hit = headers.find(([key]) => key.toLowerCase() === target)
    return hit ? String(hit[1]) : ''
  }
  for (const [key, value] of Object.entries(headers)) {
    if (key.toLowerCase() === target && value !== undefined) {
      return String(value)
    }
  }
  return ''
}

function hasBearer(headers?: HeadersInit): boolean {
  return /^bearer\s+\S+/i.test(readHeader(headers, 'Authorization'))
}

/**
 * 用 Headers.set 写请求头，避免 Authorization / authorization 同时存在时
 * 被合成 "Bearer jwt, Bearer jwt"（JWT 会报 Wrong number of segments）。
 */
function applyRequestHeaders(base?: HeadersInit): Headers {
  const headers = new Headers()
  if (base instanceof Headers) {
    base.forEach((value, key) => {
      if (key.toLowerCase() !== 'authorization') {
        headers.set(key, value)
      }
    })
  } else if (Array.isArray(base)) {
    for (const [key, value] of base) {
      if (key.toLowerCase() !== 'authorization') {
        headers.set(key, value)
      }
    }
  } else if (base) {
    for (const [key, value] of Object.entries(base)) {
      if (value !== undefined && key.toLowerCase() !== 'authorization') {
        headers.set(key, String(value))
      }
    }
  }
  const token = resolveToken()
  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }
  headers.set('X-Client-Type', 'pc')
  return headers
}

// 公开页面：游客可自由浏览，401 绝不能清掉刚写入的登录态
const PUBLIC_PATH_PATTERNS: RegExp[] = [
  /^\/$/,
  /^\/login(\/|$)/,
  /^\/register(\/|$)/,
  /^\/goods(\/|$)/,
  /^\/category(\/|$)/,
  /^\/search(\/|$)/,
  /^\/article(\/|$)/,
  /^\/announcement(\/|$)/,
  /^\/help(\/|$)/,
  /^\/marketing(\/|$)/,
  /^\/diy(\/|$)/,
]

function isPublicPath(appPath: string): boolean {
  return PUBLIC_PATH_PATTERNS.some((p) => p.test(appPath))
}

const BASE_PATH = '/pc'

function stripBase(pathname: string): string {
  if (pathname === BASE_PATH || pathname === `${BASE_PATH}/`) return '/'
  if (pathname.startsWith(`${BASE_PATH}/`)) return pathname.slice(BASE_PATH.length) || '/'
  return pathname
}

function redirectToLogin() {
  if (!import.meta.client) return
  const appPath = stripBase(window.location.pathname)
  const { search } = window.location
  if (appPath === '/login') return
  if (isPublicPath(appPath)) return
  navigateTo(`/login?redirect=${encodeURIComponent(appPath + search)}`)
}

function shouldClearSession(headers?: HeadersInit): boolean {
  if (!import.meta.client) return false
  if (!hasBearer(headers)) return false
  return !isPublicPath(stripBase(window.location.pathname))
}

function clearSession() {
  removeToken()
  removeCachedUser()
  if (!import.meta.client) return
  import('~/store/user').then(({ useUserStore }) => {
    useUserStore().clearSession()
  }).catch(() => { /* pinia 尚未就绪时忽略 */ })
}

export const request = ofetch.create({
  onRequest({ options }) {
    options.headers = applyRequestHeaders(options.headers as HeadersInit | undefined)
  },

  onResponseError({ response, options }) {
    const skipAuthClear = Boolean((options as { _skipAuthClear?: boolean })._skipAuthClear)
    if (response.status === 401 && !skipAuthClear && shouldClearSession(options.headers as HeadersInit | undefined)) {
      clearSession()
      redirectToLogin()
    }
  },
})

export interface ApiResponse<T = any> {
  code: number
  message: string
  data: T
}

export interface Pagination {
  current_page: number
  per_page: number
  last_page: number
  total: number
}

export interface PageResult<T> {
  list: T[]
  pagination: Pagination
}

function normalizePaginationParams(params?: Record<string, any>): Record<string, any> | undefined {
  if (!params) return params
  const normalized = { ...params }
  if (normalized.page === undefined && normalized.page_no !== undefined) {
    normalized.page = normalized.page_no
  } else if (normalized.page_no === undefined && normalized.page !== undefined) {
    normalized.page_no = normalized.page
  }
  if (normalized.limit === undefined && normalized.page_size !== undefined) {
    normalized.limit = normalized.page_size
  } else if (normalized.page_size === undefined && normalized.limit !== undefined) {
    normalized.page_size = normalized.limit
  }
  return normalized
}

/**
 * 后端鉴权失败是 HTTP 200 + body.code=401，不会走 onResponseError。
 * 只有「本次请求确实带了 Bearer」且当前不在公开页时才清会话。
 */
async function handleResponse<T>(
  promise: Promise<ApiResponse<T>>,
  showError = true,
  headers?: HeadersInit,
): Promise<ApiResponse<T>> {
  const res = await promise
  if (res.code === 401 && shouldClearSession(headers)) {
    clearSession()
    redirectToLogin()
    return res
  }
  if (res.code !== 200 && showError && import.meta.client) {
    import('naive-ui').then(({ createDiscreteApi }) => {
      const { message } = createDiscreteApi(['message'])
      message.error(res.message || '请求失败')
    }).catch(() => {
      console.error(res.message || '请求失败')
    })
  }
  return res
}

function call<T>(url: string, options: Record<string, unknown>, showError = true) {
  const headers = applyRequestHeaders()
  return handleResponse<T>(
    request<ApiResponse<T>>(url, {
      ...options,
      ...(!showError ? { _skipAuthClear: true } : {}),
    }),
    showError,
    headers,
  )
}

export function get<T = any>(url: string, params?: Record<string, any>, showError = true): Promise<ApiResponse<T>> {
  return call<T>(url, { method: 'GET', params: normalizePaginationParams(params) }, showError)
}

export function post<T = any>(url: string, body?: Record<string, any>, showError = true): Promise<ApiResponse<T>> {
  return call<T>(url, { method: 'POST', body }, showError)
}

export function put<T = any>(url: string, body?: Record<string, any>, showError = true): Promise<ApiResponse<T>> {
  return call<T>(url, { method: 'PUT', body }, showError)
}

export function del<T = any>(url: string, body?: Record<string, any>, showError = true): Promise<ApiResponse<T>> {
  return call<T>(url, { method: 'DELETE', body }, showError)
}
