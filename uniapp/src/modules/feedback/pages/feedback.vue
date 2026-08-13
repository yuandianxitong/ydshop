<template>
  <d-page :safe-area="true">
    <view class="feedback-page">
      <view class="tabs">
        <view
          v-for="tab in tabs"
          :key="tab.value"
          class="tabs__item"
          :class="{ 'tabs__item--active': activeTab === tab.value }"
          @tap="switchSection(tab.value)"
        >
          {{ tab.label }}
        </view>
      </view>

      <view v-if="activeTab === 'submit'" class="submit-panel">
        <view class="card">
          <view class="card__title">反馈类型</view>
          <view class="type-list">
            <view
              v-for="item in typeOptions"
              :key="item.value"
              class="type-tag"
              :class="{ 'type-tag--active': form.type === item.value }"
              @tap="form.type = item.value"
            >
              {{ item.label }}
            </view>
          </view>
        </view>

        <view class="card">
          <view class="card__title">反馈内容</view>
          <view class="textarea-wrap">
            <textarea
              v-model="form.content"
              class="textarea"
              placeholder="请详细描述您遇到的问题或建议..."
              placeholder-class="placeholder"
              :maxlength="1000"
              :auto-height="false"
            />
            <text class="textarea-count">{{ form.content.length }} / 1000</text>
          </view>
        </view>

        <view class="card">
          <view class="card__title">
            上传图片
            <text class="card__hint">最多 3 张</text>
          </view>
          <view class="image-upload">
            <view v-for="(img, index) in form.images" :key="img" class="image-item">
              <image
                :src="appStore.getImageUrl(img)"
                mode="aspectFill"
                class="preview-image"
                @tap="previewImages(form.images, index)"
              />
              <view class="image-delete" @tap="removeImage(index)">
                <d-icon name="trash" size="24rpx" color="#fff" />
              </view>
            </view>
            <view v-if="form.images.length < 3" class="image-add" @tap="handleUpload">
              <d-icon name="image" size="48rpx" color="#b7bcc5" />
              <text>添加图片</text>
            </view>
          </view>
        </view>

        <view class="card">
          <view class="card__title">
            联系方式
            <text class="card__hint">选填</text>
          </view>
          <input
            v-model="form.contact"
            :maxlength="100"
            placeholder="手机号或邮箱，方便我们联系您"
            class="contact-input"
            placeholder-class="placeholder"
          />
        </view>

        <view
          class="submit"
          :class="{ 'submit--disabled': !canSubmit, 'submit--loading': submitting }"
          @tap="canSubmit && handleSubmit()"
        >
          <text class="submit__text">{{ submitting ? '提交中...' : '提交反馈' }}</text>
        </view>
      </view>

      <view v-else class="history-panel">
        <view class="history-summary">
          <view>
            <text class="history-summary__count">{{ total }} 条反馈记录</text>
            <text class="history-summary__hint">处理结果会同步到消息中心</text>
          </view>
          <view class="history-summary__new" @tap="switchSection('submit')">新建反馈</view>
        </view>

        <scroll-view scroll-y class="history-scroll" @scrolltolower="getHistory">
          <view
            v-for="item in historyList"
            :key="item.id"
            class="feedback-card"
            :class="{ 'feedback-card--expanded': expandedId === item.id }"
            @tap="toggleDetail(item)"
          >
            <view class="feedback-card__top">
              <text class="feedback-card__type">{{ typeLabel(item.type) }}</text>
              <text class="feedback-card__status" :class="`status-${item.status}`">
                {{ statusLabel(item.status) }}
              </text>
            </view>
            <text class="feedback-card__content">{{ item.content }}</text>

            <view v-if="item.images?.length" class="history-images">
              <image
                v-for="(image, index) in item.images.slice(0, 3)"
                :key="image"
                :src="appStore.getImageUrl(image)"
                mode="aspectFill"
                @tap.stop="previewImages(item.images, index)"
              />
            </view>

            <view class="feedback-card__footer">
              <text>{{ formatTime(item.created_at) }}</text>
              <view class="feedback-card__expand">
                <text>{{ expandedId === item.id ? '收起' : '查看详情' }}</text>
                <d-icon name="arrow-right" size="24rpx" color="#8b95a5" />
              </view>
            </view>

            <view v-if="expandedId === item.id" class="feedback-detail" @tap.stop>
              <view v-if="detailLoadingId === item.id" class="feedback-detail__loading">正在加载处理结果...</view>
              <template v-else>
                <view v-if="currentDetail(item).reply" class="reply-block">
                  <view class="reply-block__title">
                    <view class="reply-block__dot" />
                    平台回复
                  </view>
                  <text class="reply-block__content">{{ currentDetail(item).reply }}</text>
                  <text v-if="currentDetail(item).replied_at" class="reply-block__time">
                    {{ formatTime(currentDetail(item).replied_at || '') }}
                  </text>
                </view>
                <view v-else class="pending-block">
                  <d-icon name="time" size="34rpx" color="#d97706" />
                  <text>{{ item.status === 3 ? '该反馈已关闭' : '工作人员正在处理中，请耐心等待' }}</text>
                </view>
                <view v-if="currentDetail(item).contact" class="feedback-detail__meta">
                  <text>预留联系方式</text>
                  <text>{{ currentDetail(item).contact }}</text>
                </view>
              </template>
            </view>
          </view>

          <d-list-loader
            :loading="historyLoading"
            :finished="historyFinished"
            :total="total"
            empty-text="还没有反馈记录"
          />
        </scroll-view>
      </view>
    </view>
  </d-page>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { feedbackApi, type FeedbackInfo } from '@/api/feedback'
