<template>
  <image
    v-if="dataUrl"
    :src="dataUrl"
    mode="aspectFit"
    :style="{ width: size, height: size }"
    class="d-icon"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ICONS } from './icons'

const props = withDefaults(
  defineProps<{
    name: string         // 例：'home'、'cart-fill'、'arrow-left'（见 icons.ts 注册表）
    size?: string        // 例：'44rpx'，默认 40rpx
    color?: string       // 例：'#2979ff' / 'rgba(0,0,0,0.6)'。**不能用 var(--*)**——SVG 内不解析 CSS 变量
                         // 默认 #71717a（与 --color-text-3 一致），需要主色等场景调用方传具体值
  }>(),
  {
    size: '40rpx',
    color: '#71717a',
  }
)

// 缓存：避免每次 render 重新生成 base64
const cache = new Map<string, string>()

const dataUrl = computed(() => {
  const key = `${props.name}|${props.color}`
  if (cache.has(key)) return cache.get(key)!

  const icon = ICONS[props.name]
  if (!icon) {
    console.warn(`[d-icon] icon "${props.name}" not in registry. 在 components/d-icon/icons.ts 注册`)
    return ''
  }

  // 把 currentColor / fill 替换为指定颜色
  const body = icon.body.replace(/currentColor/g, props.color)
  // viewBox 用 width/height 即可（默认 24x24）
  const w = icon.width || 24
  const h = icon.height || 24
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}">${body}</svg>`

  // encodeURIComponent 比 base64 更友好（H5 / mp / App 三端 image src 都接受 data:image/svg+xml,...）
  const url = `data:image/svg+xml,${encodeURIComponent(svg)}`
  cache.set(key, url)
  return url
})
</script>

<style lang="scss" scoped>
.d-icon {
  display: inline-block;
  vertical-align: middle;
}
</style>
