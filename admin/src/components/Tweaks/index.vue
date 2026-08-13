<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'

import { useSettingStore } from '@/store'
import { PRESET_COLORS } from '@/utils/theme'

const settingStore = useSettingStore()
const visible = ref(false)
const panelRef = ref<HTMLElement | null>(null)

function toggle() {
    visible.value = !visible.value
}

function onClickOutside(e: MouseEvent) {
    if (panelRef.value && !panelRef.value.contains(e.target as Node)) {
        visible.value = false
    }
}

function selectColor(color: string) {
    settingStore.setSetting('primaryColor', color)
}

function toggleCompact() {
    settingStore.setSetting('compact', !settingStore.compact)
}

function toggleLabels() {
    settingStore.setSetting('sidebarLabels', !settingStore.sidebarLabels)
}

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('mousedown', onClickOutside)
})

defineExpose({ toggle })
</script>

<template>
    <Teleport to="body">
        <div v-if="visible" ref="panelRef" class="tweaks">
            <div class="tweaks-header">
                <span class="tweaks-title">{{ $t('header.settings') }}</span>
                <span class="tweaks-close" @click="visible = false">&times;</span>
            </div>

            <div class="tweaks-section">
                <div class="tweaks-label">{{ $t('tweaks.primaryColor') }}</div>
                <div class="tweaks-colors">
                    <div
                        v-for="color in PRESET_COLORS"
                        :key="color"
                        class="color-swatch"
                        :class="{ active: settingStore.primaryColor === color }"
                        :style="{ backgroundColor: color }"
                        @click="selectColor(color)"
                    >
                        <span v-if="settingStore.primaryColor === color" class="check">✓</span>
                    </div>
                </div>
            </div>

            <div class="tweaks-row">
                <div>
                    <div class="tweaks-label">{{ $t('tweaks.compact') }}</div>
                    <div class="tweaks-hint">{{ $t('tweaks.compactHint') }}</div>
                </div>
                <el-switch
                    :model-value="settingStore.compact"
                    @change="toggleCompact"
                />
            </div>

            <div class="tweaks-row">
                <div>
                    <div class="tweaks-label">{{ $t('tweaks.sidebarLabels') }}</div>
                    <div class="tweaks-hint">{{ $t('tweaks.sidebarLabelsHint') }}</div>
                </div>
                <el-switch
                    :model-value="settingStore.sidebarLabels"
                    @change="toggleLabels"
                />
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.tweaks {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 100;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
    border: 1px solid var(--ink-200);
    width: 300px;
    padding: 18px;
}

.tweaks-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.tweaks-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--ink-900);
}

.tweaks-close {
    color: var(--ink-400);
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
}

.tweaks-close:hover {
    color: var(--ink-600);
}

.tweaks-section {
    margin-bottom: 20px;
}

.tweaks-label {
    font-size: 12.5px;
    color: var(--ink-600);
    margin-bottom: 10px;
}

.tweaks-hint {
    font-size: 11px;
    color: var(--ink-400);
    margin-top: 2px;
}

.tweaks-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.tweaks-row:last-child {
    margin-bottom: 0;
}

.tweaks-row .tweaks-label {
    margin-bottom: 0;
}

.tweaks-colors {
    display: flex;
    gap: 10px;
}

.color-swatch {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s;
}

.color-swatch:hover {
    transform: scale(1.1);
}

.color-swatch.active {
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px currentColor;
}

.check {
    color: #fff;
    font-size: 14px;
}
</style>