import { usePaging } from '@/hooks/usePaging'
import { useUpload } from '@/hooks/useUpload'
import { useAppStore } from '@/store/app.store'

type Section = 'submit' | 'history'

const appStore = useAppStore()
const { chooseAndUpload } = useUpload({ maxSize: 2 })
const tabs: Array<{ label: string; value: Section }> = [
  { label: '提交反馈', value: 'submit' },
  { label: '反馈记录', value: 'history' },
]
const typeOptions = [
  { label: '功能建议', value: 'suggestion' },
  { label: '问题反馈', value: 'bug' },
  { label: '投诉', value: 'complaint' },
  { label: '其他', value: 'other' },
]

const activeTab = ref<Section>('submit')
const submitting = ref(false)
const expandedId = ref(0)
const detailLoadingId = ref(0)
const details = reactive<Record<number, FeedbackInfo>>({})

const form = reactive({
  type: 'suggestion',
  content: '',
  images: [] as string[],
  contact: '',
})

const {
  list: historyList,
  loading: historyLoading,
  finished: historyFinished,
  total,
  getList: getHistory,
  refresh: refreshHistory,
} = usePaging<FeedbackInfo>({
  fetchFun: params => feedbackApi.getList(params),
  size: 10,
})

const canSubmit = computed(() => {
  const length = form.content.trim().length
  return length > 0 && length <= 1000 && !submitting.value
})

function switchSection(section: Section) {
  activeTab.value = section
  if (section === 'history' && historyList.value.length === 0 && !historyLoading.value) {
    refreshHistory()
  }
}

async function handleUpload() {
  if (form.images.length >= 3) return
  try {
    const path = await chooseAndUpload()
    if (!form.images.includes(path)) form.images.push(path)
  } catch {
    // 用户取消或请求层已提示错误
  }
}

function removeImage(index: number) {
  form.images.splice(index, 1)
}

function previewImages(images: string[], index: number) {
  const urls = images.map(image => appStore.getImageUrl(image))
  if (urls.length === 0) return
  uni.previewImage({ urls, current: urls[index] })
}

function resetForm() {
  form.type = 'suggestion'
  form.content = ''
  form.images = []
  form.contact = ''
}

async function handleSubmit() {
  const content = form.content.trim()
  if (!content) {
    uni.showToast({ title: '请输入反馈内容', icon: 'none' })
    return
  }
  if (content.length > 1000) {
    uni.showToast({ title: '反馈内容不能超过 1000 字', icon: 'none' })
    return
  }
  if (form.contact.trim().length > 100) {
    uni.showToast({ title: '联系方式不能超过 100 字', icon: 'none' })
    return
  }
  if (!canSubmit.value) return

  submitting.value = true
  try {
    const feedback = await feedbackApi.submit({
      type: form.type,
      content,
      images: form.images.length > 0 ? form.images : undefined,
      contact: form.contact.trim() || undefined,
    })
    uni.showToast({ title: '提交成功', icon: 'success' })
    resetForm()
    activeTab.value = 'history'
    details[feedback.id] = feedback
    expandedId.value = feedback.id
    await refreshHistory()
  } catch {
    // 请求层统一提示
  } finally {
    submitting.value = false
  }
}

