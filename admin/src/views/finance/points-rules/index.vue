<script setup lang="ts" name="FinancePointsRules">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { financeApi } from '@/api/finance'
import type { PointsRulesOverview } from '@/types/api'

const router = useRouter()
const data = ref<PointsRulesOverview | null>(null)
const loading = ref(false)

const load = async () => {
    loading.value = true
    try {
        const res = await financeApi.getPointsRules()
        data.value = res.data
    } finally {
        loading.value = false
    }
}

const goConfig = (url: string) => {
    router.push(url)
}

onMounted(load)
</script>

<template>
    <div v-loading="loading" class="points-rules-container">
        <!-- 页头 -->
        <div class="page-head">
            <div>
                <h2 class="page-title">积分规则</h2>
                <p class="page-desc">展示当前所有积分发放规则的配置；如需修改请到对应配置页</p>
            </div>
        </div>

        <div v-if="data" class="row-12">
            <!-- 注册赠送 -->
            <el-card class="rule-card" shadow="never">
                <div class="card-head">
                    <div class="card-title">注册赠送</div>
                    <el-button text type="primary" @click="goConfig(data.register_gift.config_url)">
                        去配置 →
                    </el-button>
                </div>
                <div class="card-body">
                    <div class="rule-row">
                        <span class="rule-label">状态</span>
                        <el-tag :type="data.register_gift.enabled ? 'success' : 'info'" size="small">
                            {{ data.register_gift.enabled ? '已启用' : '已禁用' }}
                        </el-tag>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">赠送积分</span>
                        <span class="rule-value">{{ data.register_gift.points }}</span>
                    </div>
                </div>
            </el-card>

            <!-- 签到 -->
            <el-card class="rule-card" shadow="never">
                <div class="card-head">
                    <div class="card-title">签到</div>
                    <el-button text type="primary" @click="goConfig(data.sign_in.config_url)">
                        去配置 →
                    </el-button>
                </div>
                <div class="card-body">
                    <div class="rule-row">
                        <span class="rule-label">基础积分</span>
                        <span class="rule-value">{{ data.sign_in.base }}</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">每日递增</span>
                        <span class="rule-value">+{{ data.sign_in.increment }}</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">单日上限</span>
                        <span class="rule-value">{{ data.sign_in.max }}</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">连续奖励触发天数</span>
                        <span class="rule-value">{{ data.sign_in.continuous_bonus_days }} 天</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-label">连续奖励积分</span>
                        <span class="rule-value">+{{ data.sign_in.continuous_bonus_points }}</span>
                    </div>
                </div>
            </el-card>
        </div>

        <!-- 消费等级倍率 -->
        <el-card v-if="data" class="rule-card rule-card-full" shadow="never">
            <div class="card-head">
                <div class="card-title">消费等级倍率</div>
                <el-button text type="primary" @click="goConfig(data.member_levels.config_url)">
                    去等级管理 →
                </el-button>
            </div>
            <div class="card-body">
                <el-table :data="data.member_levels.levels" stripe>
                    <el-table-column prop="id" label="ID" width="80" />
                    <el-table-column prop="name" label="等级名称" />
                    <el-table-column label="积分倍率" width="180">
                        <template #default="{ row }">
                            <span class="rule-value">{{ row.points_rate }}x</span>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
        </el-card>
    </div>
</template>

<style lang="scss" scoped>
.points-rules-container {
    padding: 0;
}

.page-head {
    margin-bottom: 16px;
}

.page-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--ink-900);
    margin: 0 0 4px;
}

.page-desc {
    font-size: 13px;
    color: var(--ink-500);
    margin: 0;
}

.rule-card {
    :deep(.el-card__body) {
        padding: 0;
    }
}

.rule-card-full {
    margin-top: 16px;
}

.card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    border-bottom: 1px solid var(--ink-100);
}

.card-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--ink-900);
}

.card-body {
    padding: 14px 18px;
}

.rule-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--ink-50);

    &:last-child {
        border-bottom: 0;
    }
}

.rule-label {
    font-size: 13px;
    color: var(--ink-600);
}

.rule-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--ink-900);
}
</style>
