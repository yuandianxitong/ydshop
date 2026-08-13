<template>
  <el-drawer v-model="visible" title="选择主题" size="400px">
    <div v-loading="loading" class="theme-drawer">
      <div v-for="theme in themes" :key="theme.id" class="theme-drawer__item" @click="applyTheme(theme)">
        <div class="theme-drawer__cover">
          <img v-if="theme.cover" :src="theme.cover" />
          <div v-else class="theme-drawer__placeholder">
            <i class="i-lucide:wand-sparkles text-2xl" />
          </div>
        </div>
        <div class="theme-drawer__name">
          {{ theme.name }}
          <el-tag v-if="theme.is_system" size="small" type="warning">系统</el-tag>
        </div>
      </div>
      <el-empty v-if="!loading && themes.length === 0" description="暂无主题" />
    </div>
  </el-drawer>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { diyTemplateApi as diyThemeApi, type DiyTemplateInfo as DiyThemeInfo } from '@/api/diyTemplate'
import { useEditor } from '../useEditor'

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits(['update:modelValue'])

const { setComponents } = useEditor()
const visible = ref(props.modelValue)
const loading = ref(false)
const themes = ref<DiyThemeInfo[]>([])

watch(() => props.modelValue, (v) => { visible.value = v; if (v) loadThemes() })
watch(visible, (v) => emit('update:modelValue', v))

async function loadThemes() {
    loading.value = true
    try {
        const res = await diyThemeApi.getList({ platform: 'uniapp', page_type: 'home', limit: 50 })
        themes.value = res.data.list
    } finally {
        loading.value = false
    }
}

async function applyTheme(theme: DiyThemeInfo) {
    await ElMessageBox.confirm('应用主题将替换当前所有组件，确定继续？', '提示', { type: 'warning' })
    setComponents(JSON.parse(JSON.stringify(theme.components || [])))
    ElMessage.success('主题已应用')
    visible.value = false
}
</script>

<style lang="scss" scoped>
.theme-drawer {
    display: flex;
    flex-direction: column;
    gap: 12px;

    &__item {
        border: 1px solid #e4e7ed;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: border-color 0.2s;
        &:hover { border-color: var(--el-color-primary); }
    }

    &__cover {
        height: 140px;
        background: #f5f7fa;
        img { width: 100%; height: 100%; object-fit: cover; }
    }

    &__placeholder {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #c0c4cc;
    }

    &__name {
        padding: 8px 12px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
}
</style>
