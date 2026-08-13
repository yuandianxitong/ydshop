<template>
    <el-dropdown trigger="click" @command="handleChange">
        <button class="top-btn" :title="currentLabel">
            <i class="i-svg:languages" />
        </button>
        <template #dropdown>
            <el-dropdown-menu>
                <el-dropdown-item
                    v-for="item in localeOptions"
                    :key="item.value"
                    :command="item.value"
                    :class="{ 'is-active': currentLocale === item.value }"
                >
                    {{ item.label }}
                </el-dropdown-item>
            </el-dropdown-menu>
        </template>
    </el-dropdown>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { computed, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

import { getLocale, localeOptions, type LocaleType, setLocale } from '@/locales/setupI18n'

const { t } = useI18n()
const currentLocale = computed(() => getLocale())

const currentLabel = computed(() => {
    const opt = localeOptions.find((o) => o.value === currentLocale.value)
    return opt?.label || ''
})

const handleChange = (locale: LocaleType) => {
    if (locale === currentLocale.value) return
    setLocale(locale)
    nextTick(() => {
        ElMessage.success(t('langSelect.message.success'))
    })
}
</script>

<style lang="scss" scoped>
:deep(.el-dropdown-menu__item.is-active) {
    color: var(--el-color-primary);
    font-weight: 500;
}
</style>
