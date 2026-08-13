<template>
  <div class="component-panel">
    <div v-for="group in groupedDefs" :key="group.label" class="component-panel__group">
      <div class="component-panel__group-label">{{ group.label }}</div>
      <div class="component-panel__grid">
        <div
          v-for="def in group.items"
          :key="def.type"
          class="component-panel__item"
          draggable="true"
          @dragstart="onDragStart($event, def)"
          @click="addComponent(def)"
        >
          <i :class="def.icon" class="text-5xl" />
          <span>{{ def.label }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { componentDefs, useEditor } from '../useEditor'
import type { ComponentDef } from '../useEditor'

const { addComponent } = useEditor()

const groupedDefs = computed(() => {
    const groups: { label: string; items: ComponentDef[] }[] = []
    const map = new Map<string, ComponentDef[]>()
    for (const def of componentDefs) {
        if (!map.has(def.group)) map.set(def.group, [])
        map.get(def.group)!.push(def)
    }
    for (const [label, items] of map) {
        groups.push({ label, items })
    }
    return groups
})

function onDragStart(e: DragEvent, def: ComponentDef) {
    e.dataTransfer?.setData('diy-component-type', def.type)
}
</script>

<style lang="scss" scoped>
.component-panel {
    padding: 12px;

    &__group { margin-bottom: 16px; }

    &__group-label {
        font-size: 12px;
        color: #909399;
        margin-bottom: 8px;
        padding-left: 4px;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    &__grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    &__item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 12px 4px;
        background: #f5f7fa;
        border: none;
        border-radius: 8px;
        cursor: grab;
        font-size: 12px;
        color: #606266;
        transition: all 0.2s;

        &:hover {
            background: var(--brand-50);
            color: var(--el-color-primary);
        }

        &:active { cursor: grabbing; }
    }
}
</style>
