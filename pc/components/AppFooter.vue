<template>
  <footer class="bg-white border-t border-gray-200 mt-10">
    <!-- Links -->
    <div class="mx-auto max-w-1200px px-4 py-8">
      <div
        class="grid gap-8"
        :style="{ gridTemplateColumns: `repeat(${footer.columns.length || 1}, 1fr)` }"
      >
        <div v-for="(col, ci) in footer.columns" :key="ci">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">{{ col.title }}</h4>
          <ul class="flex flex-col gap-2">
            <li v-for="(l, li) in col.links" :key="li">
              <a
                v-if="isExternal(l.path)"
                :href="l.path"
                target="_blank"
                rel="noopener"
                class="text-sm text-gray-500 hover:text-[var(--color-primary)]"
              >{{ l.label }}</a>
              <NuxtLink
                v-else-if="l.path"
                :to="l.path"
                class="text-sm text-gray-500 hover:text-[var(--color-primary)]"
              >{{ l.label }}</NuxtLink>
              <span v-else class="text-sm text-gray-500">{{ l.label }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!-- Copyright -->
    <div class="border-t border-gray-100">
      <div class="mx-auto max-w-1200px px-4 py-4 text-center text-xs text-gray-400">
        {{ copyrightText }}
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { useFooterConfig } from '~/composables/usePcMenu'

const footer = useFooterConfig()

const year = new Date().getFullYear()
const copyrightText = computed(() => (footer.value.copyright || '').replace('{YEAR}', String(year)))

function isExternal(p: string) {
  return /^https?:\/\//.test(p)
}
</script>
