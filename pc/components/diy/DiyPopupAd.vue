<template>
  <Teleport v-if="visible" to="body">
    <div class="diy-popup-ad" @click="onMaskClick">
      <div class="diy-popup-ad__inner" @click.stop>
        <img
          :src="ad?.image || ''"
          class="diy-popup-ad__img"
          alt="popup"
          @click="onImageClick"
        />
        <button class="diy-popup-ad__close" type="button" @click.stop="dismiss">×</button>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'

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

const router = useRouter()
const visible = ref(false)

const storageKey = (key: string) => `diy_popup_ad_seen_${key}`

function shouldShow(): boolean {
    const ad = props.ad
    if (!ad || !ad.enabled || !ad.image) return false
    if (typeof window === 'undefined') return false
    if (ad.display_type === 'every') return true
    try {
        return !window.localStorage.getItem(storageKey(props.pageKey))
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
    if (props.ad?.display_type === 'first' && typeof window !== 'undefined') {
        try {
            window.localStorage.setItem(storageKey(props.pageKey), '1')
        } catch { /* ignore */ }
    }
}

function onImageClick() {
    const link = props.ad?.link
    dismiss()
    if (!link) return
    if (/^https?:\/\//.test(link)) {
        window.open(link, '_blank')
    } else {
        router.push(link)
    }
}

function onMaskClick() {
    dismiss()
}

watch(() => props.ad, () => {
    if (!visible.value) show()
}, { deep: true })

onMounted(show)
</script>

<style scoped>
.diy-popup-ad {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.diy-popup-ad__inner {
    position: relative;
    max-width: 480px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.diy-popup-ad__img {
    max-width: 100%;
    max-height: calc(80vh - 60px);
    border-radius: 8px;
    background: #fff;
    cursor: pointer;
    display: block;
}

.diy-popup-ad__close {
    margin-top: 16px;
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.92);
    color: #333;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
