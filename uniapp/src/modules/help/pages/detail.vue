<template>
  <view class="help-detail">
    <view v-if="loading" class="empty">加载中...</view>
    <view v-else-if="!detail" class="empty">
      <text>帮助不存在或已下架</text>
    </view>
    <view v-else class="content">
      <view class="title">{{ detail.title }}</view>
      <view class="meta">
        {{ detail.category_name }} · 阅读 {{ detail.view_count }} · {{ detail.updated_at?.slice(0, 10) }}
      </view>
      <view v-if="detail.summary" class="summary">{{ detail.summary }}</view>
      <rich-text :nodes="detail.content" class="rich" />

      <view v-if="related.length" class="related">
        <view class="related-title">相关帮助</view>
        <view
          v-for="r in related"
          :key="r.id"
          class="related-item"
          @tap="goDetail(r.id)"
        >• {{ r.title }}</view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { helpApi, type HelpDetail, type HelpItem } from '@/api/help'

const detail = ref<HelpDetail | null>(null)
const related = ref<HelpItem[]>([])
const loading = ref(true)

async function loadDetail(id: string | number) {
  loading.value = true
  try {
    const res = await helpApi.getDetail(id)
    detail.value = res
    uni.setNavigationBarTitle({ title: res.title })
    const relRes = await helpApi.getList({ category_id: res.category_id, page_size: 6 })
    related.value = relRes.list.filter(r => r.id !== res.id).slice(0, 5)
  } catch {
    detail.value = null
  } finally {
    loading.value = false
  }
}

function goDetail(id: number) {
  uni.redirectTo({ url: `/modules/help/pages/detail?id=${id}` })
}

onLoad((options: any) => {
  const id = options?.id
  if (id) loadDetail(id)
  else loading.value = false
})
</script>

<style scoped lang="scss">
.help-detail { min-height: 100vh; background: #f5f7fa; padding: 24rpx; }
.empty { padding: 200rpx 0; text-align: center; font-size: 28rpx; color: #9ca3af; }
.content { background: #fff; border-radius: 8rpx; padding: 32rpx; }
.title { font-size: 36rpx; font-weight: 600; color: #1f2937; line-height: 1.4; }
.meta { font-size: 24rpx; color: #9ca3af; margin-top: 16rpx; }
.summary {
  margin-top: 24rpx; padding: 16rpx; background: #f9fafb; border-radius: 6rpx;
  font-size: 26rpx; color: #4b5563; line-height: 1.6;
}
.rich {
  margin-top: 32rpx; font-size: 28rpx; color: #333; line-height: 1.8;
}
.related { margin-top: 48rpx; padding-top: 24rpx; border-top: 1rpx solid #f0f0f0; }
.related-title { font-size: 28rpx; font-weight: 600; color: #1f2937; margin-bottom: 16rpx; }
.related-item { font-size: 26rpx; color: #4b5563; padding: 8rpx 0; }
</style>
