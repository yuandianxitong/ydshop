<template>
    <div class="tabs-wrap">
        <div ref="tabsScrollRef" class="tabs">
            <div
                v-for="item in tabsLists"
                :key="item.fullPath"
                class="tab"
                :class="{ active: currentTab === item.fullPath }"
                @click="handleSelect(item)"
            >
                <span class="tab-dot" />
                <span class="tab-label">{{ translateRouteTitle(item.title, item.name) }}</span>
                <span
                    v-if="tabsLists.length > 1"
                    class="tab-close"
                    @click.stop="removeTab(item.fullPath)"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                    >
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </span>
            </div>
        </div>

        <el-dropdown @command="handleCommand">
            <div class="tabs-actions">
                <span class="tabs-act-btn">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        style="width: 14px; height: 14px; transform: rotate(90deg)"
                    >
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </span>
            </div>
            <template #dropdown>
                <el-dropdown-menu>
                    <el-dropdown-item command="closeCurrent">{{ $t('tabs.closeCurrent') }}</el-dropdown-item>
                    <el-dropdown-item command="closeOther">{{ $t('tabs.closeOther') }}</el-dropdown-item>
                    <el-dropdown-item command="closeAll">{{ $t('tabs.closeAll') }}</el-dropdown-item>
                </el-dropdown-menu>
            </template>
        </el-dropdown>
    </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

import useMultipleTabs from '@/hooks/useMultipleTabs'
import { useWatchRoute } from '@/hooks/useWatchRoute'
import { translateRouteTitle } from '@/utils/i18n'

const router = useRouter()
const tabsScrollRef = ref<HTMLElement>()

const { addTab, removeTab, removeOtherTab, removeAllTab, tabsLists, currentTab } = useMultipleTabs()

useWatchRoute(() => {
    addTab()
})

const handleSelect = (item: { fullPath: string; path: string; query?: Record<string, any> }) => {
    currentTab.value = item.fullPath
    router.push({ path: item.path, query: item.query })
}

const handleCommand = (command: string) => {
    switch (command) {
        case 'closeCurrent':
            removeTab(currentTab.value)
            break
        case 'closeOther':
            removeOtherTab()
            break
        case 'closeAll':
            removeAllTab()
            break
    }
}
</script>

<style lang="scss" scoped>
.tabs-wrap {
    display: flex;
    background: #fff;
    border-bottom: 1px solid var(--ink-100);
    height: var(--tabs-h);
    position: relative;
}

.tabs {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    padding: 0 16px;
    gap: 6px;
    overflow-x: auto;
    overflow-y: hidden;
    flex-wrap: nowrap;
    white-space: nowrap;
    scrollbar-width: thin;

    &::-webkit-scrollbar {
        height: 4px;
    }
    &::-webkit-scrollbar-thumb {
        background: var(--ink-200);
        border-radius: 2px;
        &:hover {
            background: var(--ink-300);
        }
    }
}

.tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12.5px;
    color: var(--ink-500);
    cursor: pointer;
    border: 1px solid transparent;
    flex-shrink: 0;
    white-space: nowrap;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
    user-select: none;

    .tab-close {
        display: none;
    }

    &:hover {
        background: var(--ink-50);
        color: var(--ink-700);

        .tab-close {
            display: flex;
        }
    }

    &.active {
        background: var(--brand-50);
        color: var(--brand-500);
        border-color: var(--brand-100);

        .tab-dot {
            background: var(--brand-500);
        }

        .tab-close {
            display: flex;
        }
    }
}

.tab-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--ink-300);
    flex-shrink: 0;
}

.tab-label {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tab-close {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    align-items: center;
    justify-content: center;
    color: var(--ink-400);
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;

    svg {
        width: 10px;
        height: 10px;
    }

    &:hover {
        background: var(--brand-500);
        color: #fff;
    }
}

.tabs-actions {
    flex-shrink: 0;
    height: var(--tabs-h);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 10px;
    border-left: 1px solid var(--ink-100);
}

.tabs-act-btn {
    width: 26px;
    height: 26px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ink-500);
    cursor: pointer;
    transition: background 0.15s, color 0.15s;

    &:hover {
        background: var(--ink-100);
        color: var(--ink-800);
    }
}
</style>
