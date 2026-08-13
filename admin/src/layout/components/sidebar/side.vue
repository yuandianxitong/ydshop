<template>
    <!-- 一级菜单（图标栏） -->
    <aside class="sidebar-main flex-shrink-0 h-full flex flex-col">
        <div class="sidebar-logo">
            <div class="logo-mark">
                <Logo />
            </div>
        </div>
        <nav class="nav flex-1 overflow-y-auto">
            <div
                v-for="item in topRoutes"
                :key="item.path"
                :class="['nav-item', selectedFirst === item.path ? 'active' : '']"
                @click="onFirstSelect(item.path)"
            >
                <Icon :name="item.meta?.icon ?? ''" class="nav-ic" />
                <span class="nav-label">{{
                    translateRouteTitle(item.meta?.title, item.name)
                }}</span>
            </div>
        </nav>
    </aside>

    <!-- 二级菜单（子菜单面板） -->
    <aside
        v-if="secondRoutes.length || inWorkspace"
        class="sidebar-sub flex-shrink-0 h-full flex flex-col"
    >
        <div v-if="inWorkspace" class="sub-head workspace-head">
            <a class="workspace-back" @click="exitWorkspace">
                <Icon name="i-lucide:arrow-left" class="back-ic" />
                <span>返回插件列表</span>
            </a>
        </div>
        <div v-else-if="selectedFirstRoute" class="sub-head">
            <span>{{
                translateRouteTitle(selectedFirstRoute.meta?.title, selectedFirstRoute.name)
            }}</span>
        </div>
        <el-scrollbar class="flex-1">
            <div class="sub-list">
                <el-menu
                    :default-active="currentFullPath"
                    router
                    unique-opened
                    class="h-full !border-none"
                    background-color="transparent"
                    text-color="var(--ink-600)"
                    @select="onSecondSelect"
                >
                    <template v-for="item in secondRoutes">
                        <el-menu-item
                            v-if="!item.children?.length"
                            :key="`item-${resolvePath(item.path)}`"
                            :index="resolvePath(item.path)"
                            class="sub-item"
                        >
                            <span class="sub-dot" />
                            <span class="sub-label">{{
                                translateRouteTitle(item.meta?.title, item.name)
                            }}</span>
                        </el-menu-item>
                        <el-sub-menu
                            v-else
                            :key="`submenu-${resolvePath(item.path)}`"
                            :index="resolvePath(item.path)"
                        >
                            <template #title>
                                <span class="sub-label">{{
                                    translateRouteTitle(item.meta?.title, item.name)
                                }}</span>
                            </template>
                            <el-menu-item
                                v-for="sub in item.children"
                                :key="`item-${resolvePath(sub.path)}`"
                                :index="resolvePath(sub.path)"
                                class="sub-item"
                            >
                                <span class="sub-dot" />
                                <span class="sub-label">{{
                                    translateRouteTitle(sub.meta?.title, sub.name)
                                }}</span>
                            </el-menu-item>
                        </el-sub-menu>
                    </template>
                </el-menu>
            </div>
        </el-scrollbar>
    </aside>
</template>

<script setup lang="ts">
import { type RouteRecordRaw, useRoute, useRouter } from 'vue-router'

import { useUserStore } from '@/store'
import { translateRouteTitle } from '@/utils/i18n'

import Logo from './logo.vue'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const routes = computed(() => userStore.routes as RouteRecordRaw[])

const topRoutes = computed(() => routes.value.filter((r) => !r.meta?.hidden))

const topPaths = computed(() => topRoutes.value.map((r) => r.path))
const selectedFirst = computed<string>(() => {
    const match = route.matched.find((m) => topPaths.value.includes(m.path))
    return match ? match.path : topPaths.value[0]
})

const selectedFirstRoute = computed(() =>
    routes.value.find((r) => r.path === selectedFirst.value)
)

// Workspace mode: when path looks like /plugin/<code>/... the user is inside
// a workspace-mode plugin (parent_menu=Application). The sidebar col 2 swaps
// in this plugin's submenu (from workspaceMenus) plus a "back to apps" item.
const workspaceCode = computed<string | null>(() => {
    // Either we're at the entry URL /plugin/<code>, or we've redirected to a
    // leaf and the matched route carries meta.plugin (set by generatePluginRoutes).
    const m = route.path.match(/^\/plugin\/([^/]+)/)
    if (m) return m[1]
    const fromMeta = route.matched.find((r) => (r.meta as any)?.plugin)
    return ((fromMeta?.meta as any)?.plugin as string) || null
})
const inWorkspace = computed(() => workspaceCode.value !== null)

