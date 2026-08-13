<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { diyApi, type DiyPageVersion } from '@/api/diy'

const props = defineProps<{ pageId: number }>()
const emit = defineEmits<{
  close: []
  preview: [snapshot: { title: string; components: any; page_settings: any; versionNo: number }]
  restored: []
}>()

const visible = ref(true)
const versions = ref<DiyPageVersion[]>([])
const loading = ref(false)

const fetchVersions = async () => {
  loading.value = true
  try {
    const res = await diyApi.listVersions(props.pageId)
    versions.value = (res.data as DiyPageVersion[]) || []
  } finally {
    loading.value = false
  }
}

const onPreview = async (v: DiyPageVersion) => {
  const res = await diyApi.getVersion(props.pageId, v.id)
  const detail = res.data as DiyPageVersion
  emit('preview', {
    title: detail.title,
    components: detail.components,
    page_settings: detail.page_settings,
    versionNo: detail.version_no,
  })
}

const onRestore = async (v: DiyPageVersion) => {
  try {
    await ElMessageBox.confirm(
      `确定恢复至 v${v.version_no}? 当前编辑器内容将被覆盖。`,
      '恢复版本',
      { confirmButtonText: '确定恢复', cancelButtonText: '取消', type: 'warning' }
    )
  } catch {
    return
  }
  await diyApi.restoreVersion(props.pageId, v.id)
  ElMessage.success(`已恢复至 v${v.version_no}`)
  emit('restored')
}

const fmtTime = (s: string) => (s || '').replace('T', ' ').slice(0, 16)

onMounted(fetchVersions)
</script>

<template>
  <el-drawer
    v-model="visible"
    title="历史版本"
    direction="rtl"
    size="480px"
    @close="emit('close')"
  >
    <div v-loading="loading" class="ver-list">
      <el-empty v-if="!loading && versions.length === 0" description="暂无历史版本(发布后才会自动生成)" />
      <div v-for="v in versions" :key="v.id" class="ver-item">
        <div class="ver-head">
          <span class="ver-no">v{{ v.version_no }}</span>
          <span class="ver-time num">{{ fmtTime(v.created_at) }}</span>
        </div>
        <div class="ver-meta">
          <span v-if="v.created_by_name" class="ver-by">{{ v.created_by_name }}</span>
          <span v-else-if="v.created_by" class="ver-by">admin#{{ v.created_by }}</span>
          <span v-else class="ver-by ver-by--system">系统</span>
        </div>
        <div class="ver-note">{{ v.note || '(无备注)' }}</div>
        <div class="ver-actions">
          <el-button size="small" @click="onPreview(v)">预览</el-button>
          <el-button size="small" type="primary" v-has-perm="['diy.page.version.restore']" @click="onRestore(v)">恢复</el-button>
        </div>
      </div>
    </div>
  </el-drawer>
</template>

<style lang="scss" scoped>
.ver-list { padding: 8px 0; }
.ver-item {
  padding: 14px 16px;
  border-bottom: 1px solid var(--ink-100);
  &:hover { background: var(--ink-50); }
}
.ver-head {
  display: flex; justify-content: space-between; align-items: center;
}
.ver-no { font-size: 14px; font-weight: 700; color: var(--brand-600); }
.ver-time { font-size: 12px; color: var(--ink-400); }
.ver-meta { font-size: 12px; color: var(--ink-500); margin-top: 2px; }
.ver-by--system { font-style: italic; }
.ver-note { font-size: 12px; color: var(--ink-700); margin: 6px 0; line-height: 1.5; }
.ver-actions { display: flex; gap: 8px; justify-content: flex-end; }
</style>
