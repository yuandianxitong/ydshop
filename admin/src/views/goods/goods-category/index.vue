<template>
  <div class="app-container">
    <!-- 页头 -->
    <div class="page-head">
      <div>
        <h2 class="page-title">商品分类</h2>
        <p class="page-desc">多级分类与前台导航树管理</p>
      </div>
      <div class="page-actions">
        <el-button v-has-perm="['goods.goods-category.create']" type="primary" @click="handleAdd(0)">
          <i class="i-lucide:plus mr-1" /> 新建一级分类
        </el-button>
      </div>
    </div>

    <!-- 表格区域 -->
    <el-card class="table-card" shadow="never">
      <div class="table-header">
        <div class="table-title">
          商品分类
          <span class="table-count">共 {{ list.length }} 条</span>
        </div>
        <div class="table-actions">
          <el-button @click="isExpandAll = !isExpandAll; tableKey++">
            {{ isExpandAll ? '全部收起' : '全部展开' }}
          </el-button>
        </div>
      </div>

      <el-table
        :key="tableKey"
        v-loading="loading"
        :data="list"
        row-key="id"
        :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
        :default-expand-all="isExpandAll"
      >
        <el-table-column label="分类名称" prop="name" min-width="240" />

        <el-table-column label="图片" prop="icon" width="100">
          <template #default="{ row }">
            <el-image
              v-if="row.icon"
              :src="appStore.getImageUrl(row.icon)"
              class="cat-thumb"
              fit="cover"
              :preview-src-list="[appStore.getImageUrl(row.icon)]"
              preview-teleported
            />
            <span v-else class="text-secondary">-</span>
          </template>
        </el-table-column>

        <el-table-column label="排序" prop="sort" width="90" align="right" />

        <el-table-column label="商品数" width="100" align="right">
          <template #default="{ row }">
            <span class="num">{{ row.goods_count ?? 0 }}</span>
          </template>
        </el-table-column>

        <el-table-column label="前台展示" width="120">
          <template #default="{ row }">
            <el-tag :type="row.is_show === 1 ? 'success' : 'info'" size="small">
              {{ row.is_show === 1 ? '展示' : '隐藏' }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>

        <el-table-column label="创建时间" prop="created_at" width="200" />

        <el-table-column label="操作" width="240" fixed="right">
          <template #default="{ row }">
            <el-button
              v-has-perm="['goods.goods-category.create']"
              type="primary"
              size="small"
              text
              @click="handleAdd(row.id)"
            >
              添加子类
            </el-button>
            <el-button
              v-has-perm="['goods.goods-category.update']"
              type="primary"
              size="small"
              text
              @click="handleEdit(row)"
            >
              编辑
            </el-button>
            <el-button
              v-has-perm="['goods.goods-category.delete']"
              type="danger"
              size="small"
              text
              @click="handleDelete(row.id, row.name)"
            >
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <GoodsCategoryForm
      v-model="formVisible"
      :form-data="formData"
      :parent-options="parentOptions"
      @success="getList"
    />
  </div>
</template>

<script setup lang="ts" name="GoodsCategoryList">
import { computed, ref } from 'vue'
import { goodsCategoryApi } from '@/api/goods-category'
import { useListPage } from '@/hooks/useListPage'
import useAppStore from '@/store/modules/app.store'
import GoodsCategoryForm from './components/GoodsCategoryForm.vue'

const appStore = useAppStore()

const isExpandAll = ref(false)
const tableKey = ref(0)

// 树形 API 不分页，包装 fetchFn 适配 useListPage 期望的 { list, pagination } 形态
const {
  list,
  loading,
  getList,
  handleDelete,
  handleStatusChange,
} = useListPage<any, Record<string, never>>({
  fetchFn: async () => {
    const res = await goodsCategoryApi.getTree()
    return {
      ...res,
      data: {
        list: (res.data || []) as any[],
        pagination: { total: 0, page: 1, limit: 0, total_pages: 0 },
      },
    } as any
  },
  deleteFn: (id) => goodsCategoryApi.delete(id),
  updateStatusFn: (id, status) => goodsCategoryApi.updateStatus(id, { status }),
  defaultSearchForm: {},
})

// 父级选项（树本身就是父级候选）
const parentOptions = computed(() => [{ id: 0, name: '顶级分类', children: list.value }])

// 弹窗
const formVisible = ref(false)
const formData = ref<Record<string, any>>({})

const handleAdd = (parentId: number) => {
  formData.value = { parent_id: parentId, status: 1, sort: 0 }
  formVisible.value = true
}

const handleEdit = (row: any) => {
  formData.value = { ...row }
  formVisible.value = true
}
</script>

<style lang="scss" scoped>
.cat-thumb {
  width: 36px;
  height: 36px;
  border-radius: 4px;
  display: block;
}
</style>
