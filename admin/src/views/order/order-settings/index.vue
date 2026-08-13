<template>
  <div class="order-settings-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">交易设置</h2>
        <p class="page-desc">订单、支付、物流、售后、评价、发票相关全局规则</p>
      </div>
      <div class="page-actions">
        <el-button type="primary" :loading="saving" @click="handleSave">
          <i class="i-svg:check" /> 保存所有配置
        </el-button>
      </div>
    </div>

    <div class="settings-layout" v-loading="loading">
      <!-- 左侧分组 -->
      <div class="card settings-nav">
        <div class="settings-nav-head">设置分组</div>
        <div class="settings-nav-body">
          <button
            v-for="g in groups"
            :key="g.k"
            class="settings-nav-item"
            :class="{ on: activeGroup === g.k }"
            @click="activeGroup = g.k"
          >
            <span class="dot" />
            {{ g.l }}
          </button>
        </div>
      </div>

      <!-- 右侧内容 -->
      <div class="card settings-content">
        <div class="card-head">
          <div class="card-title">{{ currentGroupTitle }}</div>
          <div class="card-meta">最后更新由系统自动同步</div>
        </div>
        <div class="card-body settings-rows">
          <!-- 订单设置 -->
          <template v-if="activeGroup === 'order'">
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">未付款自动关闭</div>
                <div class="setting-desc">超过设定时间用户未支付，自动取消订单并释放库存</div>
              </div>
              <div class="setting-row-r">
                <el-input-number v-model="form['order.auto_cancel_minutes']" :min="0" :max="10080" :step="5" controls-position="right" />
                <span class="unit">分钟</span>
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">已发货自动确认</div>
                <div class="setting-desc">发货后超过设定天数，系统自动确认收货</div>
              </div>
              <div class="setting-row-r">
                <el-input-number v-model="form['order.auto_confirm_days']" :min="0" :max="365" controls-position="right" />
                <span class="unit">天</span>
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">售后申请期限</div>
                <div class="setting-desc">确认收货后超过该天数不允许申请售后</div>
              </div>
              <div class="setting-row-r">
                <el-input-number v-model="form['order.refund_deadline_days']" :min="0" :max="365" controls-position="right" />
                <span class="unit">天</span>
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">自动好评天数</div>
                <div class="setting-desc">确认收货后超过此天数未评价，系统自动默认好评</div>
              </div>
              <div class="setting-row-r">
                <el-input-number v-model="form['order.auto_review_days']" :min="0" :max="365" controls-position="right" />
                <span class="unit">天</span>
              </div>
            </div>
          </template>

          <!-- 支付设置 -->
          <template v-else-if="activeGroup === 'pay'">
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">支持的支付方式</div>
                <div class="setting-desc">勾选启用的支付通道</div>
              </div>
              <div class="setting-row-r">
                <el-checkbox-group v-model="form['pay.methods']">
                  <el-checkbox label="wechat" value="wechat">微信支付</el-checkbox>
                  <el-checkbox label="alipay" value="alipay">支付宝</el-checkbox>
                  <el-checkbox label="balance" value="balance">余额支付</el-checkbox>
                </el-checkbox-group>
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">支付超时</div>
                <div class="setting-desc">跳转支付页超过该时长视为支付失败</div>
              </div>
              <div class="setting-row-r">
                <el-input-number v-model="form['pay.timeout_minutes']" :min="0" :max="120" controls-position="right" />
                <span class="unit">分钟</span>
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">允许积分抵现</div>
                <div class="setting-desc">100 积分 = ¥ 1，单笔订单最多抵扣金额的 50%</div>
              </div>
              <div class="setting-row-r">
                <el-switch v-model="form['pay.points_enabled']" :active-value="1" :inactive-value="0" />
              </div>
            </div>
          </template>

          <!-- 发货 / 物流 -->
          <template v-else-if="activeGroup === 'ship'">
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">发货时效要求</div>
                <div class="setting-desc">付款后超过该时长未发货将触发提醒</div>
              </div>
              <div class="setting-row-r">
                <el-input-number v-model="form['ship.deadline_hours']" :min="0" :max="168" controls-position="right" />
                <span class="unit">小时</span>
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">开启电子面单</div>
                <div class="setting-desc">对接物流平台自动打印电子面单</div>
              </div>
              <div class="setting-row-r">
                <el-switch v-model="form['ship.electronic_waybill']" :active-value="1" :inactive-value="0" />
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">支持自提</div>
                <div class="setting-desc">允许用户到店自提，免运费</div>
              </div>
              <div class="setting-row-r">
                <el-switch v-model="form['ship.self_pickup']" :active-value="1" :inactive-value="0" />
              </div>
            </div>
          </template>

          <!-- 售后规则 -->
          <template v-else-if="activeGroup === 'after'">
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">商家审核时限</div>
                <div class="setting-desc">超过该时长未处理，自动同意用户申请</div>
              </div>
              <div class="setting-row-r">
                <el-input-number v-model="form['after.audit_deadline_hours']" :min="0" :max="168" controls-position="right" />
                <span class="unit">小时</span>
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">允许仅退款</div>
                <div class="setting-desc">用户未收到货时可申请仅退款</div>
              </div>
              <div class="setting-row-r">
                <el-switch v-model="form['after.allow_refund_only']" :active-value="1" :inactive-value="0" />
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">允许换货</div>
                <div class="setting-desc">用户可申请换货而非退款</div>
              </div>
              <div class="setting-row-r">
                <el-switch v-model="form['after.allow_exchange']" :active-value="1" :inactive-value="0" />
              </div>
            </div>
          </template>

          <!-- 评价规则 -->
          <template v-else-if="activeGroup === 'review'">
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">允许匿名评价</div>
                <div class="setting-desc">用户提交评价时可隐藏昵称</div>
              </div>
              <div class="setting-row-r">
                <el-switch v-model="form['review.allow_anonymous']" :active-value="1" :inactive-value="0" />
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">评价奖励积分</div>
                <div class="setting-desc">用户提交评价后赠送积分</div>
              </div>
              <div class="setting-row-r">
                <el-input-number v-model="form['review.reward_points']" :min="0" controls-position="right" />
                <span class="unit">积分</span>
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">差评先审后发</div>
                <div class="setting-desc">差评需运营审核后才向用户展示</div>
              </div>
              <div class="setting-row-r">
                <el-switch v-model="form['review.bad_review_audit']" :active-value="1" :inactive-value="0" />
              </div>
            </div>
          </template>

          <!-- 发票规则 -->
          <template v-else-if="activeGroup === 'invo'">
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">启用发票申请</div>
                <div class="setting-desc">用户可在前台申请发票</div>
              </div>
              <div class="setting-row-r">
                <el-switch v-model="form['invoice.enabled']" :active-value="1" :inactive-value="0" />
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">支持的发票形式</div>
                <div class="setting-desc">勾选启用的发票形式</div>
              </div>
              <div class="setting-row-r">
                <el-checkbox-group v-model="form['invoice.types']">
                  <el-checkbox label="electronic" value="electronic">电子发票</el-checkbox>
                  <el-checkbox label="paper" value="paper">纸质发票</el-checkbox>
                  <el-checkbox label="vat" value="vat">增值税专票</el-checkbox>
                </el-checkbox-group>
              </div>
            </div>
            <div class="setting-row">
              <div class="setting-row-l">
                <div class="setting-label">开票时限</div>
                <div class="setting-desc">订单完成后该时限内可申请开票</div>
              </div>
              <div class="setting-row-r">
                <el-input-number v-model="form['invoice.deadline_days']" :min="0" controls-position="right" />
                <span class="unit">天</span>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts" name="OrderSettings">
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref } from 'vue'

