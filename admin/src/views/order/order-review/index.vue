<template>
  <div class="order-review-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">评价管理</h2>
        <p class="page-desc">商品评价的展示、回复与异常评价处理</p>
      </div>
    </div>

    <!-- 统计概览 -->
    <div class="mini-grid mini-grid-4 stats-row">
      <div class="kpi-mini tone-blue">
        <div class="lb"><span class="ic">总</span>评价总数</div>
        <div class="nm num">{{ formatCount(pagination.total) }}</div>
        <div class="tr">分页统计</div>
      </div>
      <div class="kpi-mini tone-teal">
        <div class="lb"><span class="ic">好</span>好评 (4-5星)</div>
        <div class="nm num">{{ formatCount(stats.positive) }}</div>
        <div class="tr">当前页内</div>
      </div>
      <div class="kpi-mini tone-amber">
        <div class="lb"><span class="ic">中</span>中差评 (1-3星)</div>
        <div class="nm num">{{ formatCount(stats.negative) }}</div>
        <div class="tr">当前页内</div>
      </div>
      <div class="kpi-mini tone-purple">
        <div class="lb"><span class="ic">复</span>已回复</div>
        <div class="nm num">{{ formatCount(stats.replied) }}</div>
        <div class="tr">当前页内</div>
      </div>
    </div>

    <!-- 星级分布 -->
    <div class="card rating-card">
      <div class="card-head">
        <div class="card-title">星级分布</div>
        <div class="card-meta">当前页 · 共 {{ formatCount(list.length) }} 条</div>
      </div>
      <div class="card-body">
        <div class="rating-grid">
          <!-- 左：分布条 -->
          <div class="rating-bars">
            <div v-for="row in ratingRows" :key="row.star" class="rating-row">
              <div class="rating-row-l">
                <span class="star">★</span>
                <span class="num">{{ row.star }}</span>
              </div>
              <div class="rating-bar">
                <div class="rating-bar-fill" :style="{ width: row.percent + '%', background: row.color }" />
              </div>
              <div class="rating-cnt num">{{ formatCount(row.count) }}</div>
              <div class="rating-pct num">{{ row.percent }}%</div>
            </div>
          </div>

          <!-- 右：综合评分 + 标签云 -->
          <div class="rating-summary">
            <div class="rating-score num">{{ averageRating.toFixed(2) }}</div>
            <div class="rating-stars">
              <i v-for="i in 5" :key="i" class="rating-star" :class="{ on: i <= Math.round(averageRating) }">★</i>
            </div>
            <div class="rating-summary-desc">综合评分（满分 5.0）</div>
            <div v-if="ratingTags.length" class="rating-tags">
              <span v-for="t in ratingTags" :key="t.label" class="chip">
                {{ t.label }}<span class="num"> ({{ t.count }})</span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- filter-bar 内置 tab + 搜索（与设计稿一致） -->
    <div class="filter-bar review-filter">
      <div class="seg review-tab-seg">
        <button :class="{ on: ratingTab === 'all' }" @click="setRatingTab('all')">全部</button>
        <button :class="{ on: ratingTab === 'good' }" @click="setRatingTab('good')">好评</button>
        <button :class="{ on: ratingTab === 'mid' }" @click="setRatingTab('mid')">中评</button>
        <button :class="{ on: ratingTab === 'bad' }" @click="setRatingTab('bad')">差评</button>
        <button :class="{ on: ratingTab === 'replied' }" @click="setRatingTab('replied')">已回复</button>
        <button :class="{ on: ratingTab === 'unreplied' }" @click="setRatingTab('unreplied')">未回复</button>
      </div>
      <span class="filter-sp" />
      <el-button @click="handleResetTab">重置</el-button>
    </div>

    <!-- 评价卡片流 -->
    <div v-loading="loading" class="card review-list">
      <div
        v-for="(row, idx) in list"
        :key="row.id"
        class="review-item"
        :class="{ 'review-item-first': idx === 0 }"
      >
        <div class="flex gap-3.5">
          <div class="review-avatar" :style="{ background: avatarGradient(row) }">
            {{ avatarInitial(row) }}
          </div>
          <div class="review-main">
            <div class="flex justify-between items-start gap-3">
              <div class="review-head-l">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="review-user">{{ row.user_nickname || row.user_name || '匿名用户' }}</span>
                  <span class="review-stars">
                    <i
                      v-for="i in 5"
                      :key="i"
                      class="review-star"
                      :class="{ 'review-star-on': i <= Number(row.rating || 0) }"
                    >★</i>
                  </span>
                  <el-tag :type="ratingTagType(row.rating)" size="small">{{ ratingTagLabel(row.rating) }}</el-tag>
                </div>
                <div class="review-time num">{{ row.created_at }}</div>
              </div>
              <div class="tbl-acts review-acts">
                <el-button type="primary" size="small" text @click="handleReply(row)">
                  {{ row.reply_content ? '修改回复' : '回复' }}
                </el-button>
              </div>
            </div>

            <div class="review-text">{{ row.content || '无文字评价' }}</div>

            <div v-if="row.images && row.images.length" class="flex gap-1.5 flex-wrap mt-2.5">
              <el-image
                v-for="(img, j) in row.images"
                :key="j"
                :src="img"
                class="review-image"
                fit="cover"
                :preview-src-list="row.images"
                :initial-index="j"
              />
            </div>

            <div v-if="row.spec_text" class="review-goods-bar">
              <span class="review-goods-label">商品：</span>
              <span class="review-goods-name">{{ row.goods_name || '—' }}</span>
              <span class="review-goods-spec num">{{ row.spec_text }}</span>
            </div>
            <div v-else-if="row.goods_name" class="review-goods-bar">
              <span class="review-goods-label">商品：</span>
              <span class="review-goods-name">{{ row.goods_name }}</span>
            </div>

            <div v-if="row.reply_content" class="review-reply">
              <div class="review-reply-label">商家回复</div>
              <div class="review-reply-text">{{ row.reply_content }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!loading && !list.length" class="review-empty">
        <el-empty description="暂无评价" />
      </div>
    </div>

    <!-- 分页 -->
    <div v-if="list.length" class="review-pagination">
      <el-pagination
        :current-page="pagination.page"
        :page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <!-- 回复对话框 -->
    <el-dialog v-model="replyDialogVisible" title="回复评价" width="520px" @close="resetReplyForm">
      <div v-if="currentReview" class="review-preview">
        <div class="review-rating">
          <i
            v-for="i in 5"
            :key="i"
            class="i-svg:star"
            :class="['star', i <= currentReview.rating ? 'star--active' : 'star--inactive']"
          />
        </div>
        <p class="review-original">{{ currentReview.content || '无文字评价' }}</p>
      </div>

      <el-form ref="replyFormRef" :model="replyForm" :rules="replyRules" label-width="80px" style="margin-top: 16px">
        <el-form-item label="回复内容" prop="reply_content">
          <el-input
            v-model="replyForm.reply_content"
            type="textarea"
            :rows="5"
            placeholder="请输入回复内容"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="replyDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="replyLoading" @click="confirmReply">提交回复</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="OrderReview">
import { ElMessage } from 'element-plus'
import { computed, reactive, ref } from 'vue'

import { orderReviewApi } from '@/api/order-review'
import { useListPage } from '@/hooks/useListPage'

interface ReviewSearchForm {
  rating?: number
  has_reply?: number
}

const {
  list,
  loading,
  pagination,
  searchForm,
  getList,
  handleSearch,
  resetSearch,
  handlePageChange,
  handleSizeChange,
} = useListPage<Record<string, any>, ReviewSearchForm>({
  fetchFn: (params) => orderReviewApi.getReviewList(params),
  defaultSearchForm: { rating: undefined, has_reply: undefined },
})

const handleReset = () => resetSearch()

// Rating tab 状态
const ratingTab = ref<'all' | 'good' | 'mid' | 'bad' | 'replied' | 'unreplied'>('all')

const setRatingTab = (tab: typeof ratingTab.value) => {
  ratingTab.value = tab
  // 清空旧条件
  searchForm.rating = undefined
  searchForm.has_reply = undefined
  // 设置新条件
  if (tab === 'good') searchForm.rating = 5
  else if (tab === 'mid') searchForm.rating = 3
  else if (tab === 'bad') searchForm.rating = 1
  else if (tab === 'replied') searchForm.has_reply = 1
  else if (tab === 'unreplied') searchForm.has_reply = 0
  handleSearch()
}

const handleResetTab = () => {
  ratingTab.value = 'all'
  resetSearch()
}

// 当前页统计概览（KPI mini）
const stats = computed(() => {
  let positive = 0, negative = 0, replied = 0
  for (const row of list.value) {
    const r = row as Record<string, any>
    const rating = Number(r.rating || 0)
    if (rating >= 4) positive += 1
    else if (rating >= 1) negative += 1
    if (r.reply || r.has_reply || r.reply_content) replied += 1
  }
  return { positive, negative, replied }
})

// 星级分布（5/4/3/2/1 星）
const STAR_COLORS: Record<number, string> = {
  5: '#10b981',
  4: '#3b82f6',
  3: '#f59e0b',
  2: '#f97316',
  1: '#ef4444',
}

const ratingDistribution = computed(() => {
  const counts: Record<number, number> = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
  for (const row of list.value) {
    const r = Number((row as Record<string, any>).rating || 0)
    if (r >= 1 && r <= 5) counts[r] += 1
  }
  return counts
})

const ratingRows = computed(() => {
  const counts = ratingDistribution.value
  const total = list.value.length || 1
  return [5, 4, 3, 2, 1].map((star) => {
    const count = counts[star]
    const percent = total > 0 ? Math.round((count / total) * 1000) / 10 : 0
    return { star, count, percent, color: STAR_COLORS[star] }
  })
})

const averageRating = computed(() => {
  if (!list.value.length) return 0
  const sum = list.value.reduce((s, r) => s + Number((r as Record<string, any>).rating || 0), 0)
  return sum / list.value.length
})

// 评价标签（从 row.tags 字段聚合，兼容字符串数组或 JSON 字符串）
const ratingTags = computed(() => {
  const counter: Record<string, number> = {}
  for (const row of list.value) {
    const tags = (row as Record<string, any>).tags
    let arr: string[] = []
    if (Array.isArray(tags)) arr = tags
    else if (typeof tags === 'string') {
      try { const parsed = JSON.parse(tags); if (Array.isArray(parsed)) arr = parsed }
      catch { /* ignore */ }
    }
    for (const t of arr) {
      if (typeof t === 'string' && t.trim()) {
        counter[t] = (counter[t] || 0) + 1
      }
    }
  }
  return Object.entries(counter)
    .map(([label, count]) => ({ label, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 8)
})

const formatCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

// 评分映射 → tag
const ratingTagLabel = (rating: number) => {
  const r = Number(rating || 0)
  if (r >= 4) return '好评'
  if (r === 3) return '中评'
  return '差评'
}

const ratingTagType = (rating: number): 'success' | 'warning' | 'danger' | 'info' => {
  const r = Number(rating || 0)
  if (r >= 4) return 'success'
  if (r === 3) return 'warning'
  if (r >= 1) return 'danger'
  return 'info'
}

// 头像首字母与渐变（从用户名 hash 取色）
const AVATAR_GRADIENTS = [
  'linear-gradient(135deg,#a3bffa,#5b73e8)',
  'linear-gradient(135deg,#fbbf24,#f97316)',
  'linear-gradient(135deg,#86efac,#10b981)',
  'linear-gradient(135deg,#f9a8d4,#ec4899)',
  'linear-gradient(135deg,#a78bfa,#7c3aed)',
  'linear-gradient(135deg,#67e8f9,#0891b2)',
]

const hashCode = (s: string) => {
  let h = 0
  for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) | 0
  return Math.abs(h)
}

const avatarInitial = (row: Record<string, any>) => {
  const name = String(row.user_nickname || row.user_name || row.user_id || '匿').trim()
  return name.slice(0, 1).toUpperCase()
}

const avatarGradient = (row: Record<string, any>) => {
  const key = String(row.user_id || row.user_nickname || row.id || '0')
  return AVATAR_GRADIENTS[hashCode(key) % AVATAR_GRADIENTS.length]
}

// 回复对话框
const replyDialogVisible = ref(false)
const replyLoading = ref(false)
const replyFormRef = ref()
const currentReview = ref<Record<string, any> | null>(null)
const replyForm = reactive({
  reply_content: '',
})
const replyRules = {
  reply_content: [{ required: true, message: '请输入回复内容', trigger: 'blur' }],
}

// 回复评价
const handleReply = (row: Record<string, any>) => {
  currentReview.value = row
  replyForm.reply_content = row.reply_content || ''
  replyDialogVisible.value = true
}

const resetReplyForm = () => {
  replyForm.reply_content = ''
  currentReview.value = null
}

const confirmReply = async () => {
  if (!replyFormRef.value) return
  await replyFormRef.value.validate()
  try {
    replyLoading.value = true
    await orderReviewApi.replyReview(currentReview.value!.id, { reply_content: replyForm.reply_content })
    ElMessage.success('回复成功')
    replyDialogVisible.value = false
    getList()
  } catch (error) {
    console.error('回复失败:', error)
  } finally {
    replyLoading.value = false
  }
}
</script>

<style lang="scss" scoped>
/* 星级分布 */
.rating-card {
  margin-bottom: 14px;
}

.rating-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 24px;
  align-items: center;

  @media (max-width: 1024px) {
    grid-template-columns: 1fr;
  }
}

.rating-bars {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.rating-row {
  display: grid;
  grid-template-columns: 60px 1fr 60px 50px;
  gap: 10px;
  align-items: center;
  font-size: 12.5px;
}

.rating-row-l {
  display: flex;
  align-items: center;
  gap: 4px;
  color: var(--ink-600);

  .star {
    color: #f59e0b;
  }
}

.rating-bar {
  height: 8px;
  border-radius: 4px;
  background: var(--ink-50);
  overflow: hidden;
}

.rating-bar-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s;
}

.rating-cnt {
  text-align: right;
  color: var(--ink-700);
}

.rating-pct {
  text-align: right;
  color: var(--ink-400);
}

.rating-summary {
  text-align: center;
  padding: 8px 0;
}

.rating-score {
  font-size: 54px;
  font-weight: 700;
  color: var(--ink-900);
  line-height: 1;
}

.rating-summary .rating-stars {
  display: flex;
  justify-content: center;
  gap: 2px;
  margin-top: 6px;
  font-size: 16px;
}

.rating-star {
  color: var(--ink-200);
  font-style: normal;

  &.on {
    color: #f59e0b;
  }
}

.rating-summary-desc {
  margin-top: 6px;
  font-size: 12px;
  color: var(--ink-500);
}

.rating-tags {
  margin-top: 14px;
  display: flex;
  justify-content: center;
  gap: 6px;
  flex-wrap: wrap;
}

.review-list {
  padding: 0;
}

.review-item {
  padding: 18px 20px;
  border-top: 1px solid var(--ink-100);

  &.review-item-first {
    border-top: 0;
  }
}

.review-avatar {
  width: 40px;
  height: 40px;
  border-radius: 20px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  flex-shrink: 0;
  font-size: 14px;
}

.review-main {
  flex: 1;
  min-width: 0;
}

.review-user {
  font-weight: 500;
  color: var(--ink-900);
}

.review-stars {
  display: inline-flex;
  gap: 1px;

  .review-star {
    color: var(--ink-200);
    font-style: normal;
    font-size: 14px;

    &.review-star-on {
      color: #f59e0b;
    }
  }
}

.review-time {
  font-size: 11.5px;
  color: var(--ink-400);
  margin-top: 3px;
}

.review-text {
  font-size: 13px;
  color: var(--ink-700);
  margin-top: 8px;
  line-height: 1.7;
  word-break: break-word;
}

.review-image {
  width: 64px;
  height: 64px;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid var(--ink-100);
}

.review-goods-bar {
  margin-top: 12px;
  padding: 10px 14px;
  background: var(--ink-50);
  border-radius: 6px;
  font-size: 12px;
  color: var(--ink-500);
  display: flex;
  align-items: center;
  gap: 8px;
}

.review-goods-label {
  color: var(--ink-400);
}

.review-goods-name {
  color: var(--brand-500);
  font-weight: 500;
}

.review-goods-spec {
  color: var(--ink-400);
  margin-left: auto;
}

.review-reply {
  margin-top: 10px;
  padding: 10px 14px;
  background: #eff6ff;
  border-radius: 6px;
  border-left: 3px solid #3b82f6;
}

.review-reply-label {
  font-size: 11.5px;
  color: #2563eb;
  margin-bottom: 4px;
  font-weight: 500;
}

.review-reply-text {
  font-size: 12.5px;
  color: var(--ink-700);
  line-height: 1.7;
}

.review-empty {
  padding: 40px 0;
}

.review-pagination {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.order-review-container {
  .stats-row {
    margin-bottom: 14px;
  }

  .goods-info {
    display: flex;
    align-items: center;
    gap: 10px;

    .goods-thumb {
      width: 56px;
      height: 56px;
      border-radius: 4px;
      flex-shrink: 0;
      border: 1px solid var(--el-border-color-lighter);

      &--empty {
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--el-fill-color-light);
        color: var(--el-text-color-secondary);
      }
    }

    .goods-meta {
      min-width: 0;

      .goods-name {
        font-size: 14px;
        font-weight: 500;
        color: var(--el-text-color-primary);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .goods-spec {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        margin-top: 2px;
      }
    }
  }

  .rating-stars {
    display: flex;
    align-items: center;
    gap: 2px;

    .star {
      font-size: 16px;

      &--active {
        color: #faad14;
      }

      &--inactive {
        color: var(--el-border-color);
      }
    }

    .rating-text {
      margin-left: 4px;
      font-size: 13px;
      color: var(--el-text-color-secondary);
    }
  }

  .review-content {
    .review-text {
      margin: 0 0 6px;
      font-size: 13px;
      color: var(--el-text-color-primary);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      max-width: 300px;
    }

    .review-images {
      display: flex;
      gap: 4px;
      align-items: center;

      .review-image {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        border: 1px solid var(--el-border-color-lighter);
      }

      .images-more {
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }
    }
  }

  .review-preview {
    padding: 12px;
    background: var(--el-fill-color-light);
    border-radius: 4px;

    .review-rating {
      display: flex;
      gap: 2px;
      margin-bottom: 8px;

      .star {
        font-size: 16px;

        &--active {
          color: #faad14;
        }

        &--inactive {
          color: var(--el-border-color);
        }
      }
    }

    .review-original {
      margin: 0;
      font-size: 14px;
      color: var(--el-text-color-primary);
    }
  }
}
</style>
