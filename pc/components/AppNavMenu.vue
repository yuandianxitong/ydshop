<template>
  <div class="bg-white border-b border-gray-100">
    <div class="mx-auto max-w-1200px px-4 h-12 flex items-stretch">
      <!-- 左侧：全部商品分类 砖位 -->
      <button
        class="all-cats-tile flex items-center gap-2 px-5 text-white font-medium text-sm"
        @click="goAllCategories"
      >
        <span class="i-carbon-menu text-lg" />
        全部商品分类
      </button>

      <!-- 右侧：横向导航 -->
      <nav class="flex items-center gap-1 ml-2">
        <NuxtLink
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="nav-link"
          :class="{ 'nav-link--active': isActive(item.path) }"
        >
          {{ item.label }}
        </NuxtLink>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useHeaderMenu } from '~/composables/usePcMenu'

const router = useRouter()
const route = useRoute()

const navItems = useHeaderMenu()

function isActive(path: string) {
  const cleanPath = path.split('?')[0]
  if (cleanPath === '/') return route.path === '/' || route.path === ''
  return route.path.startsWith(cleanPath)
}

function goAllCategories() {
  router.push('/category')
}
</script>

<style scoped>
.all-cats-tile {
  background: #ff4d4f;
  min-width: 210px;
  border: 0;
  cursor: pointer;
  transition: filter 0.15s;
}
.all-cats-tile:hover { filter: brightness(1.06); }

.nav-link {
  display: inline-flex;
  align-items: center;
  padding: 0 18px;
  font-size: 15px;
  color: #444;
  height: 100%;
  transition: color 0.15s;
}
.nav-link:hover { color: var(--color-primary); }
.nav-link--active {
  color: var(--color-primary);
  font-weight: 600;
}
</style>
