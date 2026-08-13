import { defineStore } from 'pinia'
import { ref } from 'vue'

import { SETTING_KEY } from '@/constants/cache'
import cache from '@/utils/cache'
import { applySettings } from '@/utils/theme'

interface SettingValues {
    primaryColor: string
    compact: boolean
    sidebarLabels: boolean
}

const defaultSettings: SettingValues = {
    primaryColor: '#4f6bff',
    compact: false,
    sidebarLabels: true
}

export const useSettingStore = defineStore('setting', () => {
    const cached = cache.get(SETTING_KEY) || {}

    const primaryColor = ref<string>(cached.primaryColor ?? defaultSettings.primaryColor)
    const compact = ref<boolean>(cached.compact ?? defaultSettings.compact)
    const sidebarLabels = ref<boolean>(cached.sidebarLabels ?? defaultSettings.sidebarLabels)
    const showDrawer = ref(false)

    function setSetting<K extends keyof SettingValues>(key: K, value: SettingValues[K]) {
        if (key === 'primaryColor') primaryColor.value = value as string
        else if (key === 'compact') compact.value = value as boolean
        else if (key === 'sidebarLabels') sidebarLabels.value = value as boolean

        cache.set(SETTING_KEY, {
            primaryColor: primaryColor.value,
            compact: compact.value,
            sidebarLabels: sidebarLabels.value
        })
        apply()
    }

    function apply() {
        applySettings({
            primaryColor: primaryColor.value,
            compact: compact.value,
            sidebarLabels: sidebarLabels.value
        })
    }

    function resetSettings() {
        primaryColor.value = defaultSettings.primaryColor
        compact.value = defaultSettings.compact
        sidebarLabels.value = defaultSettings.sidebarLabels
        cache.remove(SETTING_KEY)
        apply()
    }

    return {
        primaryColor,
        compact,
        sidebarLabels,
        showDrawer,
        setSetting,
        apply,
        resetSettings
    }
})

export default useSettingStore
