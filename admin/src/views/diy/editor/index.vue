<template>
  <div class="diy-editor">
    <div class="diy-editor__toolbar">
      <div class="flex items-center gap-3">
        <el-button text @click="$router.back()">
          <i class="i-svg:arrow-left mr-1" />返回
        </el-button>
        <el-divider direction="vertical" />
        <el-tag size="small">{{ platform === 'uniapp' ? '移动端' : 'PC端' }}</el-tag>
      </div>
      <div class="flex items-center gap-2">
        <div class="toolbar-group">
          <el-button :disabled="undoStack.length === 0" @click="undo">
            <i class="i-svg:rotate-ccw mr-1" />撤销
          </el-button>
          <el-button :disabled="redoStack.length === 0" @click="redo">
            <i class="i-lucide:rotate-cw mr-1" />重做
          </el-button>
        </div>
        <el-divider direction="vertical" />
        <div class="toolbar-group">
          <el-button :disabled="previewMode" @click="themeDrawerVisible = true">
            <i class="i-lucide:wand-sparkles mr-1" />主题
          </el-button>
          <el-button v-has-perm="['diy.template.create']" :disabled="previewMode" @click="handleSaveAsTemplate">
            <i class="i-lucide:bookmark-plus mr-1" />保存为模板
          </el-button>
          <el-button
            v-has-perm="['diy.page.version.list']"
            :disabled="previewMode"
            @click="showVersionDrawer = true"
          >
            <i class="i-lucide:history mr-1" />历史版本
          </el-button>
        </div>
        <el-divider direction="vertical" />
        <el-button :disabled="previewMode" @click="handleSave" :loading="saving">保存</el-button>
        <el-button
          type="primary"
          :disabled="previewMode"
          v-has-perm="['diy.page.publish']"
          @click="handlePublish"
          :loading="publishing"
        >
          发布
        </el-button>
      </div>
    </div>
    <div v-if="previewMode" class="preview-banner">
      <span>正在预览 v{{ previewMeta?.versionNo }} — 内容只读</span>
      <el-button size="small" @click="exitPreview">退出预览</el-button>
    </div>
    <div class="diy-editor__body">
      <ComponentPanel class="diy-editor__left" />
      <SimulatorPreview class="diy-editor__center" />
      <PropertyPanel class="diy-editor__right" />
    </div>
    <ThemeDrawer v-model="themeDrawerVisible" />
    <VersionDrawer
      v-if="showVersionDrawer && pageId"
      :page-id="pageId"
      @close="showVersionDrawer = false"
      @preview="(snap) => { enterPreview(snap); showVersionDrawer = false }"
      @restored="async () => { showVersionDrawer = false; await reloadPage(); refreshSnapshot() }"
    />
    <PublishDialog
      v-if="showPublishDialog"
      :page-title="pageTitle"
      @confirm="onEditorPublishConfirm"
      @close="showPublishDialog = false"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { diyApi } from '@/api/diy'
import { diyTemplateApi } from '@/api/diyTemplate'
import { useEditor } from './useEditor'
import ComponentPanel from './components/ComponentPanel.vue'
import SimulatorPreview from './components/SimulatorPreview.vue'
import PropertyPanel from './components/PropertyPanel.vue'
import ThemeDrawer from './components/ThemeDrawer.vue'
import VersionDrawer from './components/VersionDrawer.vue'
import PublishDialog from '../page/PublishDialog.vue'

const route = useRoute()
const router = useRouter()
const {
    components, platform, pageType, undoStack, redoStack, undo, redo, setComponents,
    pageTitle, pageSettings,
    pageId, previewMode, previewMeta,
    enterPreview, exitPreview, reloadPage,
} = useEditor()

const saving = ref(false)
const publishing = ref(false)
const themeDrawerVisible = ref(false)
const showVersionDrawer = ref(false)
const showPublishDialog = ref(false)

// 脏检查：保存上次落盘时的页面快照，与当前编辑状态对比判断是否有未保存改动
const cleanSnapshot = ref('')
function currentSnapshot() {
    return JSON.stringify({
        title: pageTitle.value,
        components: components.value,
        page_settings: pageSettings.value,
    })
}
function refreshSnapshot() {
    cleanSnapshot.value = currentSnapshot()
}
// 预览模式下数据被换成历史版本，跳过脏检查避免误报
const isDirty = computed(() => !previewMode.value && currentSnapshot() !== cleanSnapshot.value)

onBeforeRouteLeave(async () => {
    if (!isDirty.value) return true
    try {
        await ElMessageBox.confirm('当前页面有未保存的修改，确定要离开吗？', '未保存提示', {
            confirmButtonText: '离开',
            cancelButtonText: '继续编辑',
            type: 'warning',
        })
        return true
    } catch {
        return false
    }
})

function onBeforeUnload(e: BeforeUnloadEvent) {
    if (!isDirty.value) return
    e.preventDefault()
    e.returnValue = ''
}

