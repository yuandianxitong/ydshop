<template>
  <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
    <div class="mx-auto max-w-1200px px-4 h-[72px] flex items-center gap-8">
      <!-- 左侧 Logo -->
      <NuxtLink to="/" class="flex items-center gap-2 flex-shrink-0">
        <span class="text-2xl font-bold text-[var(--color-primary)] tracking-tight">元点Shop</span>
      </NuxtLink>

      <!-- 中间留白 + 推开 -->
      <div class="flex-1" />

      <!-- 右侧：搜索 + 购物车 -->
      <form class="search-box flex items-center" @submit.prevent="onSearch">
        <input
          v-model="keyword"
          type="text"
          placeholder="搜索商品"
          class="search-input"
          maxlength="50"
        />
        <button type="submit" class="search-btn">
          <span class="i-carbon-search text-base" />
          <span class="ml-1 text-sm">搜索</span>
        </button>
      </form>

      <NuxtLink to="/cart" class="cart-link relative flex items-center gap-1.5 text-sm text-gray-700 hover:text-[var(--color-primary)] transition-colors">
        <span class="i-carbon-shopping-cart text-xl" />
        <span>购物车</span>
        <span
          v-if="cartCount > 0"
          class="cart-badge"
        >{{ cartCount > 99 ? '99+' : cartCount }}</span>
      </NuxtLink>
    </div>
  </header>
</template>

<script setup lang="ts">
import { useUserStore } from '~/store/user'
import { cartApi } from '~/api/cart'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const keyword = ref((route.query.keyword as string) || '')
const cartCount = ref(0)

function onSearch() {
  const k = keyword.value.trim()
  router.push({ path: '/goods', query: k ? { keyword: k } : {} })
}

async function fetchCartCount() {
  if (!userStore.isLoggedIn) {
    cartCount.value = 0
    return
  }
  try {
    const res = await cartApi.getCartList()
    if (res.code === 200) {
      cartCount.value = Array.isArray(res.data) ? res.data.length : 0
    }
  } catch {
    /* silent */
  }
}

// 登录态变化时刷新角标（退出登录 → 清零；登录后 → 拉取）
watch(() => userStore.isLoggedIn, fetchCartCount, { immediate: true })

// 路由切换也刷一次，覆盖"刚在详情页加入购物车再回到首页/列表"的场景
watch(() => route.fullPath, () => {
  if (userStore.isLoggedIn) fetchCartCount()
})
</script>

<style scoped>
.search-box {
  width: 360px;
  height: 36px;
  border: 2px solid var(--color-primary);
  border-radius: 2px;
  overflow: hidden;
  background: #fff;
}
.search-input {
  flex: 1;
  height: 100%;
  padding: 0 12px;
  border: 0;
  outline: 0;
  font-size: 13px;
  background: transparent;
}
.search-input::placeholder { color: #c0c4cc; }
.search-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 0 16px;
  background: var(--color-primary);
  color: #fff;
  border: 0;
  cursor: pointer;
  font-weight: 500;
  transition: filter 0.15s;
}
.search-btn:hover { filter: brightness(1.06); }

.cart-link {
  height: 36px;
  padding: 0 14px;
  border: 1px solid #e5e7eb;
  border-radius: 2px;
  background: #fff;
}
.cart-link:hover { border-color: var(--color-primary); }
.cart-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  background: #ff4d4f;
  color: #fff;
  font-size: 11px;
  line-height: 18px;
  text-align: center;
  border-radius: 9px;
  white-space: nowrap;
}
</style>
