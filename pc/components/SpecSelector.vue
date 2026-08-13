<template>
  <div class="spec-selector">
    <!-- No SKUs: single product with no variants -->
    <template v-if="!hasSpecs">
      <div class="spec-selector__price">
        <span class="text-2xl font-bold text-[var(--color-primary)]">
          ¥{{ singleSku ? singleSku.price.toFixed(2) : '0.00' }}
        </span>
        <span
          v-if="singleSku && singleSku.original_price > singleSku.price"
          class="ml-2 text-sm text-gray-400 line-through"
        >
          ¥{{ singleSku.original_price.toFixed(2) }}
        </span>
      </div>
      <div class="spec-selector__stock mt-1 text-sm text-gray-400">
        库存：{{ singleSku?.stock ?? 0 }}件
      </div>
    </template>

    <!-- Has specs -->
    <template v-else>
      <!-- Selected SKU info bar -->
      <div class="spec-selector__info mb-4">
        <template v-if="selectedSku">
          <div class="flex items-center gap-3">
            <img
              v-if="selectedSku.image"
              :src="selectedSku.image"
              class="w-14 h-14 object-cover rounded border border-gray-100"
              alt=""
            />
            <div>
              <div class="text-xl font-bold text-[var(--color-primary)]">
                ¥{{ selectedSku.price.toFixed(2) }}
                <span
                  v-if="selectedSku.original_price > selectedSku.price"
                  class="ml-1 text-sm text-gray-400 line-through font-normal"
                >
                  ¥{{ selectedSku.original_price.toFixed(2) }}
                </span>
              </div>
              <div class="text-xs text-gray-400 mt-0.5">库存：{{ selectedSku.stock }}件</div>
            </div>
          </div>
        </template>
        <template v-else>
          <div class="text-lg font-bold text-[var(--color-primary)]">{{ priceRange }}</div>
          <div class="text-xs text-gray-400 mt-0.5">请选择规格</div>
        </template>
      </div>

      <!-- Spec groups -->
      <div
        v-for="spec in specs"
        :key="spec.name"
        class="spec-selector__group mb-4"
      >
        <div class="text-sm font-medium text-gray-600 mb-2">{{ spec.name }}</div>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="val in spec.values"
            :key="val.id"
            class="spec-btn"
            :class="{
              'is-selected': isSelected(spec.name, val.id),
              'is-disabled': isDisabled(spec.name, val.id),
            }"
            :disabled="isDisabled(spec.name, val.id)"
            @click="selectValue(spec.name, val.id)"
          >
            {{ val.value }}
          </button>
        </div>
      </div>
    </template>

    <!-- Quantity -->
    <div class="spec-selector__quantity mt-4 flex items-center gap-3">
      <span class="text-sm text-gray-600">数量</span>
      <NInputNumber
        v-model:value="quantity"
        :min="1"
        :max="maxStock"
        :disabled="maxStock === 0"
        size="small"
        class="w-32"
      />
      <span v-if="maxStock === 0" class="text-xs text-red-400">暂无库存</span>
    </div>

    <!-- Actions -->
    <div class="spec-selector__actions mt-5 flex gap-3">
      <slot name="actions" :disabled="confirmDisabled" :on-confirm="handleConfirm">
        <NButton
          type="primary"
          :disabled="confirmDisabled"
          class="flex-1"
          @click="handleConfirm"
        >
          确认
        </NButton>
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { NInputNumber, NButton } from 'naive-ui'

export interface SpecValue {
  id: number
  value: string
}

export interface SpecGroup {
  name: string
  values: SpecValue[]
}

export interface SkuItem {
  id: number
  spu_id: number
  name: string
  price: number
  original_price: number
  stock: number
  image: string
  /** key: spec name, value: spec value string */
  attributes: Record<string, string>
}

const props = defineProps<{
  specs: SpecGroup[]
  skus: SkuItem[]
}>()

const emit = defineEmits<{
  confirm: [payload: { sku: SkuItem; quantity: number }]
}>()

// -------- state --------
/** map: specName -> selected value id */
const selectedMap = ref<Record<string, number>>({})
const quantity = ref(1)