async function toggleDetail(item: FeedbackInfo) {
  if (expandedId.value === item.id) {
    expandedId.value = 0
    return
  }
  expandedId.value = item.id
  if (details[item.id]) return

  detailLoadingId.value = item.id
  try {
    details[item.id] = await feedbackApi.getDetail(item.id)
  } catch {
    expandedId.value = 0
  } finally {
    detailLoadingId.value = 0
  }
}

function currentDetail(item: FeedbackInfo): FeedbackInfo {
  return details[item.id] || item
}

function typeLabel(type: string): string {
  return typeOptions.find(item => item.value === type)?.label || '其他'
}

function statusLabel(status: number): string {
  return ['待处理', '处理中', '已回复', '已关闭'][status] || '未知状态'
}

function formatTime(value: string): string {
  return value ? value.replace('T', ' ').slice(0, 16) : ''
}

async function openRequestedFeedback(id: number) {
  if (id <= 0) return
  let item = historyList.value.find(feedback => feedback.id === id)
  if (!item) {
    try {
      item = await feedbackApi.getDetail(id)
      historyList.value.unshift(item)
      details[id] = item
    } catch {
      return
    }
  }
  await toggleDetail(item)
}

onLoad(async options => {
  const requestedType = String(options?.type || '')
  if (typeOptions.some(item => item.value === requestedType)) {
    form.type = requestedType
  }
  const goodsName = String(options?.goods || '').trim()
  if (goodsName) {
    form.content = `商品咨询/反馈：${goodsName}\n`
  }

  const requestedId = Number.parseInt(String(options?.id || '0'), 10)
  if (options?.tab === 'history' || requestedId > 0) {
    activeTab.value = 'history'
    await refreshHistory()
    await openRequestedFeedback(requestedId)
  }
})
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

$radius: 12rpx;

.feedback-page {
  padding-bottom: 24rpx;
}

