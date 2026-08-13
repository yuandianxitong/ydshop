import http from '@/utils/request'
import { getToken } from '@/utils/auth'

const OA_OPENID_KEY = 'wechat_oa_openid'
const OA_BINDPENDING_KEY = 'wechat_oa_bind_pending'
const OA_NO_AUTO_KEY = 'wechat_oa_no_auto'
const OA_LAST_TRY_KEY = 'wechat_oa_last_try'

/** 授权重试冷却时间：避免授权/登录持续失败时每次刷新都跳一次微信 */
const AUTH_COOLDOWN_MS = 60 * 1000

/**
 * 网页授权 scope，需与后端换取用户信息时保持一致：
 * - snsapi_userinfo：弹授权页，可拿到微信昵称头像（要求已认证服务号）
 * - snsapi_base：静默授权，只能拿到 openid，昵称回落为「微信用户」
 * 公众号若无 snsapi_userinfo 权限（微信授权页报错），改成 snsapi_base 即可。
 */
const OAUTH_SCOPE: 'snsapi_base' | 'snsapi_userinfo' = 'snsapi_userinfo'

export function getOaOpenid(): string | null {
  return localStorage.getItem(OA_OPENID_KEY)
}

export function setOaOpenid(openid: string) {
  localStorage.setItem(OA_OPENID_KEY, openid)
}

/** 标记 oa_openid 待绑定（登录后自动关联到用户） */
export function setBindPending(pending: boolean) {
  if (pending) {
    localStorage.setItem(OA_BINDPENDING_KEY, '1')
  } else {
    localStorage.removeItem(OA_BINDPENDING_KEY)
  }
}

export function isBindPending(): boolean {
  return localStorage.getItem(OA_BINDPENDING_KEY) === '1'
}

/**
 * 主动退出登录后抑制微信自动登录，否则退出会被立刻自动登录回来。
 * 下次手动登录成功时解除。
 */
export function suppressOaAutoLogin(suppressed: boolean) {
  if (suppressed) {
    localStorage.setItem(OA_NO_AUTO_KEY, '1')
  } else {
    localStorage.removeItem(OA_NO_AUTO_KEY)
  }
}

function isOaAutoLoginSuppressed(): boolean {
  return localStorage.getItem(OA_NO_AUTO_KEY) === '1'
}

/**
 * 登录成功后调用：如果有待绑定的 oa_openid，关联到当前用户
 */
export async function bindOaOpenidAfterLogin() {
  if (!isBindPending()) return
  const openid = getOaOpenid()
  if (!openid) return

  try {
    await http.post('/api/user/bind-oa-openid', { oa_openid: openid })
    setBindPending(false)
  } catch (e) {
    console.error('绑定 oa_openid 失败', e)
  }
}

/**
 * 是否可以走微信自动登录（同步判断，供请求拦截器在 401 时决策）
 *
 * 冷却窗口用于避免授权链路持续失败时反复跳转微信。
 */
export function canWechatAutoLogin(): boolean {
  if (typeof navigator === 'undefined' || typeof localStorage === 'undefined') return false
  if (!navigator.userAgent.toLowerCase().includes('micromessenger')) return false
  if (isOaAutoLoginSuppressed()) return false
  return Date.now() - Number(localStorage.getItem(OA_LAST_TRY_KEY) || 0) >= AUTH_COOLDOWN_MS
}

/**
 * 跳转微信授权页。先同步占住冷却窗口，避免并发 401 触发多次跳转。
 */
export async function startWechatOAuth() {
  localStorage.setItem(OA_LAST_TRY_KEY, String(Date.now()))

  try {
    const result = await http.get<{ url: string }>('/api/wechat/oauth-url', {
      redirect_url: window.location.href.split('?')[0],
      scope: OAUTH_SCOPE,
    })
    if (result && result.url) {
      window.location.href = result.url
    }
  } catch (e) {
    console.error('获取 OAuth URL 失败', e)
  }
}

/**
 * H5 微信浏览器内检测并触发 OAuth 授权登录
 *
 * 流程：
 * 1. 首次进入 → 未登录 → 跳转微信授权
 * 2. 微信回调 → URL 带 code → 调后端登录接口 → 用微信身份直接建立会话（新用户后端自动注册）
 * 3. 后续访问 → 已登录则跳过；token 失效则重新静默授权（主动退出登录的除外）
 */
export async function initWechatOAuth() {
  if (typeof navigator === 'undefined') return
  const ua = navigator.userAgent.toLowerCase()
  if (!ua.includes('micromessenger')) return

  const url = new URL(window.location.href)

  // Step 2: 微信授权回调后，URL 中有 code 参数
  const code = url.searchParams.get('code')
  if (code) {
    // 清理 URL 参数（避免 code 过期后重复使用）
    url.searchParams.delete('code')
    url.searchParams.delete('state')
    window.history.replaceState({}, '', url.toString())

    try {
      // 调用 H5 微信登录接口（新用户由后端按微信资料自动注册）
      const result = await http.post<{
        status: string
        openid: string
        unionid?: string
        token?: string
        user_info?: any
      }>('/api/auth/wechat-h5-login', { code, scope: OAUTH_SCOPE })

      // 存储 openid
      if (result.openid) {
        setOaOpenid(result.openid)
      }

      if (result.token) {
        // 动态导入避免 wechat-oauth ↔ user.store 循环依赖
        const { useUserStore } = await import('@/store/user.store')
        await useUserStore().setSession(result.token, result.user_info || null)
        setBindPending(false)
      } else {
        // 后端未建立会话（例如公众号未配置完整），留待用户手动登录时补绑
        setBindPending(true)
      }
    } catch (e) {
      console.error('微信 H5 登录失败', e)
    }
    return
  }

  // Step 1: 未登录（含 token 过期）且未主动退出时发起授权
  if (getToken() || !canWechatAutoLogin()) return

  await startWechatOAuth()
}
