// uniapp/src/styles/theme-vars.ts
//
// 三端主题注入器
// - H5：写入 document.documentElement.style
// - 小程序 / App-vue：通过动态 <style> 标签注入到 page 选择器
//
// 调用：useAppStore().applyThemeVars()

interface ThemeVars {
  '--color-primary': string
  '--color-auxiliary': string
  '--color-text': string
  '--color-bg': string
}

/**
 * H5：documentElement.style 直接 set（最快、与 CSS 优先级一致）
 */
function applyH5(vars: ThemeVars) {
  // #ifdef H5
  Object.entries(vars).forEach(([key, val]) => {
    document.documentElement.style.setProperty(key, val)
  })
  // #endif
}

/**
 * 小程序 / App-vue：拼接 CSS 字符串，注入到全局 <style id="d-theme">
 *
 * 微信小程序 SDK 2.7+ 起支持 page 元素的 CSS 变量。
 * 我们用 selectorQuery 找到 head（仅 H5）或 page 节点不可行，
 * 需要走 uni.createSelectorQuery + page 内联 style 路径。
 *
 * 实践中：在 App.vue 顶层 <style> 已用 :root,page {...} 兜底默认值，
 * 此处只需用 setStorageSync 把 vars 存起来，由 App.vue / 各页 onShow
 * 通过 currentPage 的 setStyle / setData 同步。但 uniapp 不支持直接给 page
 * 设置 inline style；折中方案：写入 globalData，每页 onLoad 时 setData 到根节点。
 *
 * 最稳的方案：把品牌色挂到 uni.$globalThemeVars，每个 d-* 组件根节点
 * 用 :style 绑定。本工程组件已经大量使用 var(--*)，故采用更简方案：
 * 用动态注入 <style> 元素（H5）+ 微信小程序 setBackgroundColor / setTextStyle
 * 等原生 API 配合 page-meta 写入。
 */
function applyMpAndApp(vars: ThemeVars) {
  // #ifdef MP-WEIXIN || MP-ALIPAY || MP-BAIDU || APP-PLUS
  // 写入 globalData，供组件 :style 兜底使用
  const app = getApp<{ globalData: { themeVars: ThemeVars } }>()
  if (app) {
    if (!app.globalData) app.globalData = { themeVars: vars }
    else app.globalData.themeVars = vars
  }
  // 持久化（onLaunch 之前的页面初始化用）
  try {
    uni.setStorageSync('d-theme-vars', vars)
  } catch (e) {
    console.warn('[theme-vars] 主题持久化失败:', e)
  }
  // #endif
}

/**
 * 应用主题变量到当前文档
 * 三端通用入口
 */
export function applyThemeVars(vars: ThemeVars) {
  applyH5(vars)
  applyMpAndApp(vars)
}

/**
 * 读取已持久化的主题变量（小程序 / App 启动早期使用）
 * 注：H5 平台无对应实现，始终返回 null
 */
export function readPersistedThemeVars(): ThemeVars | null {
  // 单一 return 路径，避免 uniapp 条件编译指令让 TS 误判 unreachable
  let result: ThemeVars | null = null
  // #ifdef MP-WEIXIN || MP-ALIPAY || MP-BAIDU || APP-PLUS
  try {
    result = uni.getStorageSync('d-theme-vars') || null
  } catch {
    result = null
  }
  // #endif
  return result
}

/**
 * 给 d-* 组件根节点用的 :style 绑定值
 * 用法：<view :style="useThemeStyle()">
 *
 * 用于小程序 / App-vue：因 page CSS var 部分老版本基础库不生效，
 * 在组件根上写一份兜底 inline style。
 */
export function useThemeStyle(): Record<string, string> {
  // 单一 return 路径，避免 uniapp 条件编译指令让 TS 误判 unreachable
  let result: Record<string, string> = {}
  // #ifdef MP-WEIXIN || MP-ALIPAY || MP-BAIDU || APP-PLUS
  const app = getApp<{ globalData?: { themeVars?: ThemeVars } }>()
  result = (app?.globalData?.themeVars as unknown as Record<string, string>) || {}
  // #endif
  return result
}
