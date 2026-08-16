<template>
  <NConfigProvider :locale="zhCN" :date-locale="dateZhCN" :theme-overrides="themeOverrides">
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
import { NConfigProvider, NMessageProvider, NDialogProvider, type GlobalThemeOverrides } from 'naive-ui'
import { zhCN, dateZhCN } from 'naive-ui'
import { useAppStore } from '~/store/app'

const DEFAULT_PRIMARY = '#2d8cf0'

const appStore = useAppStore()
if (import.meta.client) {
  appStore.fetchConfig()
}

const themeOverrides = computed<GlobalThemeOverrides>(() => {
  const primary = appStore.config?.theme_primary_color || DEFAULT_PRIMARY
  return {
    common: {
      primaryColor: primary,
      primaryColorHover: primary,
      primaryColorPressed: primary,
      primaryColorSuppl: primary,
    },
    Button: {
      colorPrimary: primary,
      colorHoverPrimary: primary,
      colorPressedPrimary: primary,
      colorFocusPrimary: primary,
      textColorPrimary: '#fff',
      borderPrimary: `1px solid ${primary}`,
    },
  }
})
</script>
