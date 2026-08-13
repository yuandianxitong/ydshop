<script setup lang="ts" name="DiyPage">
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, onMounted,ref } from 'vue'
import { useRouter } from 'vue-router'

import type { DiyPageInfo } from '@/api/diy'
import { diyApi } from '@/api/diy'
import { useAppStore } from '@/store/modules/app.store'

import CreatePageDialog from './CreatePageDialog.vue'
import PublishDialog from './PublishDialog.vue'

const router = useRouter()
const appStore = useAppStore()

type Device = 'uniapp' | 'pc'
type PageType = 'home' | 'custom'

const device = ref<Device>('uniapp')
const pageType = ref<PageType>('home')
const keyword = ref('')
const loading = ref(false)
const allPages = ref<DiyPageInfo[]>([])
const showCreateDialog = ref(false)
const publishTarget = ref<DiyPageInfo | null>(null)

const filteredPages = computed(() =>
  allPages.value.filter((p) => {
    if (p.platform !== device.value) return false
    if (p.page_type !== pageType.value) return false
    if (!keyword.value) return true
    return (p.title || '').toLowerCase().includes(keyword.value.toLowerCase())
  })
)

const counts = computed(() => ({
  uniapp: allPages.value.filter((p) => p.platform === 'uniapp').length,
  pc: allPages.value.filter((p) => p.platform === 'pc').length,
  published: allPages.value.filter((p) => p.is_published).length,
  draft: allPages.value.filter((p) => !p.is_published).length,
}))

const fmtCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const fetchPages = async () => {
  try {
    loading.value = true
    const res = await diyApi.getPageList({ limit: 200 })
    allPages.value = res.data.list || []
  } finally {
    loading.value = false
  }
}

const handleEdit = (p: DiyPageInfo) => router.push(`/diy/editor?id=${p.id}`)

const handlePublish = async (p: DiyPageInfo) => {
  if (p.is_published) {
    // 取消发布: 直接调用,不弹 Dialog
    await diyApi.publishPage(p.id, false)
    ElMessage.success('已取消发布')
    fetchPages()
    return
  }
  // 发布: 打开 Dialog 让用户填备注
  publishTarget.value = p
}

const onPublishConfirm = async (note: string) => {
  if (!publishTarget.value) return
  const id = publishTarget.value.id
  await diyApi.publishPage(id, true, note)
  ElMessage.success('发布成功')
  publishTarget.value = null
  fetchPages()
}

const onPublishClose = () => {
  publishTarget.value = null
}

const handleSetDefault = async (p: DiyPageInfo) => {
  try {
    await ElMessageBox.confirm(
      `切换后 ${p.platform === 'uniapp' ? '移动端' : 'PC端'}首页将改为「${p.title}」,确定?`,
      '切换默认确认',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' }
    )
    await diyApi.setDefault(p.id)
    ElMessage.success('已设为默认')
    fetchPages()
  } catch (e) {
    if (e !== 'cancel') console.error('切换默认失败:', e)
  }
}

const handleDelete = async (p: DiyPageInfo) => {
  if (p.is_default) {
    ElMessage.warning('当前生效首页不可删除,请先把另一个页面设为默认')
    return
  }
  try {
    await ElMessageBox.confirm(`确定删除「${p.title}」?删除后不可恢复!`, '删除确认', {
      confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning',
    })
    await diyApi.deletePage(p.id)
    ElMessage.success('删除成功')
    fetchPages()
  } catch (e) {
    if (e !== 'cancel') console.error('删除失败:', e)
  }
}

const onCreated = (newPage: DiyPageInfo) => {
  showCreateDialog.value = false
  ElMessage.success('创建成功')
  router.push(`/diy/editor?id=${newPage.id}`)
}

onMounted(fetchPages)
</script>

