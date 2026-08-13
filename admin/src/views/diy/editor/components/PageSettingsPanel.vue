<template>
  <div class="page-settings-panel">
    <div class="page-settings-panel__item">
      <label class="page-settings-panel__label">页面标题</label>
      <el-input v-model="pageTitle" placeholder="请输入页面标题" />
    </div>
    <div class="page-settings-panel__item">
      <label class="page-settings-panel__label">显示头部导航栏</label>
      <el-switch v-model="showHeader" />
      <p class="page-settings-panel__tip">关闭后页面顶部不显示标题栏</p>
    </div>
    <div class="page-settings-panel__item">
      <label class="page-settings-panel__label">背景颜色</label>
      <div class="page-settings-panel__color-row">
        <el-color-picker v-model="bgColor" show-alpha />
        <el-input v-model="bgColor" placeholder="如 #ffffff" style="flex: 1; margin-left: 8px" />
      </div>
    </div>
    <div class="page-settings-panel__item">
      <label class="page-settings-panel__label">背景图片</label>
      <ImageSelect v-model="bgImage" />
    </div>

    <el-divider class="page-settings-panel__divider">弹窗广告</el-divider>

    <div class="page-settings-panel__item">
      <label class="page-settings-panel__label">是否显示</label>
      <el-switch v-model="popupEnabled" />
    </div>
    <template v-if="popupEnabled">
      <div class="page-settings-panel__item">
        <label class="page-settings-panel__label">显示类型</label>
        <el-radio-group v-model="popupDisplayType">
          <el-radio value="first">首次弹出</el-radio>
          <el-radio value="every">每次弹出</el-radio>
        </el-radio-group>
        <p class="page-settings-panel__tip">
          {{ popupDisplayType === 'first' ? '同一设备上仅首次进入页面时弹出' : '每次进入页面都会弹出' }}
        </p>
      </div>
      <div class="page-settings-panel__item">
        <label class="page-settings-panel__label">广告图</label>
        <ImageSelect v-model="popupImage" />
      </div>
      <div class="page-settings-panel__item">
        <label class="page-settings-panel__label">广告链接</label>
        <LinkPicker v-model="popupLink" />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useEditor } from '../useEditor'
import ImageSelect from '@/components/ImageSelect/index.vue'
import LinkPicker from './LinkPicker.vue'

const { pageTitle, pageSettings, updatePageSettings } = useEditor()

const bgColor = computed({
    get: () => pageSettings.value.background_color,
    set: (val: string) => updatePageSettings({ background_color: val || '' }),
})

const bgImage = computed({
    get: () => pageSettings.value.background_image,
    set: (val: string) => updatePageSettings({ background_image: val || '' }),
})

const showHeader = computed({
    get: () => pageSettings.value.show_header !== false,
    set: (val: boolean) => updatePageSettings({ show_header: val }),
})

function ensurePopupAd() {
    if (!pageSettings.value.popup_ad) {
        updatePageSettings({
            popup_ad: { enabled: false, display_type: 'first', image: '', link: '' },
        })
    }
}

const popupEnabled = computed({
    get: () => !!pageSettings.value.popup_ad?.enabled,
    set: (val: boolean) => {
        ensurePopupAd()
        updatePageSettings({
            popup_ad: { ...pageSettings.value.popup_ad!, enabled: val },
        })
    },
})

const popupDisplayType = computed({
    get: () => pageSettings.value.popup_ad?.display_type || 'first',
    set: (val: 'first' | 'every') => {
        ensurePopupAd()
        updatePageSettings({
            popup_ad: { ...pageSettings.value.popup_ad!, display_type: val },
        })
    },
})

const popupImage = computed({
    get: () => pageSettings.value.popup_ad?.image || '',
    set: (val: string) => {
        ensurePopupAd()
        updatePageSettings({
            popup_ad: { ...pageSettings.value.popup_ad!, image: val },
        })
    },
})

const popupLink = computed({
    get: () => pageSettings.value.popup_ad?.link || '',
    set: (val: string) => {
        ensurePopupAd()
        updatePageSettings({
            popup_ad: { ...pageSettings.value.popup_ad!, link: val },
        })
    },
})
</script>

<style lang="scss" scoped>
.page-settings-panel {
    padding: 16px;

    &__item {
        margin-bottom: 16px;
    }

    &__label {
        display: block;
        font-size: 13px;
        color: #606266;
        margin-bottom: 8px;
    }

    &__color-row {
        display: flex;
        align-items: center;
    }

    &__divider {
        margin: 8px 0 16px;
        font-size: 12px;
        color: #909399;
    }

    &__tip {
        margin: 6px 0 0;
        font-size: 12px;
        color: #909399;
        line-height: 1.5;
    }
}
</style>
