<template>
  <div class="simulator" :class="{ 'simulator--pc': platform === 'pc' }">
    <div v-if="platform === 'uniapp'" class="simulator__phone">
      <img class="simulator__statusbar-img" src="data:image/png;base64,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" alt="statusbar" />
      <div
        v-if="pageSettings.show_header !== false"
        class="simulator__titlebar"
        :style="{ backgroundColor: pageSettings.background_color || '#fff' }"
        @click.stop="activatePageSettings"
      >
        <span class="simulator__titlebar-text">{{ pageTitle || '页面标题' }}</span>
      </div>
      <div ref="previewContainer" class="simulator__content" @drop="onDrop" @dragover.prevent @scroll="updateSelectedRect">
        <div v-if="components.length === 0" class="simulator__empty">拖拽左侧组件到此处</div>
        <div
          v-for="(comp, idx) in components"
          :key="comp.id"
          :data-diy-comp-id="comp.id"
          class="simulator__component"
          :class="{
            'simulator__component--active': selectedId === comp.id,
            'simulator__component--hidden': comp.hidden,
          }"
          :style="componentStyleToCss(comp.props?.componentStyle)"
          @click.stop="selectedId = comp.id"
          draggable="true"
          @dragstart="onSortStart($event, idx)"
          @dragover.prevent="onSortOver($event, idx)"
          @drop.stop="onSortDrop($event, idx)"
        >
          <component :is="getPreview(comp.type)" v-bind="comp.props" />
        </div>
      </div>
      <div class="simulator__home-indicator"></div>
    </div>
    <div v-else class="simulator__pc">
      <div ref="previewContainer" class="simulator__content" @drop="onDrop" @dragover.prevent @scroll="updateSelectedRect">
        <div v-if="components.length === 0" class="simulator__empty">拖拽左侧组件到此处</div>
        <div
          v-for="(comp, idx) in components"
          :key="comp.id"
          :data-diy-comp-id="comp.id"
          class="simulator__component"
          :class="{
            'simulator__component--active': selectedId === comp.id,
            'simulator__component--hidden': comp.hidden,
          }"
          :style="componentStyleToCss(comp.props?.componentStyle)"
          @click.stop="selectedId = comp.id"
          draggable="true"
          @dragstart="onSortStart($event, idx)"
          @dragover.prevent="onSortOver($event, idx)"
          @drop.stop="onSortDrop($event, idx)"
        >
          <component :is="getPreview(comp.type)" v-bind="comp.props" />
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="selectedRect && selectedComponent"
        class="diy-floating-actions"
        :style="{ top: selectedRect.top + 'px', left: selectedRect.left + 'px' }"
        @click.stop
      >
        <button
          class="diy-floating-actions__btn"
          :title="selectedComponent.hidden ? '显示' : '隐藏'"
          @click.stop="toggleHidden(selectedComponent.id)"
        >
          <i :class="selectedComponent.hidden ? 'i-lucide:eye-off' : 'i-lucide:eye'" />
        </button>
        <button
          class="diy-floating-actions__btn"
          title="删除"
          @click.stop="removeComponent(selectedComponent.id)"
        >
          <i class="i-svg:trash-2" />
        </button>
        <button
          class="diy-floating-actions__btn"
          title="复制"
          @click.stop="copyComponent(selectedComponent.id)"
        >
          <i class="i-svg:copy" />
        </button>
        <button
          class="diy-floating-actions__btn"
          title="上移"
          :disabled="selectedIdx <= 0"
          @click.stop="moveComponentUp(selectedComponent.id)"
        >
          <i class="i-lucide:chevron-up" />
        </button>
        <button
          class="diy-floating-actions__btn"
          title="下移"
          :disabled="selectedIdx === components.length - 1"
          @click.stop="moveComponentDown(selectedComponent.id)"
        >
          <i class="i-lucide:chevron-down" />
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { componentDefs, useEditor } from '../useEditor'
import { componentStyleToCss } from '../styleUtils'
import PreviewBanner from '../preview/PreviewBanner.vue'
import PreviewImageAd from '../preview/PreviewImageAd.vue'
import PreviewRichText from '../preview/PreviewRichText.vue'
import PreviewTitleBar from '../preview/PreviewTitleBar.vue'
import PreviewDivider from '../preview/PreviewDivider.vue'
import PreviewGoodsGrid from '../preview/PreviewGoodsGrid.vue'
import PreviewCategoryNav from '../preview/PreviewCategoryNav.vue'
import PreviewSearchBar from '../preview/PreviewSearchBar.vue'
import PreviewCouponList from '../preview/PreviewCouponList.vue'
import PreviewSeckill from '../preview/PreviewSeckill.vue'
import PreviewNotice from '../preview/PreviewNotice.vue'
import PreviewNavGrid from '../preview/PreviewNavGrid.vue'
import PreviewFloatButton from '../preview/PreviewFloatButton.vue'
import PreviewVideo from '../preview/PreviewVideo.vue'
import PreviewImageCube from '../preview/PreviewImageCube.vue'
import PreviewArticleList from '../preview/PreviewArticleList.vue'
import PreviewAdSlot from '../preview/PreviewAdSlot.vue'

const previewMap: Record<string, any> = {
    'banner': PreviewBanner,
    'image-ad': PreviewImageAd,
    'rich-text': PreviewRichText,
    'title-bar': PreviewTitleBar,
    'divider': PreviewDivider,
    'goods-grid': PreviewGoodsGrid,
    'category-nav': PreviewCategoryNav,
    'search-bar': PreviewSearchBar,
    'coupon-list': PreviewCouponList,
    'seckill': PreviewSeckill,
    'notice': PreviewNotice,
    'nav-grid': PreviewNavGrid,
    'float-button': PreviewFloatButton,
    'video': PreviewVideo,
    'image-cube': PreviewImageCube,
    'article-list': PreviewArticleList,
    'ad-slot': PreviewAdSlot,
}

