<script setup lang="ts" name="WaybillConfig">
import { ElMessage } from 'element-plus'
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { batchUpdateConfigs, getConfigsByGroup } from '@/api/system/config'
import { waybillTemplateApi } from '@/api/waybill-template'
import RegionCascader from '@/components/Region/index.vue'
import { useListPage } from '@/hooks/useListPage'
import type { WaybillCatalog, WaybillTemplateInfo, WaybillTemplateQuery } from '@/types/waybill'
import { ensureLodop, getLodopStatus } from '@/utils/lodop'

import WaybillTemplateForm from './components/WaybillTemplateForm.vue'

const loading = ref(false)
const router = useRouter()
const activeTab = ref<'templates' | 'settings'>('templates')
const catalog = ref<WaybillCatalog>({})
const formVisible = ref(false)
const formData = ref<Partial<WaybillTemplateInfo>>({})
const lodopHint = ref('尚未检测 Lodop')

const tabs = [
  { key: 'templates' as const, label: '面单模版' },
  { key: 'settings' as const, label: '设置' },
]

const settings = reactive<Record<string, string>>({
  waybill_enabled: '0',
  waybill_provider: '',
  waybill_app_key: '',
  waybill_app_secret: '',
  waybill_sender_name: '',
  waybill_sender_phone: '',
  waybill_sender_province: '',
  waybill_sender_city: '',
  waybill_sender_district: '',
  waybill_sender_address: '',
  waybill_lodop_enabled: '1',
  waybill_lodop_http_port: '8000',
  waybill_lodop_https_port: '8443',
})

const senderRegionNames = ref<string[]>([])

const onSenderRegionChange = (payload: { ids: number[]; names: string[]; codes: string[] }) => {
  settings.waybill_sender_province = payload.names[0] || ''
  settings.waybill_sender_city = payload.names[1] || ''
  settings.waybill_sender_district = payload.names[2] || ''
}

const providerLabel = computed(() => {
  return ({ kdniao: '快递鸟' } as Record<string, string>)[settings.waybill_provider] || '未配置'
})

const payTypeLabel = (v: number) =>
  (({ 1: '现付', 2: '到付', 3: '月结' }) as Record<number, string>)[v] || '—'

const fmtCount = (n: number) => (n ?? 0).toLocaleString('zh-CN')

const {
  list: tableData,
  loading: tableLoading,
  pagination,
  searchForm,
  getList: fetchList,
  handleSearch,
  resetSearch: handleReset,
  handlePageChange,
  handleSizeChange,
  handleDelete,
  handleStatusChange,
} = useListPage<WaybillTemplateInfo, WaybillTemplateQuery>({
  fetchFn: (params) => waybillTemplateApi.getList(params),
  deleteFn: (id) => waybillTemplateApi.delete(id),
  updateStatusFn: (id, status) => waybillTemplateApi.updateStatus(id, { status }),
  defaultSearchForm: { keyword: '', status: undefined },
  pageSize: 15,
  immediate: false,
})

const switchTab = (key: 'templates' | 'settings') => {
  activeTab.value = key
}

async function detectLodop() {
  lodopHint.value = '正在连接 Lodop…'
  const ok = await ensureLodop({
    httpPort: settings.waybill_lodop_http_port,
    httpsPort: settings.waybill_lodop_https_port,
    enabled: settings.waybill_lodop_enabled !== '0',
  })
  const st = getLodopStatus()
  lodopHint.value = ok ? st.message : st.message
}

onMounted(async () => {
  try {
    loading.value = true
    const [cfgRes, catalogRes] = await Promise.all([
      getConfigsByGroup('waybill'),
      waybillTemplateApi.getCatalog(),
    ])
    ;(cfgRes.data || []).forEach((c: any) => {
      if (c.config_key in settings) {
        settings[c.config_key] = c.config_value || ''
      }
    })
    senderRegionNames.value = [
      settings.waybill_sender_province,
      settings.waybill_sender_city,
      settings.waybill_sender_district,
    ].filter(Boolean)
    catalog.value = catalogRes.data || {}
    await fetchList()
    await detectLodop()
  } finally {
    loading.value = false
  }
})

const handleAdd = () => {
  formData.value = {
    status: 1,
    pay_type: 1,
    need_pickup: 0,
    is_default: 0,
    sort: 0,
    template_size: '',
  }
  formVisible.value = true
}

// el-table 插槽 row 类型为 DefaultRow，与业务类型不兼容，此处放宽入参
const handleEdit = (row: any) => {
  formData.value = { ...(row as WaybillTemplateInfo) }
  formVisible.value = true
}

