<template>
  <div class="cond-block">
    <div class="cond-logic-bar">
      满足
      <el-radio-group :model-value="value.logic" size="small" @update:model-value="updateLogic">
        <el-radio-button value="AND">全部</el-radio-button>
        <el-radio-button value="OR">任意</el-radio-button>
      </el-radio-group>
      包含条件，且<strong>不匹配</strong>任何排除条件：
    </div>

    <div v-for="(c, idx) in value.conditions" :key="idx" class="cond-row">
      <el-radio-group v-model="c.exclude" size="small" class="cond-mode" @change="emitChange">
        <el-radio-button :value="false">包含</el-radio-button>
        <el-radio-button :value="true">排除</el-radio-button>
      </el-radio-group>
      <el-select v-model="c.field" placeholder="字段" style="width: 160px" @change="emitChange">
        <el-option v-for="opt in fieldOptions" :key="opt.value" :label="opt.label" :value="opt.value" />
      </el-select>
      <el-select v-model="c.op" placeholder="运算符" style="width: 90px" @change="emitChange">
        <el-option v-for="op in operatorOptions" :key="op.value" :label="op.label" :value="op.value" />
      </el-select>
      <el-input v-model="c.value" placeholder="值（如 100 或 30_days_ago）" style="flex: 1" @change="emitChange" />
      <el-button type="danger" text circle @click="remove(idx)">
        <i class="i-svg:close" />
      </el-button>
    </div>

    <div class="cond-actions">
      <el-button size="small" @click="add">+ 添加条件</el-button>
    </div>

    <div class="cond-hint">
      时间相对值：<code>7_days_ago</code> / <code>14_days_ago</code> / <code>30_days_ago</code> / <code>90_days_ago</code> / <code>today</code> / <code>yesterday</code>
    </div>
  </div>
</template>

<script setup lang="ts" name="RuleConditionEditor">
import { reactive, watch } from 'vue'

export interface RuleCondition {
  field: string
  op: string
  value: string
  exclude: boolean
}

export interface Rules {
  logic: 'AND' | 'OR'
  conditions: RuleCondition[]
}

interface Props {
  modelValue: Rules | null
}
interface Emits {
  (e: 'update:modelValue', v: Rules): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const DEFAULT_FIELDS = [
  { label: '注册时间',   value: 'created_at' },
  { label: '累计消费',   value: 'total_consume' },
  { label: '订单数',     value: 'order_count' },
  { label: '积分',       value: 'points' },
  { label: '余额',       value: 'balance' },
  { label: '会员等级',   value: 'member_level_id' },
  { label: '最后登录',   value: 'last_login_time' },
  { label: '是否分销员', value: 'is_distributor' },
]
const fieldOptions = DEFAULT_FIELDS

const operatorOptions = [
  { label: '=',  value: '=' },
  { label: '!=', value: '!=' },
  { label: '>',  value: '>' },
  { label: '>=', value: '>=' },
  { label: '<',  value: '<' },
  { label: '<=', value: '<=' },
]

// 内部状态：从 modelValue 同步初始值；用户编辑直接改 conditions 数组、再 emit
const value = reactive<Rules>({
  logic: 'AND',
  conditions: [],
})

const syncFromProp = () => {
  const v = props.modelValue
  value.logic = v?.logic === 'OR' ? 'OR' : 'AND'
  value.conditions = Array.isArray(v?.conditions)
    ? v.conditions.map((c) => ({
        field: String(c.field || ''),
        op: String(c.op || '='),
        value: c.value == null ? '' : String(c.value),
        exclude: !!c.exclude,
      }))
    : []
}

watch(() => props.modelValue, syncFromProp, { immediate: true, deep: true })

const emitChange = () => {
  emit('update:modelValue', {
    logic: value.logic,
    conditions: value.conditions.map((c) => ({ ...c })),
  })
}

const updateLogic = (v: string | number | boolean | undefined) => {
  value.logic = v === 'OR' ? 'OR' : 'AND'
  emitChange()
}

const add = () => {
  value.conditions.push({ field: '', op: '=', value: '', exclude: false })
  emitChange()
}

const remove = (idx: number) => {
  value.conditions.splice(idx, 1)
  emitChange()
}
</script>

<style lang="scss" scoped>
.cond-block {
  width: 100%;
  padding: 14px;
  background: var(--ink-50, #f6f7fa);
  border-radius: 8px;
  border: 1px solid var(--ink-100, #eef0f5);
}
.cond-logic-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 12.5px;
  color: var(--ink-500, #8b95a7);
}
.cond-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 0;
  & + .cond-row { border-top: 1px dashed var(--ink-200, #dde0e6); }
}
.cond-mode { flex-shrink: 0; }
.cond-actions {
  margin-top: 12px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.cond-hint {
  margin-top: 10px;
  font-size: 12px;
  color: var(--ink-500, #8b95a7);
  code {
    background: #fff;
    padding: 1px 6px;
    border-radius: 3px;
    border: 1px solid var(--ink-100, #eef0f5);
    font-size: 11.5px;
  }
}
</style>
