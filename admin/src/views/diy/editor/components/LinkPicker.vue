<template>
  <div class="link-picker">
    <el-input v-model="localValue" placeholder="输入或选择链接" @change="emitUpdate">
      <template #append>
        <el-button @click="dialogVisible = true"><i class="i-ri-link" /></el-button>
      </template>
    </el-input>

    <el-dialog v-model="dialogVisible" title="选择链接" width="600px" append-to-body>
      <div class="link-picker__body">
        <el-tabs v-model="activeTab" tab-position="left">
          <el-tab-pane v-for="cat in displayCategories" :key="cat.key" :label="cat.label" :name="cat.key">
            <div class="link-picker__items">
              <div
                v-for="item in cat.items"
                :key="item.path"
                class="link-picker__item"
                :class="{ 'link-picker__item--active': selectedPath === item.path }"
                @click="selectLink(item)"
              >
                <span>{{ item.label }}</span>
                <el-tag v-if="item.needSelect" size="small" type="info">需选择</el-tag>
              </div>
            </div>

            <div v-if="showSearch && searchType === 'goods'" class="link-picker__search">
              <el-input v-model="searchKeyword" placeholder="搜索商品" size="small" @input="onSearch">
                <template #prefix><i class="i-svg:search" /></template>
              </el-input>
              <div class="link-picker__results">
                <div v-for="r in searchResults" :key="r.id" class="link-picker__result" @click="selectResource(r)">
                  {{ r.title || r.name }} (ID: {{ r.id }})
                </div>
              </div>
            </div>

            <div v-if="showSearch && searchType === 'article'" class="link-picker__search">
              <el-input v-model="searchKeyword" placeholder="搜索文章" size="small" @input="onSearch">
                <template #prefix><i class="i-svg:search" /></template>
              </el-input>
              <div class="link-picker__results">
                <div v-for="r in searchResults" :key="r.id" class="link-picker__result" @click="selectResource(r)">
                  {{ r.title }} (ID: {{ r.id }})
                </div>
              </div>
            </div>

            <div v-if="showSearch && searchType === 'topic'" class="link-picker__search">
              <div class="link-picker__results">
                <div v-for="r in searchResults" :key="r.id" class="link-picker__result" @click="selectResource(r)">
                  {{ r.title }} (ID: {{ r.id }})
                </div>
              </div>
            </div>
          </el-tab-pane>

          <el-tab-pane label="自定义" name="custom">
            <el-input v-model="customUrl" placeholder="输入自定义链接" @change="localValue = customUrl; emitUpdate()" />
          </el-tab-pane>
        </el-tabs>
      </div>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="confirmSelect">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { linkCategories } from '../linkConfig'
import type { LinkItem } from '../linkConfig'
import { myRequest } from '@/utils/request'
import { useEditor } from '../useEditor'

const props = defineProps<{ modelValue: string; platform?: 'uniapp' | 'pc' }>()
const emit = defineEmits(['update:modelValue'])
const { catalogLinks, loadCatalog, platform: editorPlatform } = useEditor()

const displayCategories = computed(() => {
    if (catalogLinks.value.length > 0) {
        return catalogLinks.value.map((g) => ({
            key: g.key,
            label: g.label,
            items: g.items.map((item) => ({
                label: item.label,
                path: item.path,
                needSelect: !!(item.need_select),
                selectType: item.select_type || undefined,
            })),
        }))
    }
    return linkCategories
})

const localValue = ref(props.modelValue || '')
const dialogVisible = ref(false)
const activeTab = ref('basic')
const selectedPath = ref('')
const showSearch = ref(false)
const searchType = ref('')
const searchKeyword = ref('')
const searchResults = ref<any[]>([])
const customUrl = ref('')
let pendingItem: LinkItem | null = null

watch(() => props.modelValue, (v) => { localValue.value = v || '' })

watch(dialogVisible, async (open) => {
    if (!open) return
    await loadCatalog(props.platform || editorPlatform.value || 'uniapp')
    if (displayCategories.value.length && !displayCategories.value.some(c => c.key === activeTab.value)) {
        activeTab.value = displayCategories.value[0].key
    }
})

function emitUpdate() {
    emit('update:modelValue', localValue.value)
}

function selectLink(item: LinkItem) {
    selectedPath.value = item.path
    if (item.needSelect) {
        showSearch.value = true
        searchType.value = item.selectType || ''
        pendingItem = item
        searchKeyword.value = ''
        searchResults.value = []
        if (item.selectType === 'topic') {
            loadTopics()
        }
    } else {
        showSearch.value = false
        pendingItem = item
        localValue.value = item.path
    }
}

async function onSearch() {
    if (!searchKeyword.value.trim()) {
        searchResults.value = []
        return
    }
    try {
        if (searchType.value === 'goods') {
            const res = await myRequest.get('/adminapi/goods/goods-spu', { params: { keyword: searchKeyword.value, limit: 10 } })
            searchResults.value = res.data?.list || []
        } else if (searchType.value === 'article') {
            const res = await myRequest.get('/adminapi/article/list', { params: { keyword: searchKeyword.value, limit: 10 } })
            searchResults.value = res.data?.list || []
        }
    } catch {
        searchResults.value = []
    }
}

async function loadTopics() {
    try {
        const res = await myRequest.get('/adminapi/diy/page', { params: { page_type: 'custom', limit: 50 } })
        searchResults.value = res.data?.list || []
    } catch {
        searchResults.value = []
    }
}

function selectResource(resource: any) {
    if (pendingItem) {
        localValue.value = pendingItem.path + resource.id
    }
}

function confirmSelect() {
    emitUpdate()
    dialogVisible.value = false
    showSearch.value = false
}
</script>

<style lang="scss" scoped>
.link-picker__body {
    min-height: 300px;
    :deep(.el-tabs__item) { height: 36px; line-height: 36px; }
}
.link-picker__items { display: flex; flex-direction: column; gap: 4px; }
.link-picker__item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 12px; border-radius: 4px; cursor: pointer;
    &:hover { background: #f5f7fa; }
    &--active { background: var(--brand-50); color: var(--el-color-primary); }
}
.link-picker__search { margin-top: 12px; border-top: 1px solid #ebeef5; padding-top: 12px; }
.link-picker__results { max-height: 200px; overflow-y: auto; margin-top: 8px; }
.link-picker__result {
    padding: 6px 12px; cursor: pointer; font-size: 13px; border-radius: 4px;
    &:hover { background: #f5f7fa; }
}
</style>
