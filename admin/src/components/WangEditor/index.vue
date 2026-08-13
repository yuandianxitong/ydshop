<template>
    <div class="wang-editor-container" :class="{ 'is-fill': fill }">
        <div class="wang-editor-toolbar-row">
            <Toolbar
                :editor="editorRef"
                :default-config="toolbarConfig"
                :mode="mode"
                class="wang-editor-toolbar"
            />
            <div v-if="$slots['toolbar-right']" class="wang-editor-toolbar-right">
                <slot name="toolbar-right" />
            </div>
        </div>
        <Editor
            v-model="valueHtml"
            :default-config="editorConfig"
            :mode="mode"
            class="wang-editor-body"
            :style="editorStyle"
            @on-created="handleCreated"
            @on-change="handleChange"
        />

        <MaterialPicker
            v-model="pickerVisible"
            multiple
            :limit="20"
            @confirm="handlePickerConfirm"
        />
    </div>
</template>

<script setup lang="ts">
import '@wangeditor/editor/dist/css/style.css'

import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
import type { IDomEditor, IEditorConfig, IToolbarConfig } from '@wangeditor/editor'
import { computed, onBeforeUnmount, ref, shallowRef, watch } from 'vue'

import MaterialPicker from '@/components/MaterialPicker/index.vue'

interface Props {
    modelValue?: string
    /** 固定像素高度；fill=true 时忽略，改为占满父容器 */
    height?: number
    /** 占满父级高度（父级需有明确高度 / flex 布局） */
    fill?: boolean
    mode?: 'default' | 'simple'
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    height: 400,
    fill: false,
    mode: 'default'
})

const editorStyle = computed(() =>
    props.fill
        ? { flex: '1 1 auto', minHeight: '0', height: '100%', overflowY: 'hidden' }
        : { height: `${props.height}px`, overflowY: 'hidden' }
)

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

const editorRef = shallowRef<IDomEditor>()
const valueHtml = ref(props.modelValue)
const pickerVisible = ref(false)

const toolbarConfig: Partial<IToolbarConfig> = {}

const editorConfig: Partial<IEditorConfig> = {
    placeholder: '请输入内容...',
    MENU_CONF: {
        uploadImage: {
            // 拦截上传图片菜单，改为打开素材选择弹窗
            customBrowseAndUpload() {
                pickerVisible.value = true
            }
        }
    }
}

const handlePickerConfirm = (urls: string[]) => {
    const editor = editorRef.value
    if (!editor) return
    // 先恢复编辑器焦点（弹窗关闭后编辑器可能失焦）
    editor.focus()
    urls.forEach(url => {
        editor.dangerouslyInsertHtml(`<img src="${url}" alt="" style="max-width:100%;" />`)
    })
}

// 监听外部 modelValue 变化
watch(
    () => props.modelValue,
    (val) => {
        if (val !== valueHtml.value) {
            valueHtml.value = val
        }
    }
)

const handleCreated = (editor: IDomEditor) => {
    editorRef.value = editor
}

const handleChange = (editor: IDomEditor) => {
    const html = editor.getHtml()
    emit('update:modelValue', html)
}

// 组件销毁时，及时销毁编辑器
onBeforeUnmount(() => {
    const editor = editorRef.value
    if (editor == null) return
    editor.destroy()
})
</script>

<style lang="scss" scoped>
.wang-editor-container {
    border: 1px solid #ccc;
    border-radius: 4px;
    overflow: hidden;
    width: 100%;
    z-index: 100;

    &.is-fill {
        height: 100%;
        display: flex;
        flex-direction: column;
        min-height: 0;

        .wang-editor-toolbar-row {
            flex-shrink: 0;
        }

        .wang-editor-body {
            flex: 1 1 auto;
            min-height: 0 !important;
        }

        :deep(.w-e-text-container),
        :deep(.w-e-scroll) {
            height: 100% !important;
        }
    }
}
.wang-editor-toolbar-row {
    display: flex;
    align-items: center;
    border-bottom: 1px solid #ccc;
}
.wang-editor-toolbar {
    flex: 1;
    min-width: 0;
}
.wang-editor-toolbar-right {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    padding: 0 8px;
    gap: 8px;
}
</style>
