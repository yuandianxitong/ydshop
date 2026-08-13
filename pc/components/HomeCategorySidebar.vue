<template>
  <!-- 浮在 banner 左侧的分类面板（半透明黑底）。容器需 position:relative 以让本组件 absolute 定位 -->
  <aside class="home-cat-sidebar">
    <ul>
      <li
        v-for="cat in topCategories"
        :key="cat.id"
        class="home-cat-sidebar__item"
        @mouseenter="hoverId = cat.id"
        @mouseleave="hoverId = null"
        @click="goCategory(cat.id)"
      >
        <span class="truncate">{{ cat.name }}</span>
        <span class="i-carbon-chevron-right opacity-70" />

        <!-- 二级分类飞出面板 -->
        <div
          v-if="hoverId === cat.id && cat.children && cat.children.length"
          class="home-cat-sidebar__flyout"
        >
          <NuxtLink
            v-for="sub in cat.children"
            :key="sub.id"
            :to="`/goods?category_id=${sub.id}`"
            class="home-cat-sidebar__flyout-item"
            @click.stop
          >
            {{ sub.name }}
          </NuxtLink>
        </div>
      </li>

      <li v-if="!topCategories.length" class="home-cat-sidebar__empty">暂无分类</li>
    </ul>
  </aside>
</template>

<script setup lang="ts">
import { goodsApi, type CategoryItem } from '~/api/goods'

const router = useRouter()
const categories = ref<CategoryItem[]>([])
const hoverId = ref<number | null>(null)
// 顶层分类截断到合理数量，避免溢出 banner 高度
const topCategories = computed(() => categories.value.slice(0, 8))

async function fetchCategories() {
  const res = await goodsApi.getCategoryTree()
  if (res.code === 200) {
    categories.value = res.data || []
  }
}

function goCategory(id: number) {
  router.push({ path: '/goods', query: { category_id: id } })
}

onMounted(fetchCategories)
</script>

<style scoped>
.home-cat-sidebar {
  position: absolute;
  top: 0;
  left: 0;        /* 父级 .diy-page 已经有 px-4 padding，这里直接 left:0 即与上方"全部商品分类"砖位左缘对齐 */
  width: 210px;   /* 与 AppNavMenu .all-cats-tile 的 min-width 严格一致 */
  height: 100%;
  z-index: 10;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(3px);
  color: #fff;
  padding: 8px 0;
  overflow: visible;
}
.home-cat-sidebar ul { list-style: none; padding: 0; margin: 0; }
.home-cat-sidebar__item {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 18px;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.home-cat-sidebar__item:hover {
  background: var(--color-primary);
  color: #fff;
}
.home-cat-sidebar__empty {
  padding: 24px 18px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  text-align: center;
}

/* 二级分类飞出面板：从一级条目右侧弹出 */
.home-cat-sidebar__flyout {
  position: absolute;
  top: 0;
  left: 100%;
  margin-left: 4px;
  min-width: 220px;
  background: #fff;
  color: #333;
  border-radius: 4px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  padding: 8px 0;
  z-index: 20;
}
.home-cat-sidebar__flyout-item {
  display: block;
  padding: 8px 16px;
  font-size: 13px;
  color: #444;
  transition: background 0.15s, color 0.15s;
}
.home-cat-sidebar__flyout-item:hover {
  background: rgba(0, 0, 0, 0.04);
  color: var(--color-primary);
}
</style>