const secondRoutes = computed(() => {
    if (workspaceCode.value) {
        // Workspace plugin menus are all `is_hidden=true` by design (that's
        // how the backend partitions them into workspace_menus instead of the
        // main sidebar). Don't filter by hidden here — show them all.
        return (userStore.workspaceMenus[workspaceCode.value] || []) as RouteRecordRaw[]
    }
    return (selectedFirstRoute.value?.children || []).filter((r) => !r.meta?.hidden)
})

function exitWorkspace() {
    router.push('/plugins/installed')
}

const currentFullPath = computed(() => route.fullPath)

function findFirstLeafPath(route: RouteRecordRaw, parentPath: string): string | null {
    const fullPath = route.path.startsWith('/') ? route.path : `${parentPath}/${route.path}`
    if (route.meta?.type === 2 && !route.meta?.hidden) {
        return fullPath
    }
    const children = (route.children || []).filter((r) => !r.meta?.hidden)
    for (const child of children) {
        const found = findFirstLeafPath(child, fullPath)
        if (found) return found
    }
    return null
}

function onFirstSelect(path: string) {
    const parent = routes.value.find((r) => r.path === path)
    if (parent) {
        const leafPath = findFirstLeafPath(parent, '')
        if (leafPath) {
            router.push(leafPath)
            return
        }
    }
    router.push(path)
}

function onSecondSelect(path: string) {
    router.push(path)
}

function resolvePath(p: string) {
    return p.startsWith('/') ? p : `${selectedFirst.value}/${p}`
}
</script>

<style lang="scss" scoped>
/* ── 第一列：图标栏 ── */
.sidebar-main {
    width: var(--sidebar-w);
    background: var(--color-sidebar-bg);
    display: flex;
    flex-direction: column;
}

.sidebar-logo {
    height: var(--header-h);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.logo-mark {
    display: flex;
    align-items: center;
    justify-content: center;

    :deep(> div) {
        padding: 0 !important;
        height: auto !important;
    }

    :deep(img) {
        width: 42px;
        height: 42px;
        object-fit: contain;
        display: block;
    }
}

.nav {
    flex: 1;
    overflow-y: auto;
    padding: 12px 8px;
    display: flex;
    flex-direction: column;
    gap: 2px;

    &::-webkit-scrollbar {
        width: 0;
    }
}

.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 6px;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.45);
    cursor: pointer;
    position: relative;
    transition: all 0.18s ease;

    &:hover {
        background: rgba(255, 255, 255, 0.05);
        color: rgba(255, 255, 255, 0.8);
    }

    &.active {
        background: linear-gradient(135deg, var(--brand-500), var(--brand-600));
        box-shadow: 0 6px 18px var(--brand-shadow);
        color: #fff;
    }
}

.nav-ic {
    width: 22px;
    height: 22px;
    display: block;
    flex-shrink: 0;
}

.nav-label {
    font-size: 12px;
    line-height: 1;
    letter-spacing: 0.2px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    color: inherit;
}

/* ── 第二列：子菜单面板 ── */
.sidebar-sub {
    width: var(--sidebar-sub-w, 180px);
    background: #fff;
    border-right: 1px solid var(--ink-100);
    display: flex;
    flex-direction: column;
    height: 100vh;
}

.sub-head {
    height: var(--header-h);
    display: flex;
    align-items: center;
    padding: 0 14px;
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-900);
    border-bottom: 1px solid var(--ink-100);
    flex-shrink: 0;
    letter-spacing: 0.5px;

    &.workspace-head {
        font-weight: 500;
    }
}

.workspace-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--el-color-primary);
    cursor: pointer;
    font-size: 13px;

    .back-ic {
        font-size: 14px;
    }

    &:hover {
        opacity: 0.8;
    }
}

.sub-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px;

    &::-webkit-scrollbar {
        width: 4px;
    }

    &::-webkit-scrollbar-thumb {
        background: var(--ink-200);
        border-radius: 2px;
    }
}

:deep(.el-menu-item.sub-item) {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px !important;
    font-size: 13px;
    color: var(--ink-600);
    border-radius: 4px;
    height: auto;
    line-height: normal;
    cursor: pointer;
    transition: all 0.15s;

    &:hover {
        background: var(--ink-50) !important;
        color: var(--ink-800) !important;
    }

    &.is-active {
        background: var(--brand-50) !important;
        color: var(--brand-600) !important;
        font-weight: 500;

        .sub-dot {
            background: var(--brand-500);
            box-shadow: 0 0 0 2px var(--brand-100);
        }
    }
}

.sub-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--ink-300);
    flex-shrink: 0;
}

.sub-label {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

:deep(.el-sub-menu .el-sub-menu__title) {
    padding: 8px 12px !important;
    font-size: 13px;
    color: var(--ink-700);
    font-weight: 500;
    height: auto;
    line-height: normal;
    border-radius: 4px;

    &:hover {
        background: var(--ink-50) !important;
        color: var(--ink-800) !important;
    }
}

:deep(.el-menu--inline) {
    background: transparent !important;
}
</style>
