<template>
    <header class="topbar-wrap">
        <div class="topbar">
            <div class="topbar-left">
                <breadcrumb />
            </div>
            <div class="topbar-actions">
                <button class="top-btn" :title="$t('header.clearCache')" @click="handleClearCache">
                    <Icon name="i-svg:rotate-ccw" :size="17" />
                </button>
                <button
                    v-if="!isMobile"
                    class="top-btn"
                    :title="isFullscreen ? $t('header.exitFullscreen') : $t('header.fullscreen')"
                    @click="toggleFullscreen"
                >
                    <Icon :name="isFullscreen ? 'i-svg:minimize' : 'i-svg:maximize'" :size="17" />
                </button>
                <notification-bell />
                <lang-select />
                <button
                    class="top-btn"
                    :title="$t('header.settings')"
                    @mousedown.stop
                    @click="tweaksRef?.toggle()"
                >
                    <Icon name="i-svg:settings" :size="17" />
                </button>
                <user-drop-down />
            </div>
        </div>
        <multiple-tabs />
    </header>
    <Tweaks ref="tweaksRef" />
</template>

<script setup lang="ts">
import { useFullscreen } from '@vueuse/core'
import { ElMessage } from 'element-plus'
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { clearSystemCache } from '@/api/system/config'
import Tweaks from '@/components/Tweaks/index.vue'
import { useAppStore } from '@/store'

import Breadcrumb from './breadcrumb.vue'
import LangSelect from './lang-select.vue'
import MultipleTabs from './multiple-tabs.vue'
import NotificationBell from './notification-bell.vue'
import UserDropDown from './user-drop-down.vue'

const { t } = useI18n()
const appStore = useAppStore()
const tweaksRef = ref<InstanceType<typeof Tweaks>>()
const isMobile = computed(() => appStore.isMobile)
const { isFullscreen, toggle: toggleFullscreen } = useFullscreen()
const cacheClearing = ref(false)

const handleClearCache = async () => {
    if (cacheClearing.value) return
    cacheClearing.value = true
    try {
        await clearSystemCache()
        ElMessage.success(t('header.clearCacheSuccess'))
        appStore.refreshView()
    } catch {
        ElMessage.error(t('header.clearCacheFailed'))
    } finally {
        cacheClearing.value = false
    }
}
</script>

<style lang="scss">
.topbar-wrap {
    display: flex;
    flex-direction: column;
}

.topbar {
    height: var(--header-h);
    background: #fff;
    border-bottom: 1px solid var(--ink-100);
    padding: 0 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.topbar-left {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
}

.topbar-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.top-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: none;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ink-500);
    cursor: pointer;
    transition: background 0.15s, color 0.15s;

    &:hover {
        background: var(--ink-100);
        color: var(--ink-800);
    }

    svg {
        width: 17px;
        height: 17px;
    }
}
</style>