// -------- computed --------
const hasSpecs = computed(() => props.specs && props.specs.length > 0)
const singleSku = computed(() => (!hasSpecs.value && props.skus.length > 0) ? props.skus[0] : null)

const selectedSku = computed<SkuItem | null>(() => {
  if (!hasSpecs.value) return singleSku.value
  // Check all spec groups have a selection
  if (props.specs.some(s => selectedMap.value[s.name] == null)) return null

  // Find SKU whose attributes match every selected value
  return props.skus.find(sku => {
    return props.specs.every(spec => {
      const selectedValId = selectedMap.value[spec.name]
      const selectedVal = spec.values.find(v => v.id === selectedValId)
      if (!selectedVal) return false
      return sku.attributes[spec.name] === selectedVal.value
    })
  }) ?? null
})

const priceRange = computed(() => {
  if (!props.skus.length) return '¥0.00'
  const prices = props.skus.filter(s => s.stock > 0).map(s => s.price)
  if (!prices.length) return '暂无库存'
  const min = Math.min(...prices)
  const max = Math.max(...prices)
  if (min === max) return `¥${min.toFixed(2)}`
  return `¥${min.toFixed(2)} ~ ¥${max.toFixed(2)}`
})

const maxStock = computed(() => {
  if (!hasSpecs.value) return singleSku.value?.stock ?? 0
  return selectedSku.value?.stock ?? 0
})

const confirmDisabled = computed(() => {
  if (hasSpecs.value && !selectedSku.value) return true
  return maxStock.value === 0
})

// -------- helpers --------
function isSelected(specName: string, valId: number): boolean {
  return selectedMap.value[specName] === valId
}

/**
 * A spec value is disabled when selecting it would yield no valid (in-stock) SKU,
 * considering the currently selected values of other specs.
 */
function isDisabled(specName: string, valId: number): boolean {
  const specVal = props.specs.find(s => s.name === specName)?.values.find(v => v.id === valId)
  if (!specVal) return true

  // Build a trial selection: current selections + this value
  const trial: Record<string, number> = { ...selectedMap.value, [specName]: valId }

  return !props.skus.some(sku => {
    if (sku.stock <= 0) return false
    return props.specs.every(spec => {
      const trialId = trial[spec.name]
      if (trialId == null) return true // not yet selected, skip constraint
      const trialVal = spec.values.find(v => v.id === trialId)
      if (!trialVal) return false
      return sku.attributes[spec.name] === trialVal.value
    })
  })
}

function selectValue(specName: string, valId: number) {
  if (isDisabled(specName, valId)) return
  if (selectedMap.value[specName] === valId) {
    // toggle off
    const updated = { ...selectedMap.value }
    delete updated[specName]
    selectedMap.value = updated
  } else {
    selectedMap.value = { ...selectedMap.value, [specName]: valId }
  }
  // Reset quantity to 1 when selection changes
  quantity.value = 1
}

function handleConfirm() {
  const sku = hasSpecs.value ? selectedSku.value : singleSku.value
  if (!sku) return
  emit('confirm', { sku, quantity: quantity.value })
}

// Watch maxStock to clamp quantity
watch(maxStock, (val) => {
  if (val > 0 && quantity.value > val) quantity.value = val
  if (val === 0) quantity.value = 1
})
</script>

<style scoped>
.spec-btn {
  padding: 5px 14px;
  border: 1.5px solid #e0e0e0;
  border-radius: 4px;
  background: #fff;
  font-size: 13px;
  color: #333;
  cursor: pointer;
  transition: all 0.15s;
  line-height: 1.6;
}
.spec-btn:hover:not(:disabled) {
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.spec-btn.is-selected {
  border-color: var(--color-primary);
  color: var(--color-primary);
  background: #e8f3ff;
  font-weight: 500;
}
.spec-btn.is-disabled {
  border-color: #e8e8e8;
  color: #ccc;
  background: #fafafa;
  cursor: not-allowed;
  position: relative;
}
.spec-btn.is-disabled::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #ccc;
  transform: rotate(-10deg);
}
</style>