const {
    components, selectedId, selectedComponent, platform, pageTitle, pageSettings,
    addComponent, removeComponent, copyComponent, saveSnapshot, activatePageSettings,
    moveComponentUp, moveComponentDown, toggleHidden,
} = useEditor()

function getPreview(type: string) {
    return previewMap[type] || null
}

function onDrop(e: DragEvent) {
    const type = e.dataTransfer?.getData('diy-component-type')
    if (type) {
        const def = componentDefs.find(d => d.type === type)
        if (def) addComponent(def)
    }
}

const dragIdx = ref<number | null>(null)

function onSortStart(e: DragEvent, idx: number) {
    dragIdx.value = idx
    e.dataTransfer?.setData('diy-sort', String(idx))
}

function onSortOver(_e: DragEvent, _idx: number) { /* prevent default handled inline */ }

function onSortDrop(_e: DragEvent, idx: number) {
    const fromIdx = dragIdx.value
    if (fromIdx === null || fromIdx === idx) return
    saveSnapshot()
    const [item] = components.value.splice(fromIdx, 1)
    components.value.splice(idx, 0, item)
    dragIdx.value = null
}

// ───── floating actions toolbar (anchored to selected component) ─────
const previewContainer = ref<HTMLElement>()
const selectedRect = ref<{ top: number; left: number } | null>(null)
const selectedIdx = computed(() =>
    components.value.findIndex(c => c.id === selectedId.value),
)

let resizeObserver: ResizeObserver | null = null

function updateSelectedRect() {
    if (!selectedId.value) {
        selectedRect.value = null
        return
    }
    const el = document.querySelector<HTMLElement>(
        `[data-diy-comp-id="${selectedId.value}"]`,
    )
    if (!el) {
        selectedRect.value = null
        return
    }
    const r = el.getBoundingClientRect()
    selectedRect.value = { top: r.top, left: r.right + 6 }
}

function observeSelectedEl() {
    resizeObserver?.disconnect()
    resizeObserver = null
    if (!selectedId.value) return
    nextTick(() => {
        const el = document.querySelector<HTMLElement>(
            `[data-diy-comp-id="${selectedId.value}"]`,
        )
        if (!el) return
        resizeObserver = new ResizeObserver(updateSelectedRect)
        resizeObserver.observe(el)
    })
}

watch(selectedId, () => {
    nextTick(updateSelectedRect)
    observeSelectedEl()
})

watch(
    () => components.value.length,
    () => nextTick(updateSelectedRect),
)

watch(
    () => selectedComponent.value?.props,
    () => nextTick(updateSelectedRect),
    { deep: true },
)

onMounted(() => {
    window.addEventListener('scroll', updateSelectedRect, true)
    window.addEventListener('resize', updateSelectedRect)
    nextTick(updateSelectedRect)
    observeSelectedEl()
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', updateSelectedRect, true)
    window.removeEventListener('resize', updateSelectedRect)
    resizeObserver?.disconnect()
})

// keep eslint happy: activatePageSettings is read in template
void activatePageSettings
</script>

<style lang="scss" scoped>
.simulator {
    display: flex;
    justify-content: center;

    &__phone {
        width: 375px;
        height: clamp(600px, calc(100vh - 120px), 844px);
        background: #fff;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
        display: flex;
        flex-direction: column;
    }

    &__statusbar-img {
        width: 100%;
        display: block;
        flex-shrink: 0;
    }

    &__titlebar {
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid #f0f0f0;
        flex-shrink: 0;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    &__titlebar-text {
        font-size: 16px;
        font-weight: 500;
        color: #333;
    }

    &__home-indicator {
        width: 134px;
        height: 5px;
        border-radius: 3px;
        background: #ccc;
        margin: 8px auto;
        flex-shrink: 0;
    }

    &__pc {
        width: 100%;
        max-width: 1200px;
        height: 100%;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    &__content {
        flex: 1;
        overflow-y: auto;
        min-height: 0;
    }

    &__empty {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 300px;
        color: #c0c4cc;
        font-size: 14px;
        border: 2px dashed #e4e7ed;
        margin: 16px;
        border-radius: 4px;
    }

    &__component {
        position: relative;
        cursor: pointer;
        border: 2px solid transparent;
        transition: border-color 0.2s;

        &:hover { border-color: #c6e2ff; }
        &--active { border-color: var(--el-color-primary) !important; }
        &--hidden {
            opacity: 0.4;
            background-image: repeating-linear-gradient(
                135deg,
                transparent 0,
                transparent 8px,
                rgba(0, 0, 0, 0.04) 8px,
                rgba(0, 0, 0, 0.04) 12px
            );
        }
    }
}
</style>

<style lang="scss">
.diy-floating-actions {
    position: fixed;
    display: flex;
    flex-direction: column;
    background: var(--el-color-primary);
    border-radius: 4px;
    overflow: hidden;
    z-index: 2000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
    pointer-events: auto;

    &__btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: 0;
        color: #fff;
        cursor: pointer;
        font-size: 14px;
        padding: 0;
        transition: background 0.15s;

        & + & {
            border-top: 1px solid rgba(255, 255, 255, 0.18);
        }

        &:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.18);
        }

        &:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
    }
}
</style>
