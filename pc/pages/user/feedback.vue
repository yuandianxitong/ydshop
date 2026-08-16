<template>
  <div>
    <h2 class="text-xl font-bold text-gray-900 mb-6">意见反馈</h2>

    <div class="card p-6">
      <div class="flex flex-wrap gap-2 mb-5">
        <button
          v-for="option in typeOptions"
          :key="option.value"
          type="button"
          class="type-btn"
          :class="{ 'type-btn--active': form.type === option.value }"
          @click="form.type = option.value"
        >
          {{ option.label }}
        </button>
      </div>

      <div class="mb-4">
        <label class="block text-sm text-gray-600 mb-1" for="feedback-content">反馈内容</label>
        <textarea
          id="feedback-content"
          v-model="form.content"
          maxlength="1000"
          rows="6"
          class="form-input resize-y min-h-32"
          placeholder="请描述操作步骤、预期结果和实际结果，信息越完整越容易定位问题。"
        />
        <div class="text-xs text-gray-400 text-right mt-1">{{ form.content.length }} / 1000</div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm text-gray-600 mb-1" for="feedback-contact">联系方式（选填）</label>
          <input
            id="feedback-contact"
            v-model="form.contact"
            maxlength="100"
            class="form-input"
            placeholder="手机号或邮箱"
          />
        </div>
        <div>
          <label class="block text-sm text-gray-600 mb-1">问题截图（最多 3 张，每张不超过 2MB）</label>
          <button
            type="button"
            class="btn-outline text-sm"
            :disabled="uploading || form.images.length >= 3"
            @click="fileInput?.click()"
          >
            {{ uploading ? '上传中...' : '选择图片' }}
          </button>
          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            multiple
            hidden
            @change="handleFiles"
          />
        </div>
      </div>

      <div v-if="form.images.length" class="flex gap-2 mb-4">
        <div v-for="(url, index) in form.images" :key="url" class="relative w-16 h-16 rounded-sm overflow-hidden bg-gray-100">
          <img :src="url" alt="反馈截图" class="w-full h-full object-cover" />
          <button
            type="button"
            class="absolute top-0.5 right-0.5 w-5 h-5 bg-black/60 text-white text-xs flex items-center justify-center"
            title="移除图片"
            @click="removeImage(index)"
          >
            <span class="i-carbon-close" />
          </button>
        </div>
      </div>

      <button class="btn-primary text-sm" :disabled="!canSubmit" @click="submitFeedback">
        {{ submitting ? '提交中...' : '提交反馈' }}
      </button>
    </div>

    <div class="flex items-center justify-between mt-8 mb-4">
      <h2 class="text-xl font-bold text-gray-900">我的反馈记录</h2>
      <button
        type="button"
        class="text-sm text-gray-500 hover:text-[var(--color-primary)] disabled:opacity-40"
        :disabled="historyLoading"
        @click="fetchHistory"
      >
        刷新
      </button>
    </div>

    <div class="card">
      <div v-if="historyLoading" class="py-10 text-center text-sm text-gray-400">加载中...</div>
      <div v-else-if="!history.length" class="py-10 text-center text-sm text-gray-400">暂无反馈记录</div>
      <div v-else>
        <article
          v-for="item in history"
          :key="item.id"
          class="px-6 py-4 border-b border-gray-100 last:border-b-0"
        >
          <div class="flex items-center justify-between gap-3 mb-2">
            <div class="flex items-center gap-2 min-w-0">
              <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-sm">{{ typeLabel(item.type) }}</span>
              <strong class="text-sm text-gray-800 font-medium">反馈 #{{ item.id }}</strong>
            </div>
            <span class="status-chip" :class="`status-${item.status}`">
              {{ item.status_text || statusLabel(item.status) }}
            </span>
          </div>
          <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ item.content }}</p>
          <div v-if="item.images?.length" class="flex gap-2 mt-2">
            <a
              v-for="url in item.images"
              :key="url"
              :href="url"
              target="_blank"
              rel="noopener noreferrer"
            >
              <img :src="url" alt="反馈附件" class="w-12 h-12 object-cover rounded-sm" />
            </a>
          </div>
          <div v-if="item.reply" class="mt-3 px-3 py-2 bg-gray-50 border-l-2 border-[var(--color-primary)]">
            <div class="text-xs text-gray-500 mb-1">客服回复</div>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ item.reply }}</p>
            <time v-if="item.replied_at" class="block mt-1 text-xs text-gray-400">{{ formatTime(item.replied_at) }}</time>
          </div>
          <time class="block mt-2 text-xs text-gray-400">提交于 {{ formatTime(item.created_at) }}</time>
        </article>
      </div>
    </div>

    <div v-if="totalPages > 1" class="flex justify-center items-center gap-3 mt-4">
      <button class="btn-outline text-sm" :disabled="page <= 1" @click="goPage(page - 1)">上一页</button>
      <span class="text-sm text-gray-400">{{ page }} / {{ totalPages }}</span>
      <button class="btn-outline text-sm" :disabled="page >= totalPages" @click="goPage(page + 1)">下一页</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useMessage } from 'naive-ui'
