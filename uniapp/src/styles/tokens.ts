// uniapp/src/styles/tokens.ts
//
// SCSS tokens 的 TypeScript 镜像
// 用途：JS 侧（echarts 主题、canvas 海报、动态 inline style 等）需要拿到 token 值时使用
// 与 tokens.scss 保持同步

// ============== Brand 默认值（运行时被后台覆盖） ==============
export const brandTokens = {
  primary: '#2979ff',
  auxiliary: '#ff9900',
  text: '#18181b',
  bg: '#f5f5f5',
} as const

// ============== Semantic ==============
export const semanticTokens = {
  success: '#10b981',
  warning: '#f59e0b',
  danger: '#ef4444',
  info: '#3b82f6',
  text1: '#18181b',
  text2: '#71717a',
  text3: '#a1a1aa',
  bg1: '#ffffff',
  bg2: '#fafafa',
  bg3: '#f4f4f5',
  border1: '#e4e4e7',
  border2: '#f4f4f5',
} as const

// ============== Scale ==============
export const scaleTokens = {
  radius: { sm: '8rpx', md: '16rpx', lg: '24rpx', xl: '32rpx', '2xl': '48rpx' },
  space: {
    1: '8rpx', 2: '16rpx', 3: '24rpx', 4: '32rpx',
    5: '40rpx', 6: '48rpx', 7: '64rpx', 8: '96rpx',
  },
  font: {
    xs: '20rpx', sm: '24rpx', base: '28rpx', md: '32rpx',
    lg: '36rpx', xl: '40rpx', '2xl': '48rpx',
  },
  lineHeight: { tight: 1.1, snug: 1.25, normal: 1.5, relaxed: 1.75 },
  shadow: {
    sm: '0 2rpx 4rpx rgba(0, 0, 0, 0.04)',
    md: '0 4rpx 12rpx rgba(0, 0, 0, 0.06)',
    lg: '0 12rpx 36rpx rgba(0, 0, 0, 0.12)',
  },
  duration: { fast: 150, base: 250, slow: 400 },
} as const

/**
 * 读取当前生效的 Brand token（如果后台主题已注入则返回后台值，否则返回默认值）
 * 需要 useAppStore 已就绪
 */
export function getCurrentBrandTokens(themeVars?: Record<string, string>) {
  if (!themeVars) return brandTokens
  return {
    primary: themeVars['--color-primary'] || brandTokens.primary,
    auxiliary: themeVars['--color-auxiliary'] || brandTokens.auxiliary,
    text: themeVars['--color-text'] || brandTokens.text,
    bg: themeVars['--color-bg'] || brandTokens.bg,
  }
}
