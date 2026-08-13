<template>
  <div class="preview-video" :style="{ aspectRatio: ratioValue, borderRadius: `${borderRadius || 0}px` }">
    <img v-if="poster" :src="poster" class="preview-video__poster" :style="{ objectFit: (objectFit || 'contain') as any }" />
    <div class="preview-video__play">
      <div class="preview-video__play-btn">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="#fff"><polygon points="8,5 19,12 8,19" /></svg>
      </div>
    </div>
    <div class="preview-video__bar">
      <span class="preview-video__time">0:00</span>
      <div class="preview-video__progress"><div class="preview-video__progress-track"></div></div>
      <span class="preview-video__time">0:30</span>
    </div>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ src?: string; poster?: string; autoplay?: boolean; loop?: boolean; muted?: boolean; aspectRatio?: string; borderRadius?: number; objectFit?: string }>()
const ratioMap: Record<string, string> = { '16:9': '16/9', '4:3': '4/3', '1:1': '1/1' }
const ratioValue = computed(() => ratioMap[props.aspectRatio || '16:9'] || '16/9')
</script>
<style scoped>
.preview-video { background: #000; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.preview-video__poster { position: absolute; inset: 0; width: 100%; height: 100%; }
.preview-video__play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; z-index: 1; }
.preview-video__play-btn { width: 48px; height: 48px; border-radius: 50%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; padding-left: 3px; }
.preview-video__bar { position: absolute; bottom: 0; left: 0; right: 0; display: flex; align-items: center; gap: 6px; padding: 6px 10px; z-index: 1; }
.preview-video__time { font-size: 10px; color: rgba(255,255,255,0.7); }
.preview-video__progress { flex: 1; height: 3px; background: rgba(255,255,255,0.2); border-radius: 2px; }
.preview-video__progress-track { width: 0%; height: 100%; background: #fff; border-radius: 2px; }
</style>