import { orderApi } from '@/api/order'

const loading = ref(false)
const saving = ref(false)

const groups = [
  { k: 'order',  l: '订单设置' },
  { k: 'pay',    l: '支付设置' },
  { k: 'ship',   l: '发货 / 物流' },
  { k: 'after',  l: '售后规则' },
  { k: 'review', l: '评价规则' },
  { k: 'invo',   l: '发票规则' },
]

const activeGroup = ref('order')
const currentGroupTitle = computed(() => groups.find((g) => g.k === activeGroup.value)?.l || '')

const form = ref<Record<string, any>>({
  // order
  'order.auto_cancel_minutes': 30,
  'order.auto_confirm_days': 7,
  'order.refund_deadline_days': 15,
  'order.auto_review_days': 15,
  // pay
  'pay.methods': ['wechat', 'alipay', 'balance'],
  'pay.timeout_minutes': 15,
  'pay.points_enabled': 1,
  // ship
  'ship.deadline_hours': 48,
  'ship.electronic_waybill': 1,
  'ship.self_pickup': 1,
  // after
  'after.audit_deadline_hours': 48,
  'after.allow_refund_only': 1,
  'after.allow_exchange': 1,
  // review
  'review.allow_anonymous': 1,
  'review.reward_points': 5,
  'review.bad_review_audit': 0,
  // invoice
  'invoice.enabled': 1,
  'invoice.types': ['electronic'],
  'invoice.deadline_days': 30,
})

