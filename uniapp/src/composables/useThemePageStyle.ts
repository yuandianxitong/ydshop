// 主题运行时注入：page-style + 原生 nav bar
//
// 背景：
// 1) mp-weixin 的 wxss 在编译期固定，运行时无法重写 `:root, page { --color-primary }` 默认值
// 2) pages.json `navigationBarBackgroundColor` 也是编译期常量
// 3) `<page-meta>` 在 uniapp + vue3 setup 模式下绑定时序经常滞后，nav bar 首次渲染可能闪原色
//
// 双保险做法：
// - `<page-meta :page-style>` 注入 CSS 变量（page-style 在 mp-weixin 表现稳定）
// - 在 onShow + watch(themeVars) 里调 `uni.setNavigationBarColor` 实时同步原生 nav
//
// 用法（任何 tab 页 / 主流程页都建议加）：
//   <template>
//     <page-meta :page-style="themePageStyle" />
//     <view>...原内容...</view>
//   </template>
//   <script setup>
//   import { useThemePageStyle } from '@/composables/useThemePageStyle'
//   const { themePageStyle } = useThemePageStyle()
//   </script>

import { computed, watch, type ComputedRef } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/app.store'

// 标准相对亮度：>186 视为浅色，配深字；否则配白字
function isLightColor(hex: string): boolean {
    if (!hex || !hex.startsWith('#')) return false
    const h = hex.length === 4
        ? '#' + [...hex.slice(1)].map((c) => c + c).join('')
        : hex
    const r = parseInt(h.slice(1, 3), 16)
    const g = parseInt(h.slice(3, 5), 16)
    const b = parseInt(h.slice(5, 7), 16)
    return (r * 0.299 + g * 0.587 + b * 0.114) > 186
}

export function useThemePageStyle(): {
    themePageStyle: ComputedRef<string>
    navBg: ComputedRef<string>
    navText: ComputedRef<'white' | 'black'>
} {
    const appStore = useAppStore()

    const themePageStyle = computed(() =>
        Object.entries(appStore.themeVars)
            .filter(([, v]) => !!v)
            .map(([k, v]) => `${k}:${v}`)
            .join(';')
    )
    const navBg = computed(() => appStore.themeVars['--color-primary'] || '#2979ff')
    const navText = computed<'white' | 'black'>(() =>
        isLightColor(navBg.value) ? 'black' : 'white'
    )

    // 同步原生 nav bar：setNavigationBarColor 在所有端兼容性比 <page-meta> 更稳
    function applyNav() {
        uni.setNavigationBarColor({
            frontColor: navText.value === 'white' ? '#ffffff' : '#000000',
            backgroundColor: navBg.value,
            // 用 navigationStyle:"custom" 的页面（如 index/my）这里会 fail，预期忽略
            fail: () => { /* noop */ },
        })
    }

    onShow(applyNav)
    // 首次 onShow 时 getConfig 还没 resolve，themeVars 仍是默认。
    // config 到位后 watch 触发，补上一次 setNavigationBarColor
    watch(navBg, applyNav)

    return { themePageStyle, navBg, navText }
}
