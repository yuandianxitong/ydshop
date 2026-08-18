import { ref, computed } from 'vue'
import { diyApi, type DiyCatalogLinkGroup, type DiyCatalogWidget, type DiyComponent, type DiyPageSettings } from '@/api/diy'
import { defaultComponentStyle } from './styleUtils'

export interface ComponentDef {
    type: string
    label: string
    icon: string
    group: string
    defaultProps: Record<string, any>
}

export const componentDefs: ComponentDef[] = [
    { type: 'banner', label: '轮播图', icon: 'i-svg:diy-banner', group: '基础组件', defaultProps: { items: [], autoplay: true, interval: 3000, height: 360, indicatorType: 'dot', indicatorPosition: 'center', animation: 'slide', objectFit: 'cover', componentStyle: { ...defaultComponentStyle } } },
    { type: 'image-ad', label: '图片广告', icon: 'i-svg:diy-image-ad', group: '基础组件', defaultProps: { items: [], layout: 'single', imageGap: 0, imageRadius: 0, objectFit: 'cover', componentStyle: { ...defaultComponentStyle } } },
    { type: 'rich-text', label: '富文本', icon: 'i-svg:diy-rich-text', group: '基础组件', defaultProps: { content: '', componentStyle: { ...defaultComponentStyle } } },
    { type: 'title-bar', label: '标题栏', icon: 'i-svg:diy-title-bar', group: '基础组件', defaultProps: { title: '标题', subtitle: '', align: 'left', more_url: '', more_text: '', titleSize: 16, titleColor: '#333333', titleWeight: 'bold', subtitleSize: 12, subtitleColor: '#999999', moreText: '查看更多', moreColor: '#999999', componentStyle: { ...defaultComponentStyle } } },
    { type: 'divider', label: '辅助分割', icon: 'i-svg:diy-divider', group: '基础组件', defaultProps: { type: 'blank', height: 20, color: '#eeeeee', lineStyle: 'solid', lineWidth: 1, componentStyle: { ...defaultComponentStyle } } },
    { type: 'video', label: '视频', icon: 'i-svg:diy-video', group: '基础组件', defaultProps: { src: '', poster: '', autoplay: false, loop: false, muted: false, aspectRatio: '16:9', borderRadius: 0, objectFit: 'contain', componentStyle: { ...defaultComponentStyle } } },
    { type: 'image-cube', label: '图片魔方', icon: 'i-svg:diy-image-cube', group: '基础组件', defaultProps: { rows: 2, cols: 3, gap: 4, borderRadius: 0, marginTop: 16, marginBottom: 16, items: [], componentStyle: { ...defaultComponentStyle } } },
    { type: 'article-list', label: '文章列表', icon: 'i-svg:diy-article-list', group: '基础组件', defaultProps: { source: 'all', category_id: null, limit: 5, layout: 'left-image', showSummary: true, showViewCount: true, showDate: true, componentStyle: { ...defaultComponentStyle } } },
    { type: 'goods-grid', label: '商品组', icon: 'i-svg:diy-goods-grid', group: '商品组件', defaultProps: { title: '', source: 'manual', goods_ids: [], category_id: null, tag: null, limit: 8, columns: 2, cardStyle: 'shadow', showName: true, showPrice: true, showBuyBtn: false, priceColor: '#ff4d4f', btnText: '购买', btnColor: '#ff4d4f', imageRatio: '1:1', listMode: 'grid', componentStyle: { ...defaultComponentStyle } } },
    { type: 'category-nav', label: '分类导航', icon: 'i-svg:diy-category-nav', group: '商品组件', defaultProps: { style: 'icon-grid', items: [], rows: 2, columns: 5, iconSize: 48, fontSize: 12, fontColor: '#333333', iconShape: 'circle', componentStyle: { ...defaultComponentStyle } } },
    { type: 'search-bar', label: '搜索框', icon: 'i-svg:diy-search-bar', group: '商品组件', defaultProps: { placeholder: '搜索商品', border_radius: 20, bg_color: '#f5f5f5', textAlign: 'left', iconColor: '#c0c4cc', inputHeight: 36, componentStyle: { ...defaultComponentStyle } } },
    { type: 'coupon-list', label: '优惠券', icon: 'i-svg:diy-coupon', group: '营销组件', defaultProps: { coupon_ids: [], style: 'horizontal', themeColor: '#ff4d4f', btnText: '立即领取', showCondition: true, componentStyle: { ...defaultComponentStyle } } },
    { type: 'ad-slot', label: '广告位', icon: 'i-lucide:image', group: '营销组件', defaultProps: { position_code: '', height: 0, componentStyle: { ...defaultComponentStyle } } },
    { type: 'seckill', label: '秒杀', icon: 'i-svg:diy-seckill', group: '营销组件', defaultProps: { activity_id: null, limit: 4, showCountdown: true, countdownStyle: 'standard', showProgress: true, themeColor: '#ff4d4f', componentStyle: { ...defaultComponentStyle } } },
    { type: 'notice', label: '公告', icon: 'i-svg:diy-notice', group: '营销组件', defaultProps: { source: 'auto', items: [], speed: 50, leftIcon: '', textColor: '#333333', scrollDirection: 'horizontal', showClose: false, componentStyle: { ...defaultComponentStyle } } },
    { type: 'nav-grid', label: '图文导航', icon: 'i-svg:diy-nav-grid', group: '导航组件', defaultProps: { items: [], columns: 4, rows: 1, iconSize: 40, fontSize: 12, fontColor: '#333333', componentStyle: { ...defaultComponentStyle } } },
    { type: 'float-button', label: '悬浮按钮', icon: 'i-svg:diy-float-button', group: '导航组件', defaultProps: { items: [], position: 'right-bottom', btnSize: 50, defaultOpacity: 100, hoverOpacity: 100, componentStyle: { ...defaultComponentStyle } } },
]