const handleSaveSettings = async () => {
  try {
    loading.value = true
    const configs = Object.entries(settings).map(([key, value]) => ({
      config_key: key,
      config_value: value,
    }))
    await batchUpdateConfigs(configs)
    ElMessage.success('保存成功')
    await detectLodop()
  } catch {
    ElMessage.error('保存配置失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="waybill-config-container" v-loading="loading">
    <div class="page-head">
      <div>
        <h2 class="page-title">电子面单</h2>
        <p class="page-desc">配置面单模版、快递鸟凭证与 C-Lodop 打印机</p>
      </div>
      <div class="page-actions">
        <el-button @click="router.push('/order/order-ship')">发货管理</el-button>
        <el-button
          v-if="activeTab === 'settings'"
          type="primary"
          :loading="loading"
          @click="handleSaveSettings"
        >
          保存设置
        </el-button>
        <el-button v-else type="primary" @click="handleAdd">
          <i class="i-lucide:plus mr-1" /> 新增模版
        </el-button>
      </div>
    </div>

    <div class="row-14">
      <div class="kpi-mini">
        <div class="lb">服务商</div>
        <div class="nm num">{{ providerLabel }}</div>
        <div class="tr">
          <span :style="{ color: settings.waybill_enabled === '1' ? 'var(--success)' : 'var(--ink-500)' }">
            {{ settings.waybill_enabled === '1' ? '已启用' : '未启用' }}
          </span>
        </div>
      </div>
      <div class="kpi-mini">
        <div class="lb">Lodop</div>
        <div class="nm num" style="font-size: 14px; line-height: 1.4">{{ lodopHint }}</div>
        <div class="tr">
          <el-button link type="primary" @click="detectLodop">重新检测</el-button>
        </div>
      </div>
      <div class="kpi-mini">
        <div class="lb">模版数</div>
        <div class="nm num">{{ fmtCount(pagination.total) }}</div>
        <div class="tr"><span style="color: var(--ink-500)">启用中可发货选用</span></div>
      </div>
      <div class="kpi-mini">
        <div class="lb">本页启用</div>
        <div class="nm num">{{ fmtCount(tableData.filter((r) => r.status === 1).length) }}</div>
        <div class="tr"><span style="color: var(--success)">本页统计</span></div>
      </div>
    </div>

    <div class="filter-bar order-filter">
      <div class="seg order-seg">
        <button
          v-for="t in tabs"
          :key="t.key"
          :class="{ on: activeTab === t.key }"
          @click="switchTab(t.key)"
        >
          {{ t.label }}
        </button>
      </div>
      <template v-if="activeTab === 'templates'">
        <el-input
          v-model="searchForm.keyword"
          placeholder="搜索模版名称 / 物流公司"
          clearable
          style="width: 240px"
          @keyup.enter="handleSearch"
        />
        <span class="filter-label">状态：</span>
        <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
        <span class="filter-sp" />
        <el-button @click="handleReset">重置</el-button>
        <el-button type="primary" @click="handleSearch">查询</el-button>
      </template>
    </div>

    <!-- 面单模版 -->
    <el-card v-show="activeTab === 'templates'" class="table-card" shadow="never">
      <div class="table-header">
        <span class="table-title">
          面单模版
          <span class="table-count">共 {{ fmtCount(pagination.total) }} 条</span>
        </span>
      </div>

      <el-table v-loading="tableLoading" :data="tableData">
        <el-table-column prop="name" label="模版名称" min-width="140">
          <template #default="{ row }">
            <span>{{ row.name }}</span>
            <el-tag v-if="row.is_default === 1" class="ml-2" size="small" type="warning" effect="light">
              默认
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="物流公司" min-width="140">
          <template #default="{ row }">
            {{ row.express_name }}
            <span class="code">{{ row.express_code }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="exp_type_name" label="业务类型" min-width="110" />
        <el-table-column prop="template_size_label" label="模版样式" min-width="120" />
        <el-table-column label="邮费方式" width="110">
          <template #default="{ row }">{{ payTypeLabel(row.pay_type) }}</template>
        </el-table-column>
        <el-table-column label="上门揽件" width="110">
          <template #default="{ row }">
            <el-tag :type="row.need_pickup === 1 ? 'success' : 'info'" size="small" effect="plain">
              {{ row.need_pickup === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="100" />
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160" fixed="right">
          <template #default="{ row }">
            <el-button text type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button text type="danger" size="small" @click="handleDelete(row.id, row.name)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        class="pagination"
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        layout="total, sizes, prev, pager, next, jumper"
        @current-change="handlePageChange"
        @size-change="handleSizeChange"
      />
    </el-card>

    <!-- 设置 -->
    <div v-show="activeTab === 'settings'" class="settings-grid">
      <el-card shadow="never" class="settings-card">
        <template #header>服务商凭证</template>
        <el-form label-width="160px">
          <el-form-item label="启用电子面单">
            <el-switch v-model="settings.waybill_enabled" active-value="1" inactive-value="0" />
          </el-form-item>
          <el-form-item label="面单服务商">
            <el-select
              v-model="settings.waybill_provider"
              placeholder="请选择服务商"
              clearable
              style="width: 100%"
            >
              <el-option label="快递鸟" value="kdniao" />
            </el-select>
          </el-form-item>
          <el-form-item label="用户 ID（EBusinessID）">
            <el-input v-model="settings.waybill_app_key" placeholder="快递鸟用户 ID / EBusinessID" />
          </el-form-item>
          <el-form-item label="API Key（AppKey）">
            <el-input
              v-model="settings.waybill_app_secret"
              type="password"
              show-password
              placeholder="快递鸟 API Key / AppKey"
            />
          </el-form-item>
        </el-form>
      </el-card>

      <el-card shadow="never" class="settings-card">
        <template #header>发件人信息</template>
        <el-form label-width="120px">
          <el-form-item label="发件人姓名">
            <el-input v-model="settings.waybill_sender_name" placeholder="请输入发件人姓名" />
          </el-form-item>
          <el-form-item label="发件人电话">
            <el-input v-model="settings.waybill_sender_phone" placeholder="请输入发件人电话" />
          </el-form-item>
          <el-form-item label="所在地区">
            <RegionCascader
              v-model="senderRegionNames"
              placeholder="选择省 / 市 / 区"
              style="width: 100%; max-width: 420px"
              @change="onSenderRegionChange"
            />
          </el-form-item>
          <el-form-item label="详细地址">
            <el-input
              v-model="settings.waybill_sender_address"
              placeholder="详细地址（不含省市区）"
            />
          </el-form-item>
        </el-form>
      </el-card>

      <el-card shadow="never" class="settings-card settings-card--full">
        <template #header>打印机设置（C-Lodop）</template>
        <el-alert
          type="info"
          :closable="false"
          show-icon
          title="请在本机安装并启动 C-Lodop。页面按 HTTPS 8443、HTTP 8000（备用 18000）探测 CLodopfuncs.js / Lodopfuncs.js。电子面单优先 Lodop，不可用时回退浏览器打印。"
          class="mb-4"
        />
        <el-form label-width="160px" style="max-width: 640px">
          <el-form-item label="启用 Lodop">
            <el-switch
              v-model="settings.waybill_lodop_enabled"
              active-value="1"
              inactive-value="0"
            />
          </el-form-item>
          <el-form-item label="HTTP 端口">
            <el-input v-model="settings.waybill_lodop_http_port" placeholder="默认 8000" />
          </el-form-item>
          <el-form-item label="HTTPS 端口">
            <el-input v-model="settings.waybill_lodop_https_port" placeholder="默认 8443" />
          </el-form-item>
          <el-form-item label="连接状态">
            <span>{{ lodopHint }}</span>
            <el-button class="ml-3" @click="detectLodop">重新检测</el-button>
          </el-form-item>
        </el-form>
      </el-card>
    </div>

    <WaybillTemplateForm
      v-model="formVisible"
      :form-data="formData"
      :catalog="catalog"
      @success="fetchList"
    />
  </div>
</template>

<style lang="scss" scoped>
.waybill-config-container {
  padding: 0;
}

.order-filter {
  flex-wrap: wrap;
}

.order-seg {
  display: inline-flex;
  background: var(--ink-50);
  padding: 3px;
  border-radius: 8px;

  button {
    border: 0;
    background: transparent;
    padding: 6px 14px;
    font-size: 13px;
    color: var(--ink-600);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;

    &.on {
      background: #fff;
      color: var(--brand-600);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      font-weight: 600;
    }

    &:hover:not(.on) {
      color: var(--brand-500);
    }
  }
}

.code {
  margin-left: 6px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.ml-2 {
  margin-left: 8px;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.settings-card--full {
  grid-column: 1 / -1;
}

.mb-4 {
  margin-bottom: 16px;
}

.ml-3 {
  margin-left: 12px;
}

@media (max-width: 960px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }
}
</style>