async function loadPage() {
    const id = Number(route.query.id)
    if (!id) {
        ElMessage.error('缺少页面ID')
        router.back()
        return
    }
    pageId.value = id
    const res = await diyApi.getPageDetail(id)
    platform.value = res.data.platform as 'uniapp' | 'pc'
    pageType.value = res.data.page_type
    setComponents(
        res.data.components || [],
        res.data.title,
        res.data.page_settings || { background_color: '', background_image: '' }
    )
    refreshSnapshot()
}

async function handleSave() {
    if (!pageTitle.value.trim()) {
        ElMessage.warning('请输入页面标题')
        return
    }
    saving.value = true
    try {
        await diyApi.updatePage(pageId.value, {
            title: pageTitle.value.trim(),
            components: components.value,
            page_settings: pageSettings.value,
        })
        refreshSnapshot()
        ElMessage.success('保存成功')
    } finally {
        saving.value = false
    }
}

async function handleSaveAsTemplate() {
    try {
        const { value: name } = await ElMessageBox.prompt('请输入模板名称', '保存为模板', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            inputPattern: /^.{1,100}$/,
            inputErrorMessage: '名称必填且不超过 100 字符'
        })
        // 模板仅支持 home / custom，其它页面类型统一保存为 custom
        const rawType = pageType.value
        const finalPageType: 'home' | 'custom' = (rawType === 'home' || rawType === 'custom') ? rawType : 'custom'
        await diyTemplateApi.create({
            name: name.trim(),
            cover: '',
            platform: platform.value,
            page_type: finalPageType,
            components: components.value
        })
        ElMessage.success('已保存为模板')
    } catch (e) {
        if (e !== 'cancel') console.error('保存模板失败:', e)
    }
}

async function handlePublish() {
    if (!pageTitle.value.trim()) {
        ElMessage.warning('请输入页面标题')
        return
    }
    showPublishDialog.value = true
}

async function onEditorPublishConfirm(note: string) {
    if (!pageId.value) return
    publishing.value = true
    try {
        await diyApi.updatePage(pageId.value, {
            title: pageTitle.value.trim(),
            components: components.value,
            page_settings: pageSettings.value,
        })
        await diyApi.publishPage(pageId.value, true, note)
        refreshSnapshot()
        ElMessage.success('发布成功')
        showPublishDialog.value = false
    } finally {
        publishing.value = false
    }
}

onMounted(() => {
    loadPage()
    window.addEventListener('beforeunload', onBeforeUnload)
})
onBeforeUnmount(() => window.removeEventListener('beforeunload', onBeforeUnload))
</script>

<style lang="scss" scoped>
.diy-editor {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #f5f6f8;

    &__toolbar {
        height: 60px;
        background: var(--brand-500, #4f6bff);
        color: #fff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 16px;
        flex-shrink: 0;

        // el-tag 在蓝底上换成白色透明小标签
        :deep(.el-tag) {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            border-color: transparent;
        }

        // el-divider 竖线在蓝底上用白色 30%
        :deep(.el-divider--vertical) {
            background-color: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.3);
        }

        // text 类按钮：白色文字，hover 浅一层
        :deep(.el-button.is-text) {
            color: #fff;
            &:hover,
            &:focus {
                background: rgba(255, 255, 255, 0.12);
                color: #fff;
            }
        }

        // 默认按钮：白色透明边框 + 白文字
        :deep(.el-button:not(.is-text):not(.el-button--primary)) {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.35);
            color: #fff;
            &:hover:not(.is-disabled) {
                background: rgba(255, 255, 255, 0.22);
                border-color: rgba(255, 255, 255, 0.55);
                color: #fff;
            }
            &.is-disabled {
                background: rgba(255, 255, 255, 0.06);
                border-color: rgba(255, 255, 255, 0.18);
                color: rgba(255, 255, 255, 0.45);
            }
        }

        // primary 按钮（发布）：反白 — 白底主色字
        :deep(.el-button--primary) {
            background: #fff;
            border-color: #fff;
            color: var(--brand-500, #4f6bff);
            &:hover:not(.is-disabled) {
                background: #fff;
                color: var(--brand-600, #3b54e0);
                opacity: 0.92;
            }
            &.is-disabled {
                background: rgba(255, 255, 255, 0.6);
                color: rgba(79, 107, 255, 0.55);
            }
        }
    }

    &__body {
        flex: 1;
        display: flex;
        overflow: hidden;
    }

    &__left {
        width: 220px;
        background: #fff;
        border-right: 1px solid #e4e7ed;
        overflow-y: auto;
        flex-shrink: 0;
    }

    &__center {
        flex: 1;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    &__right {
        width: 400px;
        background: #fff;
        border-left: 1px solid #e4e7ed;
        overflow-y: auto;
        flex-shrink: 0;
    }

    .toolbar-group {
        display: flex;
        align-items: center;
        gap: 4px;
    }
}

.preview-banner {
    background: #fef3c7;
    color: #92400e;
    padding: 8px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    border-bottom: 1px solid #fcd34d;
}
</style>
