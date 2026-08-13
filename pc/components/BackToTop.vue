<template>
  <Transition name="fade">
    <button
      v-show="visible"
      class="back-to-top"
      title="返回顶部"
      @click="scrollToTop"
    >
      <span class="i-carbon-arrow-up text-xl" />
    </button>
  </Transition>
</template>

<script setup lang="ts">
const visible = ref(false)
const THRESHOLD = 400

function onScroll() {
  visible.value = (window.scrollY || document.documentElement.scrollTop) > THRESHOLD
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>

<style scoped>
.back-to-top {
  position: fixed;
  right: 24px;
  bottom: 80px;
  z-index: 60;
  width: 44px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.65);
  color: #fff;
  border: 0;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.15s, transform 0.15s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
.back-to-top:hover {
  background: var(--color-primary, #4f6bff);
  transform: translateY(-2px);
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
