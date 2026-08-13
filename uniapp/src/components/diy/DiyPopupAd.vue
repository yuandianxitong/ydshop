<template>
  <view v-if="visible" class="diy-popup-ad" @tap="onMaskTap">
    <view class="diy-popup-ad__inner" @tap.stop>
      <image
        :src="appStore.getImageUrl(ad?.image || '')"
        mode="widthFix"
        class="diy-popup-ad__img"
        @tap="onImageTap"
      />
      <view class="diy-popup-ad__close" @tap.stop="dismiss">
        <text class="diy-popup-ad__close-icon">×</text>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useAppStore } from '@/store/app.store'

interface PopupAd {
  enabled: boolean
  display_type: 'first' | 'every'
  image: string
  link: string
}

const props = defineProps<{
  ad: PopupAd | null | undefined
  pageKey: string
}>()

const appStore = useAppStore()
const visible = ref(false)

const storageKey = (key: string) => `diy_popup_ad_seen_${key}`

function shouldShow(): boolean {
    const ad = props.ad
    if (!ad || !ad.enabled || !ad.image) return false
    if (ad.display_type === 'every') return true
    try {
        return !uni.getStorageSync(storageKey(props.pageKey))
    } catch {
        return true
    }
}

function show() {
    if (shouldShow()) {
        visible.value = true
    }
}

function dismiss() {
    visible.value = false
    if (props.ad?.display_type === 'first') {
        try {
            uni.setStorageSync(storageKey(props.pageKey), '1')
        } catch { /* ignore */ }
    }
}

function onImageTap() {
    const link = props.ad?.link
    dismiss()
    if (link) {
        if (/^https?:\/\//.test(link)) {
            // external link: copy to clipboard as best-effort fallback in MP
            // most MP runtimes don't allow opening external URLs from popup
            // eslint-disable-next-line no-empty
            try { uni.setClipboardData({ data: link }) } catch {}
        } else {
            uni.navigateTo({ url: link, fail: () => uni.switchTab({ url: link, fail: () => {} }) })
        }
    }
}

function onMaskTap() {
    // tap on mask area dismisses but does not nav
    dismiss()
}

watch(() => props.ad, () => {
    if (!visible.value) show()
}, { deep: true })

onMounted(show)
</script>

<style lang="scss" scoped>
.diy-popup-ad {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;

    &__inner {
        position: relative;
        width: 600rpx;
        max-width: 80%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    &__img {
        width: 100%;
        border-radius: 16rpx;
        background: #fff;
    }

    &__close {
        margin-top: 24rpx;
        width: 64rpx;
        height: 64rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__close-icon {
        font-size: 40rpx;
        color: #333;
        line-height: 1;
    }
}
</style>
