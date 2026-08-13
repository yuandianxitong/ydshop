<template>
  <div class="bg-[#1a1a1a] text-gray-300 text-xs">
    <div class="mx-auto max-w-1200px px-4 h-8 flex items-center justify-between gap-6">
      <!-- 左侧：公告跑马灯 -->
      <div class="flex items-center gap-2 min-w-0 flex-1">
        <span class="i-carbon-notification text-gray-400 flex-shrink-0" />
        <span class="text-gray-400 flex-shrink-0">公告：</span>
        <div class="topbar-marquee-mask flex-1 min-w-0 overflow-hidden">
          <div v-if="announcements.length" class="topbar-marquee flex gap-12 whitespace-nowrap">
            <NuxtLink
              v-for="item in announcementsLoop"
              :key="item.key"
              :to="`/announcement/${item.id}`"
              class="hover:text-white transition-colors"
            >{{ item.title }}</NuxtLink>
          </div>
          <span v-else class="text-gray-500">暂无公告</span>
        </div>
      </div>

      <!-- 右侧：登录态 / 快捷入口 -->
      <div class="flex items-center gap-3 flex-shrink-0">
        <template v-if="userStore.isLoggedIn">
          <span class="text-gray-400">Hi · {{ userStore.userInfo?.nickname || '亲' }}</span>
          <span class="text-gray-700">|</span>
          <NuxtLink to="/order" class="hover:text-white transition-colors">我的订单</NuxtLink>
          <span class="text-gray-700">|</span>
          <NuxtLink to="/user" class="hover:text-white transition-colors">个人中心</NuxtLink>
          <span class="text-gray-700">|</span>
          <button class="hover:text-white transition-colors" @click="handleLogout">退出</button>
        </template>
        <template v-else>
          <NuxtLink to="/login" class="hover:text-white transition-colors">登录</NuxtLink>
          <span class="text-gray-700">|</span>
          <NuxtLink to="/register" class="hover:text-white transition-colors">注册</NuxtLink>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useUserStore } from '~/store/user'
import { get } from '~/composables/useRequest'

const userStore = useUserStore()
const router = useRouter()

const announcements = ref<{ id: number; title: string }[]>([])
// 跑马灯需要至少两份数据无缝拼接才能循环
const announcementsLoop = computed(() => {
  const list = announcements.value
  if (!list.length) return []
  return [...list, ...list].map((a, idx) => ({ ...a, key: `${a.id}-${idx}` }))
})

async function fetchAnnouncements() {
  try {
    const res = await get<{ list: { id: number; title: string }[] }>(
      '/api/announcement/list',
      { page_no: 1, page_size: 8 },
      false,
    )
    if (res.code === 200) {
      announcements.value = res.data.list || []
    }
  } catch {
    /* silent */
  }
}

async function handleLogout() {
  await userStore.logout()
  router.push('/')
}

onMounted(() => {
  fetchAnnouncements()
  // 进入站点时若已登录但还没拉过 userInfo，主动拉一次（用于顶栏显示昵称）
  if (userStore.isLoggedIn && !userStore.userInfo) {
    userStore.fetchUserInfo().catch(() => { /* silent */ })
  }
})
</script>

<style scoped>
/* 跑马灯：内容总宽 = 2 倍原始列表（announcementsLoop 拼接了两遍），
   动画从 0 滚到 -50%，正好滚过一份后回到起点，视觉上无缝 */
.topbar-marquee {
  animation: topbar-marquee-scroll 35s linear infinite;
  will-change: transform;
}
.topbar-marquee-mask:hover .topbar-marquee {
  animation-play-state: paused;
}
@keyframes topbar-marquee-scroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
</style>
