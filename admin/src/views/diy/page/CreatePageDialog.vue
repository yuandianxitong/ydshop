<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { diyApi, type DiyPageInfo } from '@/api/diy'
import { diyTemplateApi, type DiyTemplateInfo } from '@/api/diyTemplate'
import { useAppStore } from '@/store/modules/app.store'

const props = defineProps<{
  defaultPlatform: 'uniapp' | 'pc'
  defaultPageType: 'home' | 'custom'
}>()
const emit = defineEmits<{
  close: []
  created: [page: DiyPageInfo]
}>()

const appStore = useAppStore()
const visible = ref(true)
const submitting = ref(false)

const form = ref({
  page_type: props.defaultPageType,
  platform: props.defaultPlatform,
  title: '',
  template_id: null as number | null,
})

const allTemplates = ref<DiyTemplateInfo[]>([])
const filteredTemplates = computed(() =>
  allTemplates.value.filter(t =>
    t.platform === form.value.platform &&
    t.page_type === form.value.page_type
  )
)

const fetchTemplates = async () => {
  const res = await diyTemplateApi.getList({ limit: 200 })
  allTemplates.value = res.data.list || []
}

const onSubmit = async () => {
  if (!form.value.title.trim()) {
    ElMessage.warning('请输入页面标题')
    return
  }
  submitting.value = true
  try {
    const res = await diyApi.createPage({
      page_type: form.value.page_type,
      platform: form.value.platform,
      title: form.value.title.trim(),
      template_id: form.value.template_id,
    })
    emit('created', res.data)
  } finally {
    submitting.value = false
  }
}

watch(() => [form.value.platform, form.value.page_type], () => {
  form.value.template_id = null
})

onMounted(fetchTemplates)
</script>

<template>
  <el-dialog
    v-model="visible"
    title="新建页面"
    width="720px"
    @close="emit('close')"
    :close-on-click-modal="false"
    append-to-body
  >
    <el-form :model="form" label-width="84px">
      <el-form-item label="页面类型">
        <el-radio-group v-model="form.page_type">
          <el-radio value="home">首页</el-radio>
          <el-radio value="custom">专题</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item label="平台">
        <el-radio-group v-model="form.platform">
          <el-radio value="uniapp">移动端</el-radio>
          <el-radio value="pc">PC 端</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item label="标题">
        <el-input v-model="form.title" placeholder="例如:春季大促首页" maxlength="50" />
      </el-form-item>

      <el-form-item label="选择模板">
        <div class="tpl-grid">
          <div
            class="tpl-card"
            :class="{ on: form.template_id === null }"
            @click="form.template_id = null"
          >
            <div class="tpl-cover blank">
              <i class="i-lucide:file-plus" />
            </div>
            <div class="tpl-name">空白模板</div>
          </div>
          <div
            v-for="t in filteredTemplates"
            :key="t.id"
            class="tpl-card"
            :class="{ on: form.template_id === t.id }"
            @click="form.template_id = t.id"
          >
            <div class="tpl-cover">
              <img v-if="t.cover" :src="appStore.getImageUrl(t.cover)" />
              <i v-else class="i-lucide:image" />
            </div>
            <div class="tpl-name">{{ t.name }}</div>
          </div>
        </div>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="emit('close')">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="onSubmit">确定创建</el-button>
    </template>
  </el-dialog>
</template>

<style lang="scss" scoped>
.tpl-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  width: 100%;
}
.tpl-card {
  border: 2px solid transparent;
  border-radius: 8px;
  padding: 4px;
  cursor: pointer;
  transition: all 0.15s;
  background: #fff;
  &.on { border-color: var(--brand-500); box-shadow: 0 2px 8px rgba(20, 100, 255, 0.15); }
  &:hover:not(.on) { border-color: var(--ink-200); }
}
.tpl-cover {
  height: 96px;
  background: var(--ink-50);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  img { width: 100%; height: 100%; object-fit: cover; }
  i { font-size: 28px; color: var(--ink-300); }
  &.blank { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); }
}
.tpl-name { text-align: center; font-size: 12px; margin-top: 6px; color: var(--ink-700); }
</style>
