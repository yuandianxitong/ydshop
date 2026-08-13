<template>
  <view class="diy-custom-page">
    <DiyRenderer v-if="pageComponents.length" :components="pageComponents" />
    <view v-else-if="!loading" class="diy-custom-page__empty">
      <text>页面不存在或未发布</text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import DiyRenderer from '@/components/diy/DiyRenderer.vue'
import { diyApi } from '@/api/diy'

const loading = ref(true)
const pageComponents = ref<any[]>([])

onLoad((options) => {
    const id = Number(options?.id)
    if (id) {
        loadPage(id)
    } else {
        loading.value = false
    }
})

async function loadPage(id: number) {
    try {
        const res = await diyApi.getCustomPage(id)
        if (res && res.components) {
            pageComponents.value = res.components
        }
    } finally {
        loading.value = false
    }
}
</script>

<style lang="scss" scoped>
.diy-custom-page {
    min-height: 100vh;
    background: #f5f5f5;

    &__empty {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 300rpx;
        color: #999;
        font-size: 28rpx;
    }
}
</style>