<template>
  <div class="diy-page-container" v-loading="loading">
    <div class="page-head">
      <div>
        <h2 class="page-title">页面装修</h2>
        <p class="page-desc">拖拽式可视化装修,PC 与移动端独立维护</p>
      </div>
      <div class="page-actions">
        <el-button
          type="primary"
          v-has-perm="['diy.page.create']"
          @click="showCreateDialog = true"
        >
          <i class="i-lucide:plus mr-1" /> 新建页面
        </el-button>
      </div>
    </div>

    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">页面总数</div>
        <div class="nm num">{{ fmtCount(allPages.length) }}</div>
        <div class="tr"><span style="color:var(--ink-500)">PC + 移动端</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">移动端</div>
        <div class="nm num">{{ fmtCount(counts.uniapp) }}</div>
        <div class="tr"><span style="color:var(--brand-500)">uniapp</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">PC 端</div>
        <div class="nm num">{{ fmtCount(counts.pc) }}</div>
        <div class="tr"><span style="color:var(--brand-500)">web</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">已发布 / 草稿</div>
        <div class="nm num">{{ fmtCount(counts.published) }} <span class="num-sep">/</span> {{ fmtCount(counts.draft) }}</div>
        <div class="tr"><span style="color:var(--success)">线上 {{ counts.published }}</span></div>
      </div>
    </div>

    <div class="filter-bar flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="scene-seg">
          <button :class="{ on: device === 'uniapp' }" @click="device = 'uniapp'">
            <i class="i-lucide:smartphone" /> 移动端
            <span class="seg-count">{{ counts.uniapp }}</span>
          </button>
          <button :class="{ on: device === 'pc' }" @click="device = 'pc'">
            <i class="i-lucide:monitor" /> PC 端
            <span class="seg-count">{{ counts.pc }}</span>
          </button>
        </div>
        <el-radio-group v-model="pageType" size="default">
          <el-radio-button value="home">首页</el-radio-button>
          <el-radio-button value="custom">专题</el-radio-button>
        </el-radio-group>
      </div>
      <el-input v-model="keyword" placeholder="搜索页面名称" clearable style="width: 240px" />
    </div>

    <div v-if="filteredPages.length" class="row-cards-4">
      <div v-for="p in filteredPages" :key="p.id" class="page-card">
        <div class="pg-cover">
          <img
            :src="appStore.getImageUrl(p.platform === 'pc' ? '/storage/diy-defaults/page-cover-pc.png' : '/storage/diy-defaults/page-cover-mobile.png')"
            :alt="p.title"
            class="pg-cover-img"
          />
          <div class="pg-tag-r">
            <span :class="['tag', p.is_published ? 'tag-green' : 'tag-amber']">
              {{ p.is_published ? '已发布' : '草稿' }}
            </span>
          </div>
          <div class="pg-tag-l flex flex-col gap-1">
            <span :class="['tag', p.platform === 'pc' ? 'tag-blue' : 'tag-purple']">
              {{ p.platform === 'pc' ? 'PC' : '移动' }}
            </span>
            <span v-if="p.is_default && p.page_type === 'home'" class="tag tag-success">生效中</span>
          </div>
        </div>

        <div class="pg-body">
          <div class="pg-title">{{ p.title }}</div>
          <div class="pg-meta num">P-{{ String(p.id).padStart(2, '0') }} · 更新 {{ (p.updated_at || '').slice(0, 10) }}</div>
          <div class="pg-acts flex justify-end gap-1">
            <el-button text type="primary" size="small" v-has-perm="['diy.page.update']" @click="handleEdit(p)">编辑</el-button>
            <el-button
              v-if="p.page_type === 'home' && !p.is_default"
              text size="small" type="success"
              v-has-perm="['diy.page.set_default']"
              @click="handleSetDefault(p)"
            >设为默认</el-button>
            <el-button text size="small" :type="p.is_published ? 'warning' : 'success'" v-has-perm="['diy.page.publish']" @click="handlePublish(p)">
              {{ p.is_published ? '取消发布' : '发布' }}
            </el-button>
            <el-button text type="danger" size="small" v-has-perm="['diy.page.delete']" @click="handleDelete(p)">删除</el-button>
          </div>
        </div>
      </div>
    </div>

    <el-empty v-else-if="!loading" description="暂无页面,点击右上角「新建页面」创建">
      <el-button type="primary" v-has-perm="['diy.page.create']" @click="showCreateDialog = true">
        创建{{ device === 'uniapp' ? '移动端' : 'PC端' }}{{ pageType === 'home' ? '首页' : '专题' }}
      </el-button>
    </el-empty>

    <CreatePageDialog
      v-if="showCreateDialog"
      :default-platform="device"
      :default-page-type="pageType"
      @close="showCreateDialog = false"
      @created="onCreated"
    />

    <PublishDialog
      v-if="publishTarget"
      :page-title="publishTarget.title"
      @confirm="onPublishConfirm"
      @close="onPublishClose"
    />
  </div>
</template>

<style lang="scss" scoped>
.diy-page-container { padding: 0; }

.scene-seg {
  display: inline-flex;
  background: var(--ink-50);
  padding: 3px;
  border-radius: 8px;

  button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 0;
    background: transparent;
    padding: 6px 14px;
    font-size: 13px;
    color: var(--ink-600);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;

    i { font-size: 14px; }

    .seg-count {
      font-family: var(--font-num);
      font-size: 11px;
      color: var(--ink-400);
      margin-left: 2px;
    }

    &.on {
      background: #fff;
      color: var(--brand-600);
      box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
      font-weight: 600;
      .seg-count { color: var(--brand-500); }
    }

    &:hover:not(.on) { color: var(--brand-500); }
  }
}

.page-card {
  background: #fff;
  border: 1px solid var(--ink-100);
  border-radius: var(--r-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: all .15s ease;

  &:hover {
    box-shadow: 0 6px 20px rgba(15, 23, 42, .08);
    transform: translateY(-2px);
  }
}

.row-cards-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.pg-cover {
  height: 500px;
  position: relative;
  overflow: hidden;
  background: var(--ink-50);
}

.pg-cover-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.pg-tag-l { position: absolute; top: 8px; left: 8px; z-index: 1; }
.pg-tag-r { position: absolute; top: 8px; right: 8px; z-index: 1; }

.tag-success { background: #d1fae5; color: #065f46; padding: 2px 6px; border-radius: 4px; font-size: 11px; }

.pg-body { padding: 12px 14px 14px; }
.pg-title { font-size: 13px; font-weight: 600; color: var(--ink-900); }
.pg-meta { font-size: 11px; color: var(--ink-400); margin-top: 3px; }
.pg-acts { margin-top: 10px; }
.num-sep { color: var(--ink-300); font-weight: 400; margin: 0 2px; }
</style>
