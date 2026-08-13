<template>
  <NModal
    :show="show"
    preset="card"
    title="选择自提门店"
    class="max-w-xl"
    @update:show="(v: boolean) => emit('update:show', v)"
  >
    <!-- Loading -->
    <div v-if="loading" class="space-y-3 py-2">
      <div v-for="i in 3" :key="i" class="h-20 bg-gray-100 rounded animate-pulse" />
    </div>

    <!-- Empty -->
    <div v-else-if="stores.length === 0" class="py-12 text-center text-gray-400">
      <span class="i-carbon-store text-4xl block mb-3 mx-auto" />
      <p class="text-sm">暂无可自提门店</p>
    </div>

    <!-- Store list -->
    <div v-else class="space-y-3 max-h-96 overflow-y-auto pr-1">
      <div
        v-for="store in stores"
        :key="store.id"
        class="store-card"
        :class="{ 'is-selected': selectedId === store.id }"
        @click="handleSelect(store)"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
              <span class="font-medium text-sm text-gray-800">{{ store.name }}</span>
              <span
                v-if="store.is_open_now !== undefined"
                class="text-xs px-1.5 py-0.5 rounded-sm leading-none"
                :class="store.is_open_now ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'"
              >
                {{ store.is_open_now ? '营业中' : '休息中' }}
              </span>
              <span v-if="store.distance != null" class="text-xs text-gray-400">
                距您约 {{ formatDistance(store.distance) }}
              </span>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed">
              <span class="i-carbon-location text-xs mr-0.5 align-middle" />
              {{ store.address }}
            </p>
            <p v-if="store.phone" class="text-xs text-gray-400 mt-1">
              <span class="i-carbon-phone text-xs mr-0.5 align-middle" />
              {{ store.phone }}
            </p>
          </div>
          <span
            v-if="selectedId === store.id"
            class="i-carbon-checkmark-filled text-[var(--color-primary)] text-lg flex-shrink-0 mt-0.5"
          />
        </div>
      </div>
    </div>

    <p v-if="!loading && !hasLocation" class="text-xs text-gray-400 mt-3">
      提示：未获取到您的位置，门店按默认排序展示
    </p>
  </NModal>
</template>

<script setup lang="ts">
import { NModal } from 'naive-ui'
import { storeApi, type Store } from '~/api/store'

const props = defineProps<{
  show: boolean
  /** 当前已选门店 id（回显选中态） */
  selectedId?: number | null
}>()

const emit = defineEmits<{
  'update:show': [value: boolean]
  select: [store: Store]
}>()

const loading = ref(false)
const loaded = ref(false)
const stores = ref<Store[]>([])
const hasLocation = ref(false)

function formatDistance(km: number): string {
  if (km < 1) return `${Math.round(km * 1000)}m`
  return `${km.toFixed(1)}km`
}

/** 尝试拿浏览器定位（3s 超时）；失败返回 null，不阻塞门店加载 */
function getPosition(): Promise<{ lng: number; lat: number } | null> {
  return new Promise((resolve) => {
    if (!import.meta.client || !navigator.geolocation) {
      resolve(null)
      return
    }
    navigator.geolocation.getCurrentPosition(
      pos => resolve({ lng: pos.coords.longitude, lat: pos.coords.latitude }),
      () => resolve(null),
      { timeout: 3000, maximumAge: 300000 },
    )
  })
}

async function loadStores() {
  loading.value = true
  try {
    const coords = await getPosition()
    hasLocation.value = !!coords
    const res = await storeApi.list(coords ? { lng: coords.lng, lat: coords.lat } : undefined)
    if (res.code === 200) {
      stores.value = res.data.list || []
      loaded.value = true
    }
  } finally {
    loading.value = false
  }
}

watch(() => props.show, (visible) => {
  if (visible && !loaded.value && !loading.value) {
    loadStores()
  }
})

function handleSelect(store: Store) {
  emit('select', store)
  emit('update:show', false)
}
</script>

<style scoped>
.store-card {
  padding: 12px 14px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.store-card:hover {
  border-color: var(--color-primary);
}

.store-card.is-selected {
  border-color: var(--color-primary);
  background-color: rgb(from var(--color-primary) r g b / 0.04);
}
</style>
