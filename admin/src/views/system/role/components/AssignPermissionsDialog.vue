<template>
    <el-dialog
        :model-value="visible"
        title=""
        width="920px"
        :show-close="false"
        :close-on-click-modal="false"
        append-to-body
        class="rp-dialog"
        @update:model-value="visible = $event"
        @close="handleClose"
    >
        <template #header>
            <div class="modal-head">
                <div class="modal-title">
                    权限分配
                    <span class="sub">为角色配置菜单权限与数据可见范围</span>
                </div>
                <button class="modal-close" @click="handleClose">
                    <i class="i-svg:x" />
                </button>
            </div>
        </template>

        <!-- Role Header Card -->
        <div class="rp-role-hd">
            <div class="rp-role-av">{{ roleInitial }}</div>
            <div class="rp-role-meta">
                <div class="rp-role-nm">
                    {{ roleInfo?.title }}
                    <span
                        class="rp-role-status"
                        :class="roleInfo?.status === 1 ? 'is-on' : 'is-off'"
                    >
                        {{ roleInfo?.status === 1 ? '启用' : '停用' }}
                    </span>
                </div>
                <div class="rp-role-sub">
                    <span class="rp-role-code">{{ roleInfo?.name }}</span>
                    <span class="rp-role-dot">&middot;</span>
                    <span>{{ checkedCount }} 项权限</span>
                </div>
            </div>
            <div class="rp-role-warn">
                <i class="i-svg:triangle-alert" />
                权限变更将实时生效，请谨慎操作
            </div>
        </div>

        <div class="rp-perm-panel">
            <!-- Toolbar -->
            <div class="rp-bar">
                <div class="rp-search">
                    <i class="i-svg:search rp-search-icon" />
                    <input
                        v-model="filterText"
                        type="text"
                        class="rp-search-input"
                        placeholder="搜索权限名称或标识"
                    />
                    <button
                        v-show="filterText"
                        class="rp-clr"
                        @click="filterText = ''"
                    >
                        <i class="i-svg:x" />
                    </button>
                </div>
                <div class="rp-bar-sp" />
                <label class="rp-cascade">
                    <input
                        v-model="cascadeChecked"
                        type="checkbox"
                    />
                    父子联动
                </label>
                <span class="rp-bar-sep" />
                <button class="rp-link" @click="expandAll">展开全部</button>
                <button class="rp-link" @click="collapseAll">收起全部</button>
                <span class="rp-bar-sep" />
                <button class="rp-link" @click="selectAll">全选</button>
                <button class="rp-link" @click="invertSelection">反选</button>
                <button class="rp-link" @click="clearAll">清空</button>
            </div>

            <!-- Permission Tree -->
            <div class="rp-tree">
                <el-tree
                    ref="menuTreeRef"
                    :data="menuTree"
                    :props="menuTreeProps"
                    show-checkbox
                    node-key="id"
                    :default-checked-keys="selectedMenus"
                    :default-expanded-keys="menuExpandKeys"
                    :check-strictly="!cascadeChecked"
                    :filter-node-method="filterNode"
                    class="rp-el-tree"
                    @check="handleMenuCheck"
                >
                    <template #default="{ node, data }">
                        <div
                            class="rp-node"
                            :class="[
                                `rp-d${node.level - 1 > 2 ? 2 : node.level - 1}`,
                            ]"
                        >
                            <span
                                class="rp-chev"
                                :class="{ expanded: node.expanded, leaf: node.isLeaf }"
                            />
                            <span
                                class="rp-type"
                                :class="{
                                    'is-dir': data.type === 1,
                                    'is-menu': data.type === 2,
                                    'is-btn': data.type === 3,
                                }"
                            >
                                {{ getMenuTypeText(data.type) }}
                            </span>
                            <span class="rp-name">{{ data.title }}</span>
                            <span v-if="data.permission" class="rp-code">{{
                                data.permission
                            }}</span>
                        </div>
                    </template>
                </el-tree>
            </div>
        </div>

        <template #footer>
            <div class="rp-foot">
                <div class="rp-foot-info">
                    已选 <b>{{ checkedCount }}</b> 项
                    <span class="rp-foot-sep">&middot;</span>
                    目录 <b>{{ stats.dir }}</b>
                    <span class="rp-foot-sep">&middot;</span>
                    菜单 <b>{{ stats.menu }}</b>
                    <span class="rp-foot-sep">&middot;</span>
                    按钮 <b>{{ stats.btn }}</b>
                </div>
                <div class="rp-foot-actions">
                    <el-button @click="handleClose">取消</el-button>
                    <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
                        保存
                    </el-button>
                </div>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts" name="AssignPermissionsDialog">
