<template>
  <div class="min-h-screen flex flex-col">
    <AppTopBar />
    <AppHeader />
    <AppNavMenu />
    <main class="flex-1">
      <slot />
    </main>
    <AppFooter />
    <BackToTop />
  </div>
</template>

<script setup lang="ts">
import { getToken } from '~/composables/useRequest'
import { useUserStore } from '~/store/user'

const userStore = useUserStore()
onMounted(() => {
  if (getToken()) {
    userStore.syncFromStorage()
  }
})
</script>