const components = ref<DiyComponent[]>([])
const selectedId = ref<string | null>(null)
const platform = ref<'uniapp' | 'pc'>('uniapp')
const pageType = ref<string>('home')
const undoStack = ref<string[]>([])
const redoStack = ref<string[]>([])
const pageTitle = ref('')
const pageSettings = ref<DiyPageSettings>({
    background_color: '',
    background_image: '',
    show_header: true,
    popup_ad: {
        enabled: false,
        display_type: 'first',
        image: '',
        link: '',
    },
})
const pageSettingsActive = ref(false)
const pageId = ref<number>(0)
const previewMode = ref(false)
const previewMeta = ref<{ versionNo: number } | null>(null)
const previewBackup = ref<{
    title: string
    components: DiyComponent[]
    page_settings: DiyPageSettings
} | null>(null)
const catalogLinks = ref<DiyCatalogLinkGroup[]>([])
const catalogWidgets = ref<DiyCatalogWidget[]>([])
const catalogLoaded = ref(false)

export function useEditor() {
    const selectedComponent = computed(() => {
        if (!selectedId.value) return null
        return components.value.find(c => c.id === selectedId.value) || null
    })

    function updatePageSettings(settings: Partial<DiyPageSettings>) {
        Object.assign(pageSettings.value, settings)
    }

    function activatePageSettings() {
        selectedId.value = null
        pageSettingsActive.value = true
    }

    function saveSnapshot() {
        undoStack.value.push(JSON.stringify(components.value))
        redoStack.value = []
        if (undoStack.value.length > 50) undoStack.value.shift()
    }

    function addComponent(def: ComponentDef) {
        saveSnapshot()
        const id = `comp_${Date.now()}_${Math.random().toString(36).slice(2, 6)}`
        const newComp: DiyComponent = {
            id,
            type: def.type,
            props: JSON.parse(JSON.stringify(def.defaultProps))
        }
        const selIdx = selectedId.value
            ? components.value.findIndex(c => c.id === selectedId.value)
            : -1
        if (selIdx !== -1) {
            components.value.splice(selIdx + 1, 0, newComp)
        } else {
            components.value.push(newComp)
        }
        selectedId.value = id
        pageSettingsActive.value = false
    }

    function moveComponentUp(id: string) {
        const idx = components.value.findIndex(c => c.id === id)
        if (idx <= 0) return
        saveSnapshot()
        const [item] = components.value.splice(idx, 1)
        components.value.splice(idx - 1, 0, item)
    }

    function moveComponentDown(id: string) {
        const idx = components.value.findIndex(c => c.id === id)
        if (idx === -1 || idx >= components.value.length - 1) return
        saveSnapshot()
        const [item] = components.value.splice(idx, 1)
        components.value.splice(idx + 1, 0, item)
    }

    function toggleHidden(id: string) {
        const comp = components.value.find(c => c.id === id)
        if (!comp) return
        saveSnapshot()
        comp.hidden = !comp.hidden
    }

    function removeComponent(id: string) {
        saveSnapshot()
        const idx = components.value.findIndex(c => c.id === id)
        if (idx !== -1) {
            components.value.splice(idx, 1)
            if (selectedId.value === id) selectedId.value = null
        }
    }

    function copyComponent(id: string) {
        saveSnapshot()
        const src = components.value.find(c => c.id === id)
        if (!src) return
        const idx = components.value.findIndex(c => c.id === id)
        const newId = `comp_${Date.now()}_${Math.random().toString(36).slice(2, 6)}`
        const copy: DiyComponent = {
            id: newId,
            type: src.type,
            props: JSON.parse(JSON.stringify(src.props))
        }
        components.value.splice(idx + 1, 0, copy)
        selectedId.value = newId
        pageSettingsActive.value = false
    }

    function updateProps(id: string, props: Record<string, any>) {
        const comp = components.value.find(c => c.id === id)
        if (comp) {
            saveSnapshot()
            comp.props = { ...comp.props, ...props }
        }
    }

    function undo() {
        if (undoStack.value.length === 0) return
        redoStack.value.push(JSON.stringify(components.value))
        components.value = JSON.parse(undoStack.value.pop()!)
    }

    function redo() {
        if (redoStack.value.length === 0) return
        undoStack.value.push(JSON.stringify(components.value))
        components.value = JSON.parse(redoStack.value.pop()!)
    }

    function setComponents(data: DiyComponent[], title?: string, settings?: DiyPageSettings) {
        components.value = data
        undoStack.value = []
        redoStack.value = []
        selectedId.value = null
        if (title !== undefined) pageTitle.value = title
        if (settings) pageSettings.value = { ...pageSettings.value, ...settings }
    }

    function enterPreview(snapshot: {
        title: string
        components: DiyComponent[]
        page_settings: DiyPageSettings | null
        versionNo: number
    }) {
        previewBackup.value = {
            title: pageTitle.value,
            components: JSON.parse(JSON.stringify(components.value)),
            page_settings: { ...pageSettings.value },
        }
        pageTitle.value = snapshot.title
        components.value = JSON.parse(JSON.stringify(snapshot.components))
        pageSettings.value = snapshot.page_settings
            ? { ...snapshot.page_settings }
            : { background_color: '', background_image: '' }
        previewMode.value = true
        previewMeta.value = { versionNo: snapshot.versionNo }
    }

    function exitPreview() {
        if (!previewBackup.value) return
        pageTitle.value = previewBackup.value.title
        components.value = JSON.parse(JSON.stringify(previewBackup.value.components))
        pageSettings.value = { ...previewBackup.value.page_settings }
        previewMode.value = false
        previewMeta.value = null
        previewBackup.value = null
    }

    async function loadCatalog(forPlatform?: 'uniapp' | 'pc') {
        const p = forPlatform || platform.value
        try {
            const res = await diyApi.getCatalog(p)
            catalogLinks.value = res.data?.links || []
            catalogWidgets.value = res.data?.widgets || []
            catalogLoaded.value = true
        } catch {
            catalogLinks.value = []
            catalogWidgets.value = []
            catalogLoaded.value = false
        }
    }

    async function reloadPage() {
        if (!pageId.value) return
        const res = await diyApi.getPageDetail(pageId.value)
        platform.value = res.data.platform as 'uniapp' | 'pc'
        pageType.value = res.data.page_type
        setComponents(
            res.data.components || [],
            res.data.title,
            res.data.page_settings || undefined,
        )
    }

    return {
        components, selectedId, selectedComponent, platform, pageType,
        undoStack, redoStack,
        pageTitle, pageSettings, pageSettingsActive,
        pageId, previewMode, previewMeta,
        addComponent, removeComponent, copyComponent, updateProps,
        moveComponentUp, moveComponentDown, toggleHidden,
        undo, redo, setComponents, saveSnapshot,
        updatePageSettings, activatePageSettings,
        enterPreview, exitPreview, reloadPage, loadCatalog,
        catalogLinks, catalogWidgets, catalogLoaded,
    }
}
