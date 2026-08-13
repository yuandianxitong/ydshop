<template>
  <div>
    <div class="flex items-center gap-2">
      <el-tag v-if="modelValue" closable @close="emit('update:modelValue', null)">活动#{{ modelValue }}</el-tag>
      <el-button size="small" @click="visible = true">选择秒杀活动</el-button>
    </div>
    <el-dialog v-model="visible" title="选择秒杀活动" width="650px" append-to-body>
      <el-input v-model="keyword" placeholder="搜索活动名称" clearable class="mb-3" @input="onSearch">
        <template #prefix><i class="i-ri-search-line" /></template>
      </el-input>
      <el-table :data="list" v-loading="loading" height="360" highlight-current-row @current-change="onRowClick">
        <el-table-column prop="name" label="活动名称" show-overflow-tooltip />
        <el-table-column label="开始时间" width="150">
          <template #default="{ row }">{{ row.start_time }}</template>
        </el-table-column>
        <el-table-column label="结束时间" width="150">
          <template #default="{ row }">{{ row.end_time }}</template>
        </el-table-column>
        <el-table-column label="状态" width="70">
          <template #default="{ row }"><el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">{{ row.status === 1 ? '进行中' : '已结束' }}</el-tag></template>
        </el-table-column>
      </el-table>
      <el-pagination v-model:current-page="page" :total="total" :page-size="10" layout="total, prev, pager, next" class="mt-2" @current-change="loadList" />
      <template #footer>
        <el-button @click="visible = false">取消</el-button>
        <el-button type="primary" :disabled="!selectedId" @click="onConfirm">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { flashSaleApi } from '@/api/marketing'

const props = defineProps<{ modelValue: number | null }>()
const emit = defineEmits(['update:modelValue'])

const visible = ref(false)
const loading = ref(false)
const keyword = ref('')
const page = ref(1)
const total = ref(0)
const list = ref<any[]>([])
const selectedId = ref<number | null>(null)

watch(visible, async (v) => { if (v) { selectedId.value = props.modelValue; await loadList() } })

async function loadList() {
    loading.value = true
    try {
        const params: Record<string, any> = { page: page.value, limit: 10 }
        if (keyword.value) params.keyword = keyword.value
        const res = await flashSaleApi.getFlashSaleList(params)
        list.value = res.data?.list || []
        total.value = res.data?.pagination?.total || 0
    } finally { loading.value = false }
}

let searchTimer: ReturnType<typeof setTimeout>
function onSearch() { clearTimeout(searchTimer); searchTimer = setTimeout(() => { page.value = 1; loadList() }, 300) }
function onRowClick(row: any) { selectedId.value = row?.id || null }
function onConfirm() { emit('update:modelValue', selectedId.value); visible.value = false }
</script>