import { feedbackApi, type FeedbackItem } from '~/api/feedback'
import { getToken } from '~/composables/useRequest'

const toast = useMessage()
const fileInput = ref<HTMLInputElement | null>(null)
const submitting = ref(false)
const uploading = ref(false)
const historyLoading = ref(true)
const history = ref<FeedbackItem[]>([])
const page = ref(1)
const pageSize = 8
const total = ref(0)

const typeOptions = [
  { value: 'suggestion', label: '功能建议' },
  { value: 'bug', label: '问题反馈' },
  { value: 'complaint', label: '服务投诉' },
  { value: 'other', label: '其他' },
]

const form = reactive({
  type: 'suggestion',
  content: '',
  contact: '',
  images: [] as string[],
})

const canSubmit = computed(() => form.content.trim().length > 0 && !submitting.value && !uploading.value)
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / pageSize)))

const typeLabel = (value: string) => typeOptions.find(item => item.value === value)?.label || '其他'
const statusLabel = (status: number) => ['待处理', '处理中', '已回复', '已关闭'][status] || '未知'
const formatTime = (value?: string) => value ? value.replace('T', ' ').slice(0, 16) : '—'

const fetchHistory = async () => {
  historyLoading.value = true
  try {
    const res = await feedbackApi.getList({ page_no: page.value, page_size: pageSize })
    if (res.code === 200) {
      history.value = res.data.list || []
      total.value = Number(res.data.pagination?.total || 0)
    }
  } finally {
    historyLoading.value = false
  }
}

const handleFiles = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const slots = Math.max(0, 3 - form.images.length)
  const files = Array.from(input.files || []).slice(0, slots)
  input.value = ''
  if (!files.length) return

  uploading.value = true
  try {
    for (const file of files) {
      if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
        toast.warning(`${file.name} 不是支持的图片格式`)
        continue
      }
      if (file.size > 2 * 1024 * 1024) {
        toast.warning(`${file.name} 超过 2MB`)
        continue
      }
      const body = new FormData()
      body.append('file', file)
      const response = await fetch('/api/common/upload/image', {
        method: 'POST',
        headers: getToken() ? { Authorization: `Bearer ${getToken()}` } : undefined,
        body,
      })
      const result = await response.json() as { code: number; message: string; data?: { url?: string } }
      if (!response.ok || result.code !== 200 || !result.data?.url) throw new Error(result.message || '上传失败')
      form.images.push(result.data.url)
    }
  } catch (error) {
    toast.error(error instanceof Error ? error.message : '图片上传失败')
  } finally {
    uploading.value = false
  }
}

const removeImage = (index: number) => form.images.splice(index, 1)

const submitFeedback = async () => {
  if (!canSubmit.value) return
  submitting.value = true
  try {
    const res = await feedbackApi.submit({
      type: form.type,
      content: form.content.trim(),
      contact: form.contact.trim() || undefined,
      images: form.images.length ? [...form.images] : undefined,
    })
    if (res.code === 200) {
      toast.success('反馈已提交，感谢您的帮助')
      Object.assign(form, { type: 'suggestion', content: '', contact: '', images: [] })
      page.value = 1
      await fetchHistory()
    }
  } finally {
    submitting.value = false
  }
}

const goPage = (target: number) => {
  if (target < 1 || target > totalPages.value || target === page.value) return
  page.value = target
  fetchHistory()
}

onMounted(fetchHistory)
</script>

<style scoped>
.type-btn {
  padding: 6px 14px;
  font-size: 0.8125rem;
  color: #4b5563;
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 2px;
}
.type-btn--active {
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.status-chip {
  flex-shrink: 0;
  padding: 2px 8px;
  font-size: 12px;
  border-radius: 2px;
  background: #fff7df;
  color: #8a5c00;
}
.status-1 { color: #1d5fa7; background: #eaf4ff; }
.status-2 { color: #087a56; background: #e8f8f1; }
.status-3 { color: #6b7280; background: #f3f4f6; }
</style>
