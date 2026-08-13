export const LOGIN_PATH = '/modules/login/pages/login'

const TAB_PATHS = new Set([
  '/pages/index/index',
  '/pages/category/index',
  '/pages/cart/index',
  '/pages/my/index',
])

type PageWithOptions = Page.PageInstance & {
  options?: Record<string, string | number | boolean | undefined>
}

function stringifyQuery(options?: PageWithOptions['options']): string {
  if (!options) return ''

  return Object.keys(options)
    .filter(key => options[key] !== undefined && options[key] !== '')
    .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(String(options[key]))}`)
    .join('&')
}

function getPageFullPath(page?: PageWithOptions): string {
  if (!page?.route) return ''

  const path = `/${page.route}`
  const query = stringifyQuery(page.options)
  return query ? `${path}?${query}` : path
}

export function getCurrentFullPath(): string {
  const pages = getCurrentPages() as PageWithOptions[]
  return getPageFullPath(pages[pages.length - 1])
}

export function isLoginPath(path: string): boolean {
  return path === LOGIN_PATH || path.startsWith(`${LOGIN_PATH}?`)
}

export function buildLoginUrl(redirect = getCurrentFullPath()): string {
  if (!redirect || isLoginPath(redirect)) return LOGIN_PATH
  return `${LOGIN_PATH}?redirect=${encodeURIComponent(redirect)}`
}

export function redirectToLogin(redirect = getCurrentFullPath(), replace = false) {
  const url = buildLoginUrl(redirect)
  if (replace) {
    uni.redirectTo({ url })
    return
  }
  uni.navigateTo({ url })
}

export function getLoginRedirect(defaultUrl = '/pages/index/index'): string {
  const pages = getCurrentPages() as PageWithOptions[]
  const current = pages[pages.length - 1]
  const raw = current?.options?.redirect
  if (!raw) return defaultUrl

  try {
    return decodeURIComponent(String(raw)) || defaultUrl
  } catch {
    return String(raw) || defaultUrl
  }
}

export function goAfterLogin(redirect = getLoginRedirect()) {
  const target = redirect || '/pages/index/index'
  const [path] = target.split('?')
  const pages = getCurrentPages() as PageWithOptions[]
  const previous = getPageFullPath(pages[pages.length - 2])

  if (previous === target) {
    uni.navigateBack()
    return
  }

  if (TAB_PATHS.has(path)) {
    uni.switchTab({ url: path })
    return
  }

  uni.redirectTo({ url: target })
}
