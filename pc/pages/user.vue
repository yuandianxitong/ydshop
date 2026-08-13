<template>
  <div class="mx-auto max-w-1200px px-6 py-8">
    <div class="flex gap-6">
      <!-- Sidebar -->
      <aside class="w-60 flex-shrink-0">
        <!-- User info card -->
        <div class="card p-5 mb-4">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-full bg-[var(--color-primary)] text-white flex items-center justify-center text-lg font-bold flex-shrink-0">
              {{ userInitial }}
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-gray-900 truncate">{{ userInfo?.nickname || '用户' }}</div>
              <div class="text-xs text-gray-400 mt-0.5">{{ userInfo?.mobile || '' }}</div>
            </div>
          </div>
          <!-- Balance & Points stats -->
          <div class="flex border-t border-gray-100 pt-3">
            <NuxtLink to="/user/balance" class="flex-1 text-center hover:opacity-80 transition-opacity">
              <div class="text-lg font-bold text-amber-600">{{ balance }}</div>
              <div class="text-xs text-gray-400 mt-0.5">余额</div>
            </NuxtLink>
            <div class="w-px bg-gray-100" />
            <NuxtLink to="/user/points" class="flex-1 text-center hover:opacity-80 transition-opacity">
              <div class="text-lg font-bold text-indigo-600">{{ points }}</div>
              <div class="text-xs text-gray-400 mt-0.5">积分</div>
            </NuxtLink>
          </div>
        </div>

        <!-- Navigation menu -->
        <div class="card py-2 overflow-hidden">
          <div v-for="group in menuGroups" :key="group.label" class="account-nav-group">
            <div class="px-5 pt-3 pb-1 text-[11px] font-semibold tracking-widest text-gray-400 uppercase">
              {{ group.label }}
            </div>
            <NuxtLink
              v-for="item in group.items"
              :key="item.path"
              :to="item.path"
              class="flex items-center px-5 py-3 text-sm transition-colors"
              :class="isActive(item.path) ? 'text-white! bg-[var(--color-primary)] font-medium' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
            >
              <span :class="[item.icon, 'mr-2.5 text-base']" />
              {{ item.label }}
            </NuxtLink>
          </div>
        </div>
      </aside>

      <!-- Main content -->
      <div class="flex-1 min-w-0">
        <NuxtPage />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { userApi } from '~/api/user'
import { useAppStore } from '~/store/app'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const appStore = useAppStore()

const userInfo = ref<{ nickname: string; mobile: string; avatar: string } | null>(null)
const balance = ref('0.00')
const points = ref(0)

const hasDistribution = computed(() => {
  const plugins = appStore.config?.installed_plugins
  return Array.isArray(plugins) && plugins.includes('distribution')
})

const menuGroups = computed(() => [
  {
    label: '我的商城',
    items: [
      { label: '个人资料', path: '/user/profile', icon: 'i-carbon-user-avatar' },
      { label: '收货地址', path: '/user/address', icon: 'i-carbon-location' },
      { label: '我的收藏', path: '/user/favorites', icon: 'i-carbon-favorite' },
      { label: '消息通知', path: '/user/notifications', icon: 'i-carbon-notification' },
    ],
  },
  {
    label: '资产与权益',
    items: [
      { label: '我的余额', path: '/user/balance', icon: 'i-carbon-wallet' },
      { label: '我的积分', path: '/user/points', icon: 'i-carbon-gift' },
      { label: '每日签到', path: '/user/sign', icon: 'i-carbon-calendar-heat-map' },
      ...(hasDistribution.value
        ? [{ label: '分销中心', path: '/user/distribution', icon: 'i-carbon-network-4' }]
        : []),
    ],
  },
  {
    label: '服务与安全',
    items: [
      { label: '我的售后', path: '/order/refund-list', icon: 'i-carbon-task' },
      { label: '意见反馈', path: '/user/feedback', icon: 'i-carbon-chat' },
      { label: '账号安全', path: '/user/security', icon: 'i-carbon-security' },
    ],
  },
])

const userInitial = computed(() => {
  const name = userInfo.value?.nickname || '用'
  return name.charAt(0).toUpperCase()
})

function isActive(path: string) {
  return route.path === path || route.path.startsWith(path + '/')
}

async function refreshUserInfo() {
  try {
    const [profileRes, balanceRes, pointsRes] = await Promise.all([
      userApi.getProfile(),
      userApi.getBalance(),
      userApi.getPoints(),
    ])
    if (profileRes.code === 200) {
      userInfo.value = profileRes.data
    }
    if (balanceRes.code === 200) {
      balance.value = balanceRes.data.balance
    }
    if (pointsRes.code === 200) {
      points.value = pointsRes.data.points
    }
  } catch {
    // ignore errors silently
  }
}

provide('refreshUserInfo', refreshUserInfo)

onMounted(() => {
  refreshUserInfo()
})
</script>
