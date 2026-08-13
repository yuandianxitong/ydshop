<template>
    <div class="menu-container">
        <div class="page-head">
            <div>
                <div class="page-title">{{ $t('menu.title') }}</div>
                <div class="page-desc">{{ $t('menu.desc') }}</div>
            </div>
            <div class="page-actions">
                <el-button @click="expandAllNodes">
                    {{ isExpandAll ? $t('common.collapseAll') : $t('common.expandAll') }}
                </el-button>
                <el-button
                    v-has-perm="['system.menu.create']"
                    type="primary"
                    @click="handleAdd()"
                >
                    <i class="i-svg:plus" />
                    {{ $t('menu.addMenu') }}
                </el-button>
            </div>
        </div>

        <div class="split">
            <!-- 左侧菜单树 -->
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-title">{{ $t('menu.menuStructure') }}</div>
                    <el-button
                        v-has-perm="['system.menu.create']"
                        size="small"
                        text
                        type="primary"
                        @click="handleAdd()"
                    >
                        <i class="i-svg:plus" />
                        {{ $t('common.add') }}
                    </el-button>
                </div>
                <div class="tree-search">
                    <el-input
                        v-model="treeFilter"
                        :placeholder="$t('menu.searchPlaceholder')"
                        clearable
                    >
                        <template #prefix><i class="i-svg:search" /></template>
                    </el-input>
                </div>
                <div v-loading="loading" class="tree">
                    <template v-for="node in filteredMenuList" :key="node.id">
                        <MenuTreeNode
                            :node="node"
                            :depth="0"
                            :selected-id="selectedMenuId"
                            :open-map="openMap"
                            @select="handleTreeSelect"
                            @toggle="handleTreeToggle"
                        />
                    </template>
                </div>
            </div>

            <!-- 右侧表单 -->
            <div class="panel">
                <template v-if="selectedMenu">
                    <div class="panel-head">
                        <div class="panel-title">{{ $t('menu.menuDetail') }}</div>
                        <div class="flex gap-1.5">
                            <el-button
                                v-has-perm="['system.menu.create']"
                                size="small"
                                @click="handleAdd(selectedMenu)"
                            >
                                {{ $t('menu.addChildMenu') }}
                            </el-button>
                            <el-button
                                v-has-perm="['system.menu.delete']"
                                size="small"
                                type="danger"
                                @click="handleDelete(selectedMenu)"
                            >
                                {{ $t('common.delete') }}
                            </el-button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <el-form
                            ref="editFormRef"
                            :model="editForm"
                            :rules="editRules"
                            label-position="top"
                        >
                            <div class="form-sec-title">{{ $t('menu.basicInfo') }}</div>
                            <div class="form-grid">
                                <div class="form-row">
                                    <div class="form-label">
                                        {{ $t('menu.menuType') }}<span class="req">*</span>
                                    </div>
                                    <el-radio-group
                                        v-model="editForm.type"
                                        @change="handleEditTypeChange"
                                    >
                                        <el-radio :value="1">{{
                                            $t('menu.typeOptions.directory')
                                        }}</el-radio>
                                        <el-radio :value="2">{{
                                            $t('menu.typeOptions.menu')
                                        }}</el-radio>
                                        <el-radio :value="3">{{
                                            $t('menu.typeOptions.button')
                                        }}</el-radio>
                                    </el-radio-group>
                                </div>
                                <div class="form-row">
                                    <div class="form-label">
                                        {{ $t('menu.parentMenu') }}<span class="req">*</span>
                                    </div>
                                    <el-tree-select
                                        v-model="editForm.parent_id"
                                        :data="editParentTreeData"
                                        node-key="id"
                                        :props="{ label: 'title', children: 'children' }"
                                        :placeholder="$t('menu.parentMenuPlaceholder')"
                                        check-strictly
                                        clearable
                                        default-expand-all
                                        style="width: 100%"
                                    />
                                </div>
                                <div class="form-row">
                                    <div class="form-label">
                                        {{ $t('menu.menuName') }}<span class="req">*</span>
                                    </div>
                                    <el-input
                                        v-model="editForm.title"
                                        :placeholder="$t('menu.namePlaceholder')"
                                    />
                                </div>
                                <div class="form-row">
                                    <div class="form-label">{{ $t('menu.permCode') }}</div>
                                    <el-input
                                        v-model="editForm.permission"
                                        :placeholder="$t('menu.permPlaceholder')"
                                    />
                                </div>
                                <div v-if="editForm.type !== 3" class="form-row">
                                    <div class="form-label">
                                        {{ $t('menu.routePath') }}<span class="req">*</span>
                                    </div>
                                    <el-input
                                        v-model="editForm.path"
                                        :placeholder="$t('menu.routePathPlaceholder')"
                                    />
                                </div>
                                <div v-if="editForm.type === 2" class="form-row">
                                    <div class="form-label">{{ $t('menu.componentPath') }}</div>
                                    <el-input
                                        v-model="editForm.component"
                                        :placeholder="$t('menu.componentPlaceholder')"
                                    >
                                        <template #prepend>src/views/</template>
                                        <template #append>.vue</template>
                                    </el-input>
                                </div>
                                <div v-if="editForm.type !== 3" class="form-row">
                                    <div class="form-label">{{ $t('menu.routeName') }}</div>
                                    <el-input
                                        v-model="editForm.name"
                                        :placeholder="$t('menu.routeNamePlaceholder')"
                                    />
                                </div>
                                <div class="form-row">
                                    <div class="form-label">{{ $t('common.sort') }}</div>
                                    <el-input-number
                                        v-model="editForm.sort"
                                        :min="0"
                                        :max="9999"
                                        controls-position="right"
                                        style="width: 100%"
                                    />
                                </div>
                                <div class="form-row">
                                    <div class="form-label">{{ $t('menu.isExternal') }}</div>
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            gap: 10px;
                                            padding-top: 6px;
                                        "
                                    >
                                        <el-switch
                                            v-model="editMetaForm.iframe"
                                            active-value="1"
                                            inactive-value=""
                                        />
                                        <span style="font-size: 12.5px; color: var(--ink-500)">
                                            {{ $t('menu.closeUseInternalRoute') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-sec-title" style="margin-top: 22px">{{ $t('menu.displayBehavior') }}</div>
                            <div class="form-grid">
                                <div v-if="editForm.type !== 3" class="form-row">
                                    <div class="form-label">{{ $t('menu.icon') }}</div>
                                    <IconSelect v-model="editForm.icon" width="100%" />
                                </div>
                                <div class="form-row">
                                    <div class="form-label">{{ $t('menu.isVisible') }}</div>
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            gap: 10px;
                                            padding-top: 6px;
                                        "
                                    >
                                        <el-switch
                                            v-model="editForm.status"
                                            :active-value="1"
                                            :inactive-value="0"
                                        />
                                        <span style="font-size: 12.5px; color: var(--ink-500)">
                                            {{ $t('menu.hiddenTip') }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="editForm.type !== 3" class="form-row">
                                    <div class="form-label">{{ $t('menu.isCache') }}</div>
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            gap: 10px;
                                            padding-top: 6px;
                                        "
                                    >
                                        <el-switch v-model="editMetaForm.cache" />
                                        <span style="font-size: 12.5px; color: var(--ink-500)">
                                            {{ $t('menu.cacheTip') }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="editForm.type !== 3" class="form-row">
                                    <div class="form-label">{{ $t('menu.isAffix') }}</div>
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            gap: 10px;
                                            padding-top: 6px;
                                        "
                                    >
                                        <el-switch v-model="editMetaForm.affix" />
                                        <span style="font-size: 12.5px; color: var(--ink-500)">
                                            {{ $t('menu.affixTip') }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="editForm.type !== 3" class="form-row">
                                    <div class="form-label">{{ $t('menu.isHidden') }}</div>
                                    <div
                                        style="
                                            display: flex;
                                            align-items: center;
                                            gap: 10px;
                                            padding-top: 6px;
                                        "
                                    >
                                        <el-switch v-model="editMetaForm.hidden" />
                                        <span style="font-size: 12.5px; color: var(--ink-500)">
                                            {{ $t('menu.hiddenFromNavTip') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-foot">
                                <el-button @click="handleEditCancel">{{ $t('common.cancel') }}</el-button>
                                <el-button @click="handleEditReset">{{ $t('common.reset') }}</el-button>
                                <el-button
                                    v-has-perm="['system.menu.update']"
                                    type="primary"
                                    :loading="submitting"
                                    @click="handleEditSave"
                                >
                                    {{ $t('menu.saveChanges') }}
                                </el-button>
                            </div>
                        </el-form>
                    </div>
                </template>
                <template v-else>
                    <div class="panel-head">
                        <div class="panel-title">{{ $t('menu.menuDetail') }}</div>
                    </div>
                    <div
                        class="panel-body"
                        style="
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 400px;
                            color: var(--ink-400);
                            font-size: 13px;
                        "
                    >
                        {{ $t('menu.selectMenuTip') }}
                    </div>
                </template>
            </div>
        </div>

        <!-- 新增弹窗 (仅新建时使用弹窗) -->
        <MenuForm
            v-model="formVisible"
            :form-data="formData"
            :parent-options="parentOptions"
            @success="getMenuList"
        />
    </div>
</template>

<script setup lang="ts" name="MenuList">
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { menuApi } from '@/api/menu'
import IconSelect from '@/components/IconSelect/index.vue'
import { useUserStore } from '@/store'
import type { MenuInfo, MenuMeta, MenuQuery, MenuReq } from '@/types/system'

import MenuForm from './components/MenuForm.vue'
import MenuTreeNode from './components/MenuTreeNode.vue'

const { t } = useI18n()
const userStore = useUserStore()

// 搜索表单
const searchForm = reactive<MenuQuery>({
    title: '',
    status: undefined,
    type: undefined
})

// 菜单列表
const menuList = ref<MenuInfo[]>([])
const loading = ref(false)

// 展开状态
const isExpandAll = ref(false)

// 弹窗相关（新增时使用弹窗）
const formVisible = ref(false)
const formData = ref<Partial<MenuInfo>>({})
const parentOptions = ref<any[]>([])

// ---- 树选择状态 ----
const treeFilter = ref('')
const selectedMenuId = ref<number | null>(null)
const openMap = ref<Record<number, boolean>>({})

// ---- 右侧编辑表单 ----
const editFormRef = ref<FormInstance>()
const submitting = ref(false)

interface EditFormData {
    id?: number
    parent_id: number
    type: number
    title: string
    name: string
    path: string
    component: string
    icon: string
    permission: string
    sort: number
    status: number
}

const defaultEditForm: EditFormData = {
    id: undefined,
    parent_id: 0,
    type: 1,
    title: '',
    name: '',
    path: '',
    component: '',
    icon: '',
    permission: '',
    sort: 100,
    status: 1
}

const editForm = reactive<EditFormData>({ ...defaultEditForm })

const defaultMeta: MenuMeta = {
    hidden: false,
    cache: true,
    affix: false,
    badge: '',
    iframe: ''
}

const editMetaForm = reactive<MenuMeta>({ ...defaultMeta })

// 父级菜单选项（编辑表单用）
const editParentOptions = ref<any[]>([])
const editParentTreeData = computed(() => [
    { id: 0, title: t('menu.topLevel'), children: [] },
    ...(editParentOptions.value || [])
])

// 编辑表单验证规则
const editRules = computed<FormRules>(() => ({
    title: [{ required: true, message: t('menu.validate.nameRequired'), trigger: 'blur' }],
    type: [{ required: true, message: t('menu.validate.typeRequired'), trigger: 'change' }],
    path: [
        {
            trigger: 'blur',
            validator: (_rule: any, value: string, callback: (error?: Error) => void) => {
                if (editForm.type === 3) {
                    callback()
                    return
                }
                if (!value) {
                    callback(new Error(t('menu.routePathPlaceholder')))
                    return
                }
                callback()
            }
        }
    ],
    component: [
        {
            trigger: 'blur',
            validator: (_rule: any, value: string, callback: (error?: Error) => void) => {
                if (editForm.type !== 2) {
                    callback()
                    return
                }
                if (!value) {
                    callback(new Error(t('menu.componentPlaceholder')))
                    return
                }
                callback()
            }
        }
    ]
}))

// 当前选中的菜单项
const selectedMenu = computed<MenuInfo | null>(() => {
    if (selectedMenuId.value == null) return null
    return findMenuById(menuList.value, selectedMenuId.value)
})

// ---- 树过滤 ----
const filteredMenuList = computed(() => {
    const keyword = treeFilter.value.trim().toLowerCase()
    if (!keyword) return menuList.value
    return filterTree(menuList.value, keyword)
})

function filterTree(list: MenuInfo[], keyword: string): MenuInfo[] {
    const result: MenuInfo[] = []
    for (const item of list) {
        const titleMatch = item.title?.toLowerCase().includes(keyword)
        const childMatches = item.children?.length ? filterTree(item.children, keyword) : []
        if (titleMatch || childMatches.length > 0) {
            result.push({
                ...item,
                children: titleMatch ? item.children : childMatches
            })
        }
    }
    return result
}

// 在树中查找菜单
function findMenuById(list: MenuInfo[], id: number): MenuInfo | null {
    for (const item of list) {
        if (item.id === id) return item
        if (item.children?.length) {
            const found = findMenuById(item.children, id)
            if (found) return found
        }
    }
    return null
}

// 获取菜单列表
const getMenuList = async () => {
    try {
        loading.value = true
        const params: MenuQuery = {}
        if (searchForm.title?.trim()) params.title = searchForm.title.trim()
        if (searchForm.status !== undefined) params.status = searchForm.status
        if (searchForm.type) params.type = searchForm.type

        const response = await menuApi.getMenuList(params)
        menuList.value = response.data

        // 如果当前有选中项，刷新编辑表单数据
        if (selectedMenuId.value != null) {
            const updated = findMenuById(menuList.value, selectedMenuId.value)
            if (updated) {
                syncEditForm(updated)
            } else {
                selectedMenuId.value = null
            }
        }
    } catch (error) {
        ElMessage.error(t('message.fetchFailed'))
    } finally {
        loading.value = false
    }
}

// 获取父级菜单选项
const getParentOptions = async (excludeId?: number) => {
    try {
        const response = await menuApi.getMenuOptions(excludeId)
        parentOptions.value = (response.data || []) as any[]
    } catch (error) {
        ElMessage.error(t('message.fetchFailed'))
    }
}

// 获取编辑表单的父级选项
const getEditParentOptions = async (excludeId?: number) => {
    try {
        const response = await menuApi.getMenuOptions(excludeId)
        editParentOptions.value = (response.data || []) as any[]
    } catch (error) {
        ElMessage.error(t('message.fetchFailed'))
    }
}

// 展开/折叠全部
const expandAllNodes = () => {
    isExpandAll.value = !isExpandAll.value
    const newMap: Record<number, boolean> = {}
    if (isExpandAll.value) {
        const setAll = (list: MenuInfo[]) => {
            for (const item of list) {
                if (item.children?.length) {
                    newMap[item.id] = true
                    setAll(item.children)
                }
            }
        }
        setAll(menuList.value)
    }
    openMap.value = newMap
}

// 树节点选中
const handleTreeSelect = (node: MenuInfo) => {
    selectedMenuId.value = node.id
    syncEditForm(node)
    getEditParentOptions(node.id)
}

// 树节点展开/折叠
const handleTreeToggle = (nodeId: number) => {
    openMap.value = { ...openMap.value, [nodeId]: !openMap.value[nodeId] }
}

// 同步选中的菜单到编辑表单
function syncEditForm(menu: MenuInfo) {
    Object.assign(editForm, {
        id: menu.id,
        parent_id: menu.parent_id ?? 0,
        type: menu.type,
        title: menu.title,
        name: menu.name || '',
        path: menu.path || '',
        component: menu.component || '',
        icon: menu.icon || '',
        permission: menu.permission || '',
        sort: menu.sort ?? 100,
        status: menu.status
    })
    if (menu.meta) {
        Object.assign(editMetaForm, defaultMeta, menu.meta)
    } else {
        Object.assign(editMetaForm, defaultMeta)
    }
    nextTick(() => editFormRef.value?.clearValidate())
}

// 编辑表单：类型变更
const handleEditTypeChange = () => {
    if (editForm.type === 3) {
        editForm.name = ''
        editForm.path = ''
        editForm.component = ''
        editForm.icon = ''
    }
}

// 编辑表单：取消
const handleEditCancel = () => {
    if (selectedMenu.value) {
        syncEditForm(selectedMenu.value)
    }
}

// 编辑表单：重置
const handleEditReset = () => {
    if (selectedMenu.value) {
        syncEditForm(selectedMenu.value)
    }
}

// 编辑表单：保存
const handleEditSave = async () => {
    if (!editFormRef.value) return
    try {
        await editFormRef.value.validate()
    } catch {
        return
    }

    if (!editForm.id) return

    try {
        submitting.value = true
        const { id, ...payload } = editForm
        await menuApi.updateMenu(id!, {
            ...payload,
            meta: payload.type !== 3 ? { ...editMetaForm } : undefined
        } as Partial<MenuReq>)
        ElMessage.success(t('message.updateSuccess'))
        await getMenuList()
    } catch (error) {
        ElMessage.error(t('common.error'))
    } finally {
        submitting.value = false
    }
}

// 新增菜单（使用弹窗）
const handleAdd = (parent?: MenuInfo) => {
    formData.value = {
        parent_id: parent?.id || 0,
        status: 1,
        sort: 100
    }
    getParentOptions()
    formVisible.value = true
}

// 删除菜单
const handleDelete = async (row: MenuInfo) => {
    try {
        await ElMessageBox.confirm(
            t('message.deleteConfirmName', { name: row.title }),
            t('message.confirmDelete'),
            {
                confirmButtonText: t('common.confirm'),
                cancelButtonText: t('common.cancel'),
                type: 'warning'
            }
        )

        await menuApi.deleteMenu(row.id)
        ElMessage.success(t('message.deleteSuccess'))

        if (selectedMenuId.value === row.id) {
            selectedMenuId.value = null
        }
        getMenuList()
    } catch (error) {
        if (error !== 'cancel') {
            ElMessage.error(t('common.error'))
        }
    }
}

// 状态变更
const handleStatusChange = async (row: MenuInfo) => {
    try {
        await menuApi.updateMenuStatus(row.id, { status: row.status })
        ElMessage.success(t('message.statusUpdateSuccess'))
    } catch (error) {
        row.status = row.status === 1 ? 0 : 1
        ElMessage.error(t('message.statusUpdateFailed'))
    }
}

onMounted(async () => {
    await getMenuList()
})
</script>

<style lang="scss" scoped>
.menu-container {
    .tree {
        min-height: 200px;
        max-height: calc(100vh - 260px);
        overflow-y: auto;
    }
}
</style>
