<template>
    <main class="main-wrap h-full">
        <el-scrollbar>
            <div class="main-content">
                <router-view v-if="isRouteShow" v-slot="{ Component, route }">
                    <transition name="fade" mode="out-in">
                        <keep-alive :include="includeList" :max="20">
                            <component :is="Component" v-if="Component" :key="route.fullPath" />
                        </keep-alive>
                    </transition>
                </router-view>
            </div>
        </el-scrollbar>
    </main>
</template>

<script setup lang="ts">
import { useAppStore, useMultipleTabsStore } from '@/store'

const appStore = useAppStore()
const tabsStore = useMultipleTabsStore()

const isRouteShow = computed(() => appStore.isRouteShow)
const includeList = computed(() => tabsStore.getCacheTabList)
</script>

<style lang="scss" scoped>
.main-content {
    padding: 20px;
    background: var(--page-bg);
    min-height: 100%;
}
</style>

<!-- Transition 类打在子组件根节点上，必须全局样式 -->
<style lang="scss">
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