import { ElMessage, ElTree } from 'element-plus'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import { roleApi } from '@/api/role'
import type { MenuInfo, RoleInfo } from '@/types/system'

const { t } = useI18n()

interface Props {
    modelValue: boolean
    roleInfo: RoleInfo | null
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const menuTreeRef = ref<InstanceType<typeof ElTree>>()

const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// UI state
const cascadeChecked = ref(true)

// 菜单相关数据
const menuTree = ref<MenuInfo[]>([])
const selectedMenus = ref<number[]>([])
const menuExpandKeys = ref<number[]>([])
const menuCheckAll = ref(false)
const menuIndeterminate = ref(false)
const filterText = ref('')
const submitLoading = ref(false)

const menuTreeProps = {
    children: 'children',
    label: 'title'
}

// Role initial letter for avatar
const roleInitial = computed(() => {
    const title = props.roleInfo?.title || ''
    return title.charAt(0) || 'R'
})

// Stats for footer
const stats = computed(() => {
    const checkedKeys = menuTreeRef.value?.getCheckedKeys() || []
    const halfCheckedKeys = menuTreeRef.value?.getHalfCheckedKeys() || []
    const allKeys = new Set([...checkedKeys, ...halfCheckedKeys])
    let dir = 0
    let menu = 0
    let btn = 0

    const traverse = (items: MenuInfo[]) => {
        items.forEach((item) => {
            if (allKeys.has(item.id)) {
                if (item.type === 1) dir++
                else if (item.type === 2) menu++
                else if (item.type === 3) btn++
            }
            if (item.children?.length) traverse(item.children)
        })
    }
    traverse(menuTree.value)
    return { dir, menu, btn }
})

const checkedCount = computed(() => {
    const checkedKeys = menuTreeRef.value?.getCheckedKeys() || []
    const halfCheckedKeys = menuTreeRef.value?.getHalfCheckedKeys() || []
    return checkedKeys.length + halfCheckedKeys.length
})

// 搜索过滤
watch(filterText, (val) => {
    menuTreeRef.value?.filter(val)
})

const filterNode = (value: string, data: any) => {
    if (!value) return true
    const m = data as MenuInfo
    return m.title?.includes(value) || m.permission?.includes(value) || false
}

// 获取菜单树
const getMenuTree = async () => {
    try {
        const response = await roleApi.getMenuTree()
        const data = response.data ?? response
        const list = Array.isArray(data) ? data : ((data as any)?.data ?? [])
        menuTree.value = list
        menuExpandKeys.value = getExpandKeys(list)
    } catch (error) {
        console.error(t('message.fetchFailed'), error)
    }
}

// 获取角色已分配的权限
const getRolePermissions = async () => {
    if (!props.roleInfo?.id) return

    try {
        const response = await roleApi.getRolePermissions(props.roleInfo.id)
        selectedMenus.value = response.data.menu_ids || []
        updateMenuCheckAllStatus()
    } catch (error) {
        console.error(t('message.fetchFailed'), error)
    }
}

// 获取展开的菜单节点
const getExpandKeys = (menus: MenuInfo[]): number[] => {
    const keys: number[] = []
    const traverse = (items: MenuInfo[]) => {
        items.forEach((item) => {
            if (item.children && item.children.length > 0) {
                keys.push(item.id)
                traverse(item.children)
            }
        })
    }
    traverse(menus)
    return keys
}

// 菜单选择变化
const handleMenuCheck = () => {
    updateMenuCheckAllStatus()
}

// 更新菜单全选状态
const updateMenuCheckAllStatus = () => {
    const checkedKeys = menuTreeRef.value?.getCheckedKeys() || []
    const allKeys = getAllMenuKeys(menuTree.value)
    const checkedKeyCount = checkedKeys.length
    const totalCount = allKeys.length

    menuCheckAll.value = checkedKeyCount === totalCount && totalCount > 0
    menuIndeterminate.value = checkedKeyCount > 0 && checkedKeyCount < totalCount
}

// 获取所有菜单节点
const getAllMenuKeys = (tree: MenuInfo[]): number[] => {
    const keys: number[] = []
    const traverse = (items: MenuInfo[]) => {
        items.forEach((item) => {
            keys.push(item.id)
            if (item.children && item.children.length > 0) {
                traverse(item.children)
            }
        })
    }
    traverse(tree)
    return keys
}

// 菜单全选/取消全选
const handleMenuCheckAllChange = (checked: boolean | string | number) => {
    const allKeys = getAllMenuKeys(menuTree.value)
    if (checked) {
        menuTreeRef.value?.setCheckedKeys(allKeys)
    } else {
        menuTreeRef.value?.setCheckedKeys([])
    }
    updateMenuCheckAllStatus()
}

// 获取菜单类型文本
const getMenuTypeText = (type: number) => {
    const typeMap: Record<number, string> = {
        1: '目录',
        2: '菜单',
        3: '按钮'
    }
    return typeMap[type] || ''
}

// Toolbar actions
const expandAll = () => {
    const tree = menuTreeRef.value
    if (!tree) return
    const nodes = (tree as any).store._getAllNodes()
    nodes.forEach((n: any) => {
        if (!n.isLeaf) n.expanded = true
    })
}

const collapseAll = () => {
    const tree = menuTreeRef.value
    if (!tree) return
    const nodes = (tree as any).store._getAllNodes()
    nodes.forEach((n: any) => {
        if (!n.isLeaf) n.expanded = false
    })
}

const selectAll = () => {
    handleMenuCheckAllChange(true)
}

const invertSelection = () => {
    const allKeys = getAllMenuKeys(menuTree.value)
    const checkedKeys = new Set(menuTreeRef.value?.getCheckedKeys() || [])
    const newChecked = allKeys.filter((k) => !checkedKeys.has(k))
    menuTreeRef.value?.setCheckedKeys(newChecked)
    updateMenuCheckAllStatus()
}

const clearAll = () => {
    handleMenuCheckAllChange(false)
}

// 提交保存
const handleSubmit = async () => {
    if (!props.roleInfo?.id) return

    try {
        submitLoading.value = true

        // 获取选中的菜单ID（包含半选的父节点）
        const checkedKeys = (menuTreeRef.value?.getCheckedKeys() || []).map(Number).filter(Boolean)
        const halfCheckedKeys = (menuTreeRef.value?.getHalfCheckedKeys() || [])
            .map(Number)
            .filter(Boolean)

        const menuIds = [...checkedKeys, ...halfCheckedKeys]

        await roleApi.assignPermissions(props.roleInfo.id, {
            menu_ids: menuIds
        })
        ElMessage.success(t('role.assignSuccess'))
        emit('success')
        handleClose()
    } catch (error) {
        console.error('权限分配失败:', error)
    } finally {
        submitLoading.value = false
    }
}

// 关闭弹窗
const handleClose = () => {
    filterText.value = ''
    visible.value = false
}

// 监听角色信息变化
watch(
    () => props.roleInfo,
    (newRole) => {
        if (newRole && visible.value) {
            getRolePermissions()
        }
    },
    { immediate: true }
)

onMounted(() => {
    getMenuTree()
})
</script>

<style lang="scss" scoped>
@import './assign-permissions.scss';
</style>

<style lang="scss">
/* Unscoped overrides for el-dialog and el-tree */
.rp-dialog {
    .el-dialog__header {
        padding: 14px 20px;
        margin: 0;
        border-bottom: 1px solid var(--ink-100);
    }

    .el-dialog__body {
        padding: 20px 24px 4px;
    }

    .el-dialog__footer {
        padding: 14px 20px;
        border-top: 1px solid var(--ink-100);
        background: var(--ink-50);
    }
}

/* Override el-tree styles to match YDAdmin design */
.rp-el-tree {
    --el-tree-node-hover-bg-color: var(--ink-50);
    background: transparent;

    .el-tree-node__content {
        height: 34px;
        padding-left: 4px !important;
    }

    /* Hide default expand icon since we use .rp-chev */
    .el-tree-node__expand-icon {
        display: none;
    }

    .el-tree-node__content:hover {
        background: var(--ink-50);
    }

    .el-checkbox {
        margin-right: 4px;
    }
}
</style>
