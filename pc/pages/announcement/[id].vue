<template>
  <div class="announcement-page">
    <div class="announcement-shell">
      <NuxtLink to="/" class="back-link"><span class="i-carbon-arrow-left" /> 返回商城首页</NuxtLink>

      <div v-if="loading" class="loading-card">
        <span class="i-carbon-circle-dash animate-spin" /> 正在加载公告...
      </div>

      <div v-else-if="!detail" class="missing-card">
        <span class="i-carbon-document-unknown" />
        <h2>公告不存在或已下架</h2>
        <p>这条公告可能已撤回，请返回首页查看其他内容。</p>
      </div>

      <article v-else class="announcement-card">
        <header>
          <div class="notice-mark"><span>NOTICE</span><strong>{{ String(detail.id).padStart(3, '0') }}</strong></div>
          <div class="headline">
            <span class="type-chip">{{ detail.type_text || '平台公告' }}</span>
            <h1>{{ detail.title }}</h1>
            <div class="meta"><span class="i-carbon-calendar" /> 发布于 {{ formatTime(detail.publish_at || detail.created_at) }}</div>
          </div>
        </header>
        <div class="content-divider"><span /></div>
        <div class="announcement-content" v-html="sanitizedContent" />
        <footer>
          <span>元点Shop 运营团队</span>
          <NuxtLink to="/">继续逛商城 <span class="i-carbon-arrow-right" /></NuxtLink>
        </footer>
      </article>
    </div>
  </div>
</template>

<script setup lang="ts">
import DOMPurify from 'dompurify'
import { announcementApi, type AnnouncementItem } from '~/api/announcement'

const route = useRoute()
const detail = ref<AnnouncementItem | null>(null)
const loading = ref(true)
const id = computed(() => String(route.params.id || ''))
const sanitizedContent = computed(() => {
  if (!detail.value?.content) return ''
  return import.meta.client ? DOMPurify.sanitize(detail.value.content) : detail.value.content
})
const formatTime = (value?: string) => value ? value.replace('T', ' ').slice(0, 16) : '—'

const loadDetail = async () => {
  loading.value = true
  try {
    const res = await announcementApi.getDetail(id.value)
    if (res.code === 200) {
      detail.value = res.data
      useHead({ title: `${res.data.title} - 商城公告` })
    } else {
      detail.value = null
    }
  } finally {
    loading.value = false
  }
}

watch(id, loadDetail)
onMounted(loadDetail)
</script>

<style scoped>
.announcement-page { min-height: 70vh; padding: 34px 20px 60px; background: linear-gradient(180deg, #f1f4f7 0, #f7f8fa 210px, #f7f8fa 100%); }.announcement-shell { max-width: 860px; margin: 0 auto; }.back-link { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 18px; color: #687486; font-size: 12px; }.back-link:hover { color: var(--color-primary); }.announcement-card { overflow: hidden; background: #fff; border: 1px solid #e2e6eb; border-radius: 3px; box-shadow: 0 22px 55px rgba(31, 43, 59, .09); }.announcement-card header { display: grid; grid-template-columns: 115px 1fr; min-height: 190px; }.notice-mark { display: flex; flex-direction: column; justify-content: space-between; padding: 27px 22px; color: #d6dde6; background: #1b2635; }.notice-mark span { font-size: 10px; font-weight: 750; letter-spacing: .2em; writing-mode: vertical-rl; }.notice-mark strong { color: #fff; font-size: 34px; font-weight: 300; letter-spacing: -.06em; }.headline { padding: 36px 44px 30px; }.type-chip { display: inline-block; padding: 4px 8px; color: var(--color-primary); font-size: 10px; font-weight: 700; letter-spacing: .08em; background: color-mix(in srgb, var(--color-primary) 8%, white); border-left: 2px solid var(--color-primary); }.headline h1 { max-width: 620px; margin: 18px 0 16px; color: #202c3c; font-size: 30px; font-weight: 720; line-height: 1.35; letter-spacing: -.025em; }.meta { display: flex; align-items: center; gap: 6px; color: #98a1ad; font-size: 11px; }.content-divider { display: flex; align-items: center; height: 1px; margin: 0 44px; background: #edf0f3; }.content-divider span { width: 70px; height: 3px; background: var(--color-primary); }.announcement-content { min-height: 230px; padding: 36px 50px 48px; color: #485466; font-size: 14px; line-height: 1.9; }.announcement-content :deep(p) { margin: 0 0 16px; }.announcement-content :deep(h1), .announcement-content :deep(h2), .announcement-content :deep(h3) { margin: 26px 0 12px; color: #263244; font-weight: 700; }.announcement-content :deep(img) { max-width: 100%; margin: 18px auto; border-radius: 2px; }.announcement-content :deep(a) { color: var(--color-primary); text-decoration: underline; }.announcement-card footer { display: flex; align-items: center; justify-content: space-between; padding: 17px 44px; color: #929ba8; font-size: 11px; background: #fafbfc; border-top: 1px solid #edf0f3; }.announcement-card footer a { display: inline-flex; align-items: center; gap: 6px; color: #536173; font-weight: 600; }.loading-card, .missing-card { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 320px; color: #8d97a5; background: #fff; border: 1px solid #e3e7ec; }.loading-card { flex-direction: row; gap: 8px; font-size: 13px; }.missing-card > span { color: #bdc4ce; font-size: 46px; }.missing-card h2 { margin: 13px 0 5px; color: #536071; font-size: 18px; }.missing-card p { margin: 0; font-size: 12px; }
@media (max-width: 650px) { .announcement-card header { grid-template-columns: 1fr; }.notice-mark { display: none; }.headline { padding: 28px 24px 24px; }.headline h1 { font-size: 24px; }.content-divider { margin: 0 24px; }.announcement-content { padding: 28px 24px 38px; }.announcement-card footer { align-items: flex-start; flex-direction: column; gap: 10px; padding: 16px 24px; } }
</style>