.tabs {
  display: flex;
  gap: 8rpx;
  margin-bottom: 20rpx;
  padding: 6rpx;
  background: #eef0f4;
  border-radius: $radius;

  &__item {
    flex: 1;
    padding: 18rpx 0;
    color: #7a8493;
    font-size: 28rpx;
    text-align: center;
    border-radius: 8rpx;

    &--active {
      color: var(--color-text, #{$text-color});
      font-weight: 600;
      background: #ffffff;
      box-shadow: 0 2rpx 8rpx rgba(31, 41, 55, 0.06);
    }
  }
}

.card {
  margin-bottom: 20rpx;
  padding: 24rpx;
  background: #ffffff;
  border: 1rpx solid $border-color;
  border-radius: $radius;

  &__title {
    margin-bottom: 20rpx;
    color: var(--color-text, #{$text-color});
    font-size: 28rpx;
    font-weight: 600;
  }

  &__hint {
    margin-left: 8rpx;
    color: $text-color-secondary;
    font-size: 22rpx;
    font-weight: 400;
  }
}

.type-list {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
}

.type-tag {
  padding: 12rpx 24rpx;
  color: #667085;
  font-size: 26rpx;
  background: #f4f6f8;
  border: 1rpx solid transparent;
  border-radius: $radius;

  &--active {
    color: var(--color-primary, #{$primary-color});
    font-weight: 550;
    background: rgba(41, 121, 255, 0.08);
    border-color: rgba(41, 121, 255, 0.28);
  }
}

.textarea-wrap {
  position: relative;
  padding: 16rpx 20rpx 40rpx;
  background: #f7f8fa;
  border-radius: $radius;
}

.textarea {
  width: 100%;
  height: 240rpx;
  font-size: 28rpx;
  color: var(--color-text, #{$text-color});
  line-height: 1.6;
}

.textarea-count {
  position: absolute;
  right: 20rpx;
  bottom: 12rpx;
  color: #a1a8b3;
  font-size: 22rpx;
}

.placeholder {
  color: #c0c4cc;
  font-size: 28rpx;
}

.image-upload {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
}

.image-item {
  position: relative;
  width: 180rpx;
  height: 180rpx;
  overflow: hidden;
  border-radius: $radius;
}

.preview-image {
  width: 100%;
  height: 100%;
}

.image-delete {
  position: absolute;
  top: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44rpx;
  height: 44rpx;
  background: rgba(15, 23, 42, 0.65);
  border-radius: 0 0 0 $radius;
}

.image-add {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  width: 180rpx;
  height: 180rpx;
  color: #99a1ad;
  font-size: 22rpx;
  background: #f8f9fb;
  border: 2rpx dashed #d5dae2;
  border-radius: $radius;
}

.contact-input {
  width: 100%;
  height: 72rpx;
  padding: 0 4rpx;
  color: var(--color-text, #{$text-color});
  font-size: 28rpx;
  box-sizing: border-box;
}

.submit {
  height: 88rpx;
  margin-top: 12rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-primary, #{$primary-color});
  border-radius: $radius;
  box-shadow: 0 8rpx 20rpx -8rpx rgba(41, 121, 255, 0.45);

  &:active {
    opacity: 0.92;
  }

  &--disabled,
  &--loading {
    opacity: 0.55;
  }

  &__text {
    font-size: 30rpx;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: 2rpx;
  }
}

.history-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20rpx;

  &__count,
  &__hint {
    display: block;
  }

  &__count {
    color: #344054;
    font-size: 28rpx;
    font-weight: 600;
  }

  &__hint {
    margin-top: 6rpx;
    color: #98a2b3;
    font-size: 22rpx;
  }

  &__new {
    padding: 12rpx 20rpx;
    color: var(--color-primary, #{$primary-color});
    font-size: 24rpx;
    background: rgba(41, 121, 255, 0.08);
    border-radius: $radius;
  }
}

.history-scroll {
  height: calc(100vh - 260rpx - env(safe-area-inset-bottom));
}

.feedback-card {
  margin-bottom: 16rpx;
  padding: 24rpx;
  background: #fff;
  border: 1rpx solid $border-color;
  border-radius: $radius;

  &--expanded {
    border-color: rgba(41, 121, 255, 0.35);
  }

  &__top,
  &__footer,
  &__expand {
    display: flex;
    align-items: center;
  }

  &__top,
  &__footer {
    justify-content: space-between;
  }

  &__type {
    color: #667085;
    font-size: 24rpx;
  }

  &__status {
    padding: 6rpx 14rpx;
    font-size: 22rpx;
    border-radius: $radius;
  }

  &__content {
    display: -webkit-box;
    overflow: hidden;
    margin-top: 16rpx;
    color: #263244;
    font-size: 28rpx;
    line-height: 1.65;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
  }

  &__footer {
    margin-top: 18rpx;
    padding-top: 16rpx;
    color: #98a2b3;
    font-size: 22rpx;
    border-top: 1rpx solid #f0f2f5;
  }

  &__expand {
    gap: 6rpx;
  }
}

.status-0 { color: #9a6700; background: #fff7dc; }
.status-1 { color: #175cd3; background: #eff8ff; }
.status-2 { color: #067647; background: #ecfdf3; }
.status-3 { color: #667085; background: #f2f4f7; }

.history-images {
  display: flex;
  gap: 12rpx;
  margin-top: 16rpx;
}

.history-images image {
  width: 112rpx;
  height: 112rpx;
  border-radius: $radius;
}

.feedback-detail {
  margin-top: 18rpx;
  padding-top: 18rpx;
  border-top: 1rpx dashed #dce2e9;
}

.feedback-detail__loading {
  padding: 20rpx 0;
  color: #98a2b3;
  font-size: 24rpx;
  text-align: center;
}

.feedback-detail__meta {
  display: flex;
  justify-content: space-between;
  margin-top: 16rpx;
  color: #98a2b3;
  font-size: 22rpx;
}

.reply-block {
  padding: 20rpx 22rpx;
  background: #f4f8ff;
  border-radius: $radius;
}

.reply-block__title {
  display: flex;
  align-items: center;
  gap: 10rpx;
  color: #1d4f91;
  font-size: 24rpx;
  font-weight: 600;
}

.reply-block__dot {
  width: 10rpx;
  height: 10rpx;
  background: var(--color-primary, #{$primary-color});
  border-radius: 50%;
}

.reply-block__content {
  display: block;
  margin-top: 12rpx;
  color: #3f4c5f;
  font-size: 26rpx;
  line-height: 1.7;
  white-space: pre-wrap;
}

.reply-block__time {
  display: block;
  margin-top: 12rpx;
  color: #98a2b3;
  font-size: 22rpx;
}

.pending-block {
  display: flex;
  align-items: center;
  gap: 12rpx;
  padding: 18rpx 20rpx;
  color: #8a5b12;
  font-size: 24rpx;
  background: #fffaf0;
  border-radius: $radius;
}
</style>
