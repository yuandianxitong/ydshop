<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { diyTemplateApi, type DiyTemplateInfo } from '@/api/diyTemplate'
import { diyApi, type DiyPageInfo } from '@/api/diy'
import { useUserStore } from '@/store/modules/user.store'
import { useAppStore } from '@/store/modules/app.store'

const props = defineProps<{
  defaultPlatform: 'uniapp' | 'pc'
  defaultPageType: 'home' | 'custom'
}>()
const emit = defineEmits<{ close: []; created: [tpl: DiyTemplateInfo] }>()

const userStore = useUserStore()
const appStore = useAppStore()
const visible = ref(true)
const submitting = ref(false)

const uploadHeaders = computed(() => ({ Authorization: `Bearer ${userStore.token}` }))

const form = ref({
    name: '',
    platform: props.defaultPlatform,
    page_type: props.defaultPageType || 'home',
    cover: '',
    source: 'blank' as 'blank' | 'template' | 'page',
    template_id: null as number | null,
    page_id: null as number | null,
})

const tplCandidates = ref<DiyTemplateInfo[]>([])
const pageCandidates = ref<DiyPageInfo[]>([])

const filteredTpls = computed(() =>
    tplCandidates.value.filter((t) => t.platform === form.value.platform && t.page_type === form.value.page_type)
)
const filteredPages = computed(() =>
    pageCandidates.value.filter((p) => p.platform === form.value.platform && p.page_type === form.value.page_type)
)

async function fetchSources() {
    const [tpls, pages] = await Promise.all([
        diyTemplateApi.getList({ limit: 200 }),
        diyApi.getPageList({ limit: 200 }),
    ])
    tplCandidates.value = tpls.data.list || []
    pageCandidates.value = pages.data.list || []
}

// 切换平台/类型时清掉旧选择，避免引用不匹配的源
watch(() => [form.value.platform, form.value.page_type], () => {
    form.value.template_id = null
    form.value.page_id = null
})

watch(() => form.value.source, (s) => {
    if (s !== 'template') form.value.template_id = null
    if (s !== 'page') form.value.page_id = null
})

function onCoverUpload(res: any) {
    if (res?.code === 200) {
        form.value.cover = res.data?.path || res.data?.url || ''
    } else {
        ElMessage.error(res?.message || '上传失败')
    }
}

async function resolveComponents(): Promise<any[]> {
    if (form.value.source === 'template' && form.value.template_id) {
        const res = await diyTemplateApi.getDetail(form.value.template_id)
        return Array.isArray((res.data as any)?.components) ? (res.data as any).components : []
    }
    if (form.value.source === 'page' && form.value.page_id) {
        const res = await diyApi.getPageDetail(form.value.page_id)
        return Array.isArray((res.data as any)?.components) ? (res.data as any).components : []
    }
    return []
}

async function onSubmit() {
    if (!form.value.name.trim()) {
        ElMessage.warning('请输入模板名称')
        return
    }
    if (form.value.source === 'template' && !form.value.template_id) {
        ElMessage.warning('请选择来源模板')
        return
    }
    if (form.value.source === 'page' && !form.value.page_id) {
        ElMessage.warning('请选择来源页面')
        return
    }

    submitting.value = true
    try {
        const components = await resolveComponents()
        const res = await diyTemplateApi.create({
            name: form.value.name.trim(),
            platform: form.value.platform,
            page_type: form.value.page_type,
            cover: form.value.cover,
            components,
        })
        ElMessage.success('创建成功')
        emit('created', res.data)
    } finally {
        submitting.value = false
    }
}

onMounted(fetchSources)
</script>

<template>
  <el-dialog
    v-model="visible"
    title="新建模板"
    width="720px"
    :close-on-click-modal="false"
    append-to-body
    @close="emit('close')"
  >
    <el-form :model="form" label-width="84px">
      <el-form-item label="名称" required>
        <el-input v-model="form.name" placeholder="例如：春季大促首页模板" maxlength="50" show-word-limit />
      </el-form-item>

      <el-form-item label="平台">
        <el-radio-group v-model="form.platform">
          <el-radio value="uniapp">移动端</el-radio>
          <el-radio value="pc">PC 端</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item label="页面类型">
        <el-radio-group v-model="form.page_type">
          <el-radio value="home">首页</el-radio>
          <el-radio value="custom">专题</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item label="封面">
        <el-upload
          class="tpl-upload"
          :action="'/adminapi/upload/image'"
          :headers="uploadHeaders"
          :show-file-list="false"
          :on-success="onCoverUpload"
        >
          <img v-if="form.cover" :src="appStore.getImageUrl(form.cover)" class="tpl-upload__img" />
          <div v-else class="tpl-upload__empty">
            <i class="i-lucide:image-plus" />
            <span>上传封面</span>
          </div>
        </el-upload>
        <div class="tpl-upload__hint">建议尺寸 560×1000，PNG / JPG，最大 2MB</div>
      </el-form-item>

      <el-form-item label="初始内容">
        <el-radio-group v-model="form.source">
          <el-radio value="blank">空白模板</el-radio>
          <el-radio value="template">基于已有模板</el-radio>
          <el-radio value="page">基于已有页面</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item v-if="form.source === 'template'" label="来源模板">
        <el-select v-model="form.template_id" placeholder="请选择已有模板" style="width: 100%" filterable>
          <el-option v-for="t in filteredTpls" :key="t.id" :label="t.name" :value="t.id" />
        </el-select>
        <div v-if="!filteredTpls.length" class="tpl-empty">当前平台/类型下没有可选模板</div>
      </el-form-item>

      <el-form-item v-if="form.source === 'page'" label="来源页面">
        <el-select v-model="form.page_id" placeholder="请选择已有页面" style="width: 100%" filterable>
          <el-option v-for="p in filteredPages" :key="p.id" :label="p.title" :value="p.id" />
        </el-select>
        <div v-if="!filteredPages.length" class="tpl-empty">当前平台/类型下没有可选页面</div>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('close')">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="onSubmit">确定创建</el-button>
    </template>
  </el-dialog>
</template>

<style lang="scss" scoped>
.tpl-upload {
  :deep(.el-upload) {
    width: 140px;
    height: 200px;
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    cursor: pointer;
    transition: border-color .2s;

    &:hover { border-color: var(--el-color-primary); }
  }
  &__img { width: 100%; height: 100%; object-fit: cover; display: block; }
  &__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    color: var(--ink-400);
    font-size: 12px;
    i { font-size: 26px; }
  }
  &__hint { font-size: 11.5px; color: var(--ink-400); margin-top: 6px; }
}
.tpl-empty { font-size: 12px; color: var(--ink-400); padding-top: 4px; }
</style>
