<template>
  <section v-if="goodsList.length" class="diy-seckill">
    <header>
      <div><strong>限时秒杀</strong><span v-if="sessionLabel" class="tag">{{ sessionLabel }}</span><span class="timer">{{ hh }}:{{ mm }}:{{ ss }}</span></div>
      <NuxtLink to="/marketing/flash-sale">查看更多 →</NuxtLink>
    </header>
    <div class="list">
      <NuxtLink v-for="item in goodsList" :key="item.item_id" :to="checkoutUrl(item)" class="item">
        <img :src="item.cover || '/placeholder.png'" :alt="item.name" />
        <p>{{ item.name }}</p>
        <div><b>¥{{ price(item.flash_price) }}</b><del>¥{{ price(item.original_price) }}</del></div>
      </NuxtLink>
    </div>
  </section>
</template>

<script setup lang="ts">
interface SeckillGoods { item_id: number; sku_id: number; spu_id: number; name: string; cover: string; flash_price: number; original_price: number }
interface SeckillData { session_label: string; end_at: string; goods: SeckillGoods[] }
const props = defineProps<{ activity_id?: number; limit?: number; seckill_data?: SeckillData | null }>()
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | null = null
const goodsList = computed(() => (props.seckill_data?.goods ?? []).slice(0, props.limit ?? 4))
const sessionLabel = computed(() => props.seckill_data?.session_label ?? '')
const remain = computed(() => Math.max(0, Math.floor((new Date((props.seckill_data?.end_at || '').replace(' ', 'T')).getTime() - now.value) / 1000)) || 0)
const pad = (value: number) => String(value).padStart(2, '0')
const hh = computed(() => pad(Math.floor(remain.value / 3600)))
const mm = computed(() => pad(Math.floor(remain.value % 3600 / 60)))
const ss = computed(() => pad(remain.value % 60))
const price = (value: number) => Number(value || 0).toFixed(2)
const checkoutUrl = (item: SeckillGoods) => `/checkout?goods_id=${item.spu_id}&sku_id=${item.sku_id}&quantity=1&flash_item_id=${item.item_id}`
onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 1000) })
onUnmounted(() => { if (timer) clearInterval(timer) })
</script>

<style scoped>
.diy-seckill { margin: 16px 0; padding: 18px; border-radius: 10px; background: #fff; }
header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
header strong { font-size: 20px; } header a { font-size: 13px; color: #666; }
.tag { margin-left: 10px; padding: 3px 7px; border-radius: 4px; color: #fff; background: #ef4444; font-size: 12px; }
.timer { margin-left: 8px; padding: 3px 7px; border-radius: 4px; color: #fff; background: #222; font-variant-numeric: tabular-nums; }
.list { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 14px; }
.item { color: inherit; min-width: 0; } .item img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 7px; background: #f5f5f5; }
.item p { margin: 7px 0 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 14px; }
.item b { color: #ef4444; } .item del { margin-left: 7px; color: #aaa; font-size: 12px; }
</style>
