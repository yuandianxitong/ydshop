<template>
  <NConfigProvider :locale="zhCN" :date-locale="dateZhCN">
    <NMessageProvider>
      <NDialogProvider>
        <NuxtLayout>
          <NuxtPage />
        </NuxtLayout>
      </NDialogProvider>
    </NMessageProvider>
  </NConfigProvider>
</template>

<script setup lang="ts">
import { NConfigProvider, NMessageProvider, NDialogProvider } from 'naive-ui'
import { zhCN, dateZhCN } from 'naive-ui'
import { useAppStore } from '~/store/app'

// 不 await：让首屏 UI 先用默认值渲染，配置到位后 reactive 自动切换
const appStore = useAppStore()
if (import.meta.client) {
  appStore.fetchConfig()
}
</script>
