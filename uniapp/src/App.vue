<script setup lang="ts">
import { onLaunch } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/app.store'

onLaunch(async () => {
  // 必须先于 await 同步隐藏原生 tabbar，否则会闪一下；pages.json 加了 custom:true 后
  // mp-weixin 已不会渲染原生 tabbar，此处再调 hideTabBar 会 fail（"hideTabBar:fail custom Tabbar"），
  // 用 fail/success 回调静默吞掉，不让它进控制台。H5/App 端仍走真正的隐藏路径
  uni.hideTabBar({
    animation: false,
    fail: () => { /* custom:true 模式下预期会 fail，忽略 */ },
  })

  const appStore = useAppStore()
  await appStore.getConfig().catch(() => {})

  // #ifdef H5
  import('@/utils/wechat-oauth').then(({ initWechatOAuth }) => {
    initWechatOAuth()
  })
  // #endif
})
</script>

<style lang="scss">
// tokens 由 reset.scss / uview-overrides.scss / 各 d-* 组件通过 @use './tokens'
// 间接加载（包括 :root,page CSS var 默认值），App.vue 不再显式 @import 以避免
// 与 @use 模块系统冲突（重复 $color-primary 定义）
@import './styles/reset.scss';
@import './styles/common.scss';
@import './styles/uview-overrides.scss';
</style>
