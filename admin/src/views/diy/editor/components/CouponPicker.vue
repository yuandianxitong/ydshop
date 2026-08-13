<template>
  <div>
    <div class="flex items-center gap-2 flex-wrap">
      <el-tag v-for="id in modelValue" :key="id" closable @close="removeId(id)">优惠券#{{ id }}</el-tag>
      <el-button size="small" @click="visible = true">选择优惠券</el-button>
    </div>
    <el-dialog v-model="visible" title="选择优惠券" width="700px" append-to-body>
      <el-input v-model="keyword" placeholder="搜索优惠券名称" clearable class="mb-3" @input="onSearch">
        <template #prefix><i class="i-ri-search-line" /></template>
      </el-input>
      <el-table :data="list" v-loading="loading" height="360" @selection-change="onSelectionChange">
        <el-table-column type="selection" width="40" />
        <el-table-column prop="name" label="名称" show-overflow-tooltip />
        <el-table-column label="面额" width="80">
          <template #default="{ row }">¥{{ row.discount_value || row.amount }}</template>
        </el-table-column>
        <el-table-column label="使用门槛" width="100">
          <template #default="{ row }">{{ row.min_amount ? `满${row.min_amount}` : '无门槛' }}</template>
        </el-table-column>
        <el-table-column label="状态" width="70">
          <template #default="{ row }"><el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag></template>
        </el-table-column>
      </el-table>
      <el-pagination v-model:current-page="page" :total="total" :page-size="10" layout="total, prev, pager, next" class="mt-2" @current-change="loadList" />
      <template #footer>
        <span class="text-sm text-gray-400 mr-4">已选 {{ selected.length }} 张</span>
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" @click="onConfirm">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { couponApi } from '@/api/marketing'

const props = defineProps<{ modelValue: number[] }>()
const emit = defineEmits(['update:modelValue'])

const visible = ref(false)
const loading = ref(false)
const keyword = ref('')
const page = ref(1)
const total = ref(0)
const list = ref<any[]>([])
const selected = ref<number[]>([])

watch(visible, async (v) => { if (v) { selected.value = [...(props.modelValue || [])]; await loadList() } })

async function loadList() {
    loading.value = true
    try {
        const params: Record<string, any> = { page: page.value, limit: 10 }
        if (keyword.value) params.keyword = keyword.value
        const res = await couponApi.getCouponList(params)
        list.value = res.data?.list || []
        total.value = res.data?.pagination?.total || 0
    } finally { loading.value = false }
}

let searchTimer: ReturnType<typeof setTimeout>
function onSearch() { clearTimeout(searchTimer); searchTimer = setTimeout(() => { page.value = 1; loadList() }, 300) }
function onSelectionChange(rows: any[]) { selected.value = rows.map(r => r.id) }
function removeId(id: number) { emit('update:modelValue', props.modelValue.filter(v => v !== id)) }
function onConfirm() { emit('update:modelValue', [...selected.value]); visible.value = false }
</script>
