<script setup lang="ts" name="DeliveryShiftList">
import { ElMessage } from 'element-plus'
import { onMounted, ref } from 'vue'
import { deliveryShiftApi, deliveryStaffApi } from '@/api/delivery'
import { useListPage } from '@/hooks/useListPage'
import type { DeliveryShift, DeliveryShiftQuery, DeliveryStaffOption } from '@/types/api'

const weekdayMap: Record<number, string> = {
    1: '周一', 2: '周二', 3: '周三', 4: '周四', 5: '周五', 6: '周六', 7: '周日',
}
const weekdayOptions = Object.entries(weekdayMap).map(([k, v]) => ({ value: Number(k), label: v }))

interface Query extends DeliveryShiftQuery {
    staff_id?: number
    weekday?: number
}

const {
    list: tableData,
    loading,
    pagination,
    searchForm,
    getList: fetchList,
    handleSearch,
    handlePageChange,
    handleSizeChange,
    handleDelete,
} = useListPage<DeliveryShift, Query>({
    fetchFn: (params) => deliveryShiftApi.getList(params),
    deleteFn: (id) => deliveryShiftApi.delete(id),
    defaultSearchForm: { staff_id: undefined, weekday: undefined },
})

const staffOptions = ref<DeliveryStaffOption[]>([])
const loadStaffOptions = async () => {
    const res = await deliveryStaffApi.getOptions()
    staffOptions.value = (res.data ?? []) as DeliveryStaffOption[]
}

const dialogVisible = ref(false)
const dialogTitle   = ref('新建班次')
const editing       = ref<DeliveryShift | null>(null)
const form = ref({
    staff_id: undefined as number | undefined,
    weekday: 1,
    start_time: '09:00:00',
    end_time:   '18:00:00',
    remark: '',
})
const submitting = ref(false)

const resetForm = () => {
    form.value = {
        staff_id: undefined,
        weekday: 1,
        start_time: '09:00:00',
        end_time:   '18:00:00',
        remark: '',
    }
}

const openCreate = () => {
    editing.value = null
    dialogTitle.value = '新建班次'
    resetForm()
    dialogVisible.value = true
}

const openEdit = (row: any) => {
    editing.value = row
    dialogTitle.value = '编辑班次'
    form.value = {
        staff_id: row.staff_id,
        weekday: row.weekday,
        start_time: row.start_time,
        end_time: row.end_time,
        remark: row.remark || '',
    }
    dialogVisible.value = true
}

const submit = async () => {
    if (!form.value.staff_id) {
        ElMessage.warning('请选择配送员')
        return
    }
    if (form.value.start_time >= form.value.end_time) {
        ElMessage.warning('上班时间必须早于下班时间')
        return
    }
    submitting.value = true
    try {
        if (editing.value) {
            await deliveryShiftApi.update(editing.value.id, form.value)
            ElMessage.success('更新成功')
        } else {
            await deliveryShiftApi.create(form.value)
            ElMessage.success('创建成功')
        }
        dialogVisible.value = false
        fetchList()
    } finally {
        submitting.value = false
    }
}

const fmtCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

onMounted(() => {
    loadStaffOptions()
})
</script>

<template>
    <div class="delivery-shift">
        <div class="page-head">
            <div>
                <h2 class="page-title">排班管理</h2>
                <p class="page-desc">配送员周班次（周一至周日）— 一键派单时按当前时段筛选在班 staff</p>
            </div>
            <div class="page-actions">
                <el-button type="primary" @click="openCreate">+ 新建班次</el-button>
            </div>
        </div>

        <div class="filter-bar">
            <el-select
                v-model="searchForm.staff_id"
                clearable
                placeholder="配送员"
                style="width: 180px"
                @change="handleSearch"
            >
                <el-option v-for="o in staffOptions" :key="o.id" :label="o.name" :value="o.id" />
            </el-select>
            <el-select
                v-model="searchForm.weekday"
                clearable
                placeholder="周几"
                style="width: 120px"
                @change="handleSearch"
            >
                <el-option v-for="o in weekdayOptions" :key="o.value" :label="o.label" :value="o.value" />
            </el-select>
            <el-button @click="handleSearch">搜索</el-button>
        </div>

        <el-card class="table-card" shadow="never">
            <div class="table-header">
                <span class="table-title">班次列表 <span class="table-count">共 {{ fmtCount(pagination.total) }} 条</span></span>
            </div>

            <el-table v-loading="loading" :data="tableData">
                <el-table-column label="配送员" min-width="140">
                    <template #default="{ row }"><span class="num">{{ row.staff_name || '-' }}</span></template>
                </el-table-column>
                <el-table-column label="周几" width="100">
                    <template #default="{ row }">
                        <el-tag class="tag-tone-blue" size="small" effect="light">{{ weekdayMap[row.weekday as number] || row.weekday }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="时段" min-width="200">
                    <template #default="{ row }"><span class="num">{{ row.start_time }} - {{ row.end_time }}</span></template>
                </el-table-column>
                <el-table-column label="备注" min-width="200">
                    <template #default="{ row }">{{ row.remark || '-' }}</template>
                </el-table-column>
                <el-table-column label="创建时间" width="200">
                    <template #default="{ row }"><span class="num text-secondary">{{ row.created_at }}</span></template>
                </el-table-column>
                <el-table-column label="操作" width="160" fixed="right">
                    <template #default="{ row }">
                        <el-button text type="primary" size="small" @click="openEdit(row)">编辑</el-button>
                        <el-button text type="danger" size="small" @click="handleDelete(row.id)">删除</el-button>
                    </template>
                </el-table-column>
            </el-table>

            <el-pagination
                v-model:current-page="pagination.page"
                v-model:page-size="pagination.limit"
                :total="pagination.total"
                :page-sizes="[10, 20, 50, 100]"
                layout="total, sizes, prev, pager, next, jumper"
                class="pagination"
                @size-change="handleSizeChange"
                @current-change="handlePageChange"
            />
        </el-card>

        <el-dialog v-model="dialogVisible" :title="dialogTitle" width="500px">
            <el-form label-width="100px">
                <el-form-item label="配送员" required>
                    <el-select v-model="form.staff_id" style="width: 100%" placeholder="选择配送员">
                        <el-option v-for="o in staffOptions" :key="o.id" :label="o.name" :value="o.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="周几" required>
                    <el-select v-model="form.weekday" style="width: 100%">
                        <el-option v-for="o in weekdayOptions" :key="o.value" :label="o.label" :value="o.value" />
                    </el-select>
                </el-form-item>
                <el-form-item label="上班时间" required>
                    <el-time-picker v-model="form.start_time" format="HH:mm:ss" value-format="HH:mm:ss" placeholder="选择时间" style="width: 100%" />
                </el-form-item>
                <el-form-item label="下班时间" required>
                    <el-time-picker v-model="form.end_time" format="HH:mm:ss" value-format="HH:mm:ss" placeholder="选择时间" style="width: 100%" />
                </el-form-item>
                <el-form-item label="备注">
                    <el-input v-model="form.remark" maxlength="100" />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="dialogVisible = false">取消</el-button>
                <el-button type="primary" :loading="submitting" @click="submit">提交</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<style scoped lang="scss">
.delivery-shift {
    padding: 0;
}
</style>
