<template>
  <div class="diy-custom-page mx-auto max-w-1200px py-6">
    <DiyRenderer v-if="pageComponents.length" :components="pageComponents" />
    <div v-else-if="!loading" class="text-center py-20 text-gray-400">
      页面不存在或未发布
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import DiyRenderer from '~/components/diy/DiyRenderer.vue'
import { diyApi } from '~/api/diy'

const route = useRoute()
const loading = ref(true)
const pageComponents = ref<any[]>([])

async function loadPage() {
    const id = Number(route.params.id)
    if (!id) { loading.value = false; return }
    try {
        const res = await diyApi.getCustomPage(id)
        if (res.code === 200 && res.data?.components) {
            pageComponents.value = res.data.components
        }
    } finally {
        loading.value = false
    }
}

onMounted(() => loadPage())
</script>
