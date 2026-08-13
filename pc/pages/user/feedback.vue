<template>
  <div class="feedback-page">
    <div class="page-heading">
      <div>
        <div class="eyebrow">SERVICE DESK</div>
        <h2>意见反馈</h2>
        <p>提交问题、建议或投诉，并在这里查看处理进度。</p>
      </div>
      <div class="response-note">
        <span class="i-carbon-time" /> 通常在 1–3 个工作日内回复
      </div>
    </div>

    <section class="submit-panel">
      <div class="panel-index">01</div>
      <div class="panel-body">
        <h3>告诉我们发生了什么</h3>
        <div class="type-options">
          <button
            v-for="option in typeOptions"
            :key="option.value"
            :class="{ active: form.type === option.value }"
            @click="form.type = option.value"
          >
            <span :class="option.icon" /> {{ option.label }}
          </button>
        </div>

        <label class="field-label" for="feedback-content">反馈内容</label>
        <div class="textarea-shell">
          <textarea
            id="feedback-content"
            v-model="form.content"
            maxlength="1000"
            placeholder="请描述操作步骤、预期结果和实际结果，信息越完整越容易定位问题。"
          />
          <span>{{ form.content.length }} / 1000</span>
        </div>

        <div class="form-grid">
          <div>
            <label class="field-label" for="feedback-contact">联系方式（选填）</label>
            <input id="feedback-contact" v-model="form.contact" maxlength="100" class="form-input" placeholder="手机号或邮箱" />
          </div>
          <div>
            <label class="field-label">问题截图（最多 3 张，每张不超过 2MB）</label>
            <button class="upload-btn" :disabled="uploading || form.images.length >= 3" @click="fileInput?.click()">
              <span :class="uploading ? 'i-carbon-circle-dash animate-spin' : 'i-carbon-image'" />
              {{ uploading ? '上传中...' : '选择图片' }}
            </button>
            <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden @change="handleFiles" />
          </div>
        </div>

        <div v-if="form.images.length" class="image-list">
          <div v-for="(url, index) in form.images" :key="url" class="image-item">
            <img :src="url" alt="反馈截图" />
            <button title="移除图片" @click="removeImage(index)"><span class="i-carbon-close" /></button>
          </div>
        </div>

        <div class="submit-row">
          <span>提交后可在下方持续查看客服回复。</span>
          <button class="submit-btn" :disabled="!canSubmit" @click="submitFeedback">
            {{ submitting ? '提交中...' : '提交反馈' }}
            <span class="i-carbon-arrow-right" />
          </button>
        </div>
      </div>
    </section>

    <section class="history-section">
      <div class="section-heading">
        <div><span>02</span><h3>我的反馈记录</h3></div>
        <button :disabled="historyLoading" @click="fetchHistory"><span class="i-carbon-renew" /> 刷新</button>
      </div>

      <div v-if="historyLoading" class="history-loading">正在加载处理记录...</div>
      <div v-else-if="!history.length" class="history-empty">暂无反馈记录，您的第一条建议会从这里开始。</div>
      <div v-else class="history-list">
        <article v-for="item in history" :key="item.id" class="history-card">
          <div class="history-top">
            <div>
              <span class="type-chip">{{ typeLabel(item.type) }}</span>
              <strong>反馈 #{{ item.id }}</strong>
            </div>
            <span class="status-chip" :class="`status-${item.status}`">{{ item.status_text || statusLabel(item.status) }}</span>
          </div>
          <p class="history-content">{{ item.content }}</p>
          <div v-if="item.images?.length" class="history-images">
            <a v-for="url in item.images" :key="url" :href="url" target="_blank" rel="noopener noreferrer"><img :src="url" alt="反馈附件" /></a>
          </div>
          <div v-if="item.reply" class="reply-box">
            <div><span class="i-carbon-customer-service" /> 客服回复</div>
            <p>{{ item.reply }}</p>
            <time v-if="item.replied_at">{{ formatTime(item.replied_at) }}</time>
          </div>
          <time class="created-time">提交于 {{ formatTime(item.created_at) }}</time>
        </article>
      </div>

      <div v-if="totalPages > 1" class="pagination">
        <button :disabled="page <= 1" @click="goPage(page - 1)">上一页</button>
        <span>{{ page }} / {{ totalPages }}</span>
        <button :disabled="page >= totalPages" @click="goPage(page + 1)">下一页</button>
      </div>
    </section>
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
  { value: 'suggestion', label: '功能建议', icon: 'i-carbon-idea' },
  { value: 'bug', label: '问题反馈', icon: 'i-carbon-debug' },
  { value: 'complaint', label: '服务投诉', icon: 'i-carbon-warning-alt' },
  { value: 'other', label: '其他', icon: 'i-carbon-chat' },
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
.feedback-page { color: #182230; }
.page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 22px; }.eyebrow { margin-bottom: 5px; color: var(--color-primary); font-size: 10px; font-weight: 800; letter-spacing: .18em; }.page-heading h2 { margin: 0; font-size: 24px; font-weight: 750; letter-spacing: -.02em; }.page-heading p { margin: 6px 0 0; color: #87909f; font-size: 13px; }.response-note { display: flex; align-items: center; gap: 6px; color: #798495; font-size: 12px; }
.submit-panel { position: relative; display: grid; grid-template-columns: 54px 1fr; overflow: hidden; background: #fff; border: 1px solid #e5e9ef; border-radius: 12px; box-shadow: 0 12px 32px rgba(25, 39, 60, .05); }.panel-index { padding-top: 24px; color: #fff; font-size: 14px; font-weight: 800; text-align: center; background: #192333; }.panel-body { padding: 24px 26px 22px; }.panel-body h3 { margin: 0 0 16px; font-size: 16px; font-weight: 700; }.type-options { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }.type-options button { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; color: #687385; font-size: 12px; border: 1px solid #e1e5eb; border-radius: 20px; transition: all .16s ease; }.type-options button.active { color: var(--color-primary); background: color-mix(in srgb, var(--color-primary) 8%, white); border-color: color-mix(in srgb, var(--color-primary) 38%, white); }
.field-label { display: block; margin-bottom: 7px; color: #5d6879; font-size: 12px; font-weight: 600; }.textarea-shell { position: relative; }.textarea-shell textarea { width: 100%; min-height: 130px; padding: 13px 14px 28px; color: #293548; font-size: 13px; line-height: 1.7; resize: vertical; background: #fafbfc; border: 1px solid #dfe4eb; border-radius: 8px; outline: none; }.textarea-shell textarea:focus { background: #fff; border-color: var(--color-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 10%, transparent); }.textarea-shell > span { position: absolute; right: 11px; bottom: 8px; color: #a0a8b4; font-size: 10px; }.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 17px; }.form-input { width: 100%; height: 38px; padding: 0 11px; color: #334155; font-size: 12px; border: 1px solid #dfe4eb; border-radius: 7px; outline: none; }.form-input:focus { border-color: var(--color-primary); }.upload-btn { display: inline-flex; align-items: center; gap: 7px; height: 38px; padding: 0 13px; color: #536071; font-size: 12px; background: #f7f8fa; border: 1px dashed #cfd5de; border-radius: 7px; }.upload-btn:disabled { opacity: .55; cursor: not-allowed; }
.image-list { display: flex; gap: 9px; margin-top: 13px; }.image-item { position: relative; width: 70px; height: 70px; overflow: hidden; background: #f1f3f6; border-radius: 7px; }.image-item img { width: 100%; height: 100%; object-fit: cover; }.image-item button { position: absolute; top: 3px; right: 3px; display: grid; place-items: center; width: 20px; height: 20px; color: #fff; font-size: 12px; background: rgba(20, 28, 40, .72); border-radius: 50%; }.submit-row { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding-top: 18px; margin-top: 20px; color: #9199a5; font-size: 11px; border-top: 1px solid #eef0f3; }.submit-btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 17px; color: #fff; font-size: 12px; font-weight: 650; background: var(--color-primary); border-radius: 7px; }.submit-btn:disabled { opacity: .5; cursor: not-allowed; }
.history-section { margin-top: 30px; }.section-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }.section-heading > div { display: flex; align-items: center; gap: 10px; }.section-heading h3 { margin: 0; font-size: 16px; }.section-heading > div > span { color: var(--color-primary); font-size: 11px; font-weight: 800; }.section-heading > button { display: inline-flex; align-items: center; gap: 5px; color: #7b8594; font-size: 11px; }.history-list { display: flex; flex-direction: column; gap: 10px; }.history-card { position: relative; padding: 17px 19px; background: #fff; border: 1px solid #e7eaf0; border-radius: 9px; }.history-top { display: flex; align-items: center; justify-content: space-between; gap: 14px; }.history-top > div { display: flex; align-items: center; gap: 9px; }.history-top strong { color: #354155; font-size: 12px; }.type-chip, .status-chip { padding: 3px 7px; font-size: 10px; border-radius: 4px; }.type-chip { color: #667386; background: #f1f3f6; }.status-chip { color: #8a5c00; background: #fff7df; }.status-1 { color: #1d5fa7; background: #eaf4ff; }.status-2 { color: #087a56; background: #e8f8f1; }.status-3 { color: #687385; background: #eef0f3; }.history-content { margin: 12px 0 0; color: #566173; font-size: 13px; line-height: 1.7; white-space: pre-wrap; }.history-images { display: flex; gap: 7px; margin-top: 10px; }.history-images img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }.reply-box { padding: 12px 14px; margin-top: 13px; background: #f7faf9; border-left: 3px solid #13a071; border-radius: 0 6px 6px 0; }.reply-box > div { display: flex; align-items: center; gap: 5px; color: #16825f; font-size: 11px; font-weight: 700; }.reply-box p { margin: 6px 0 0; color: #4f5f59; font-size: 12px; line-height: 1.65; white-space: pre-wrap; }.reply-box time, .created-time { color: #a0a7b1; font-size: 10px; }.reply-box time { display: block; margin-top: 6px; }.created-time { display: block; margin-top: 11px; }.history-loading, .history-empty { padding: 38px 20px; color: #929aa7; font-size: 12px; text-align: center; background: #fff; border: 1px dashed #dfe3e9; border-radius: 9px; }.pagination { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 18px; color: #8b94a2; font-size: 11px; }.pagination button { padding: 6px 11px; background: #fff; border: 1px solid #e0e4ea; border-radius: 5px; }.pagination button:disabled { opacity: .4; cursor: not-allowed; }
@media (max-width: 800px) { .form-grid { grid-template-columns: 1fr; }.submit-row { align-items: flex-start; flex-direction: column; }.page-heading { align-items: flex-start; flex-direction: column; } }
</style>
