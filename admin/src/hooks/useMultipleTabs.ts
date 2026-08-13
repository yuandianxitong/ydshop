import type { TabPaneName } from 'element-plus'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import useTabsStore from '@/store/modules/multipleTabs.store'

/**
 * 多标签页钩子
 * 提供 tabsLists, currentTab, 以及对标签的增删改方法
 */
export default function useMultipleTabs() {
    const router = useRouter()
    const route = useRoute()
    const tabsStore = useTabsStore()

    const tabsLists = computed(() => tabsStore.getTabList)

    const currentTab = computed({
        get: () => route.fullPath,
        set: (fullPath: string) => {
            router.push(fullPath)
        }
    })

    const addTab = () => {
        tabsStore.addTab(router)
    }

    function removeTab(fullPath: TabPaneName) {
        tabsStore.removeTab(String(fullPath), router)
    }

    const removeOtherTab = () => {
        tabsStore.removeOtherTab(route)
    }

    const removeAllTab = () => {
        tabsStore.removeAllTab(router)
    }

    return {
        tabsLists,
        currentTab,
        addTab,
        removeTab,
        removeOtherTab,
        removeAllTab
    }
}