const loadSettings = async () => {
  loading.value = true
  try {
    const res = await orderApi.getSettings()
    if ((res as any).code === 200 && (res as any).data) {
      Object.assign(form.value, (res as any).data)
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const handleSave = async () => {
  saving.value = true
  try {
    const res = await orderApi.updateSettings(form.value)
    if ((res as any).code === 200) {
      ElMessage.success('已保存所有配置')
    } else {
      ElMessage.error((res as any).message || '保存失败')
    }
  } catch (e) {
    console.error(e)
  } finally {
    saving.value = false
  }
}

onMounted(loadSettings)
</script>

<style lang="scss" scoped>
.order-settings-container {
  .settings-layout {
    display: grid;
    grid-template-columns: 200px minmax(0, 1fr);
    gap: 14px;
  }
}

.settings-nav {
  height: fit-content;
  position: sticky;
  top: 14px;
}

.settings-nav-head {
  padding: 12px 14px;
  border-bottom: 1px solid var(--ink-100);
  font-size: 12px;
  color: var(--ink-500);
  font-weight: 500;
}

.settings-nav-body {
  padding: 6px;
}

.settings-nav-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 6px;
  background: transparent;
  color: var(--ink-700);
  border: none;
  cursor: pointer;
  font-size: 13px;
  font-weight: 400;
  text-align: left;
  margin-bottom: 2px;

  &:hover {
    background: var(--ink-50);
  }

  &.on {
    background: var(--brand-50);
    color: var(--brand-600);
    font-weight: 600;

    .dot {
      background: var(--brand-500);
    }
  }

  .dot {
    width: 6px;
    height: 6px;
    border-radius: 3px;
    background: var(--ink-300);
    flex-shrink: 0;
  }
}

.settings-rows {
  padding: 0 20px 20px;
}

.setting-row {
  display: grid;
  grid-template-columns: minmax(160px, 200px) minmax(0, 1fr);
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px dashed var(--ink-100);
  align-items: flex-start;

  &:last-child {
    border-bottom: 0;
  }
}

.setting-label {
  font-size: 13px;
  color: var(--ink-900);
  font-weight: 500;
}

.setting-desc {
  font-size: 11.5px;
  color: var(--ink-400);
  margin-top: 3px;
  line-height: 1.6;
}

.setting-row-r {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.unit {
  font-size: 12.5px;
  color: var(--ink-500);
}
</style>
