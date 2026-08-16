<template>
  <div class="spec-selector">
    <template v-if="!hasSpecs">
      <div class="spec-selector__price">
        <span class="text-2xl font-bold text-[var(--color-primary)]">
          ¥{{ formatPrice(singleSku?.price) }}
        </span>
        <span
          v-if="singleSku && skuOriginalPrice(singleSku) > Number(singleSku.price)"
          class="ml-2 text-sm text-gray-400 line-through"
        >
          ¥{{ formatPrice(skuOriginalPrice(singleSku)) }}
        </span>
      </div>
      <div class="spec-selector__stock mt-1 text-sm text-gray-400">
        库存：{{ singleSku?.stock ?? 0 }}件
      </div>
    </template>

    <template v-else>
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
                ¥{{ formatPrice(selectedSku.price) }}
                <span
                  v-if="skuOriginalPrice(selectedSku) > Number(selectedSku.price)"
                  class="ml-1 text-sm text-gray-400 line-through font-normal"
                >
                  ¥{{ formatPrice(skuOriginalPrice(selectedSku)) }}
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

    <div class="spec-selector__quantity mt-4 flex items-center gap-3">
      <span class="text-sm text-gray-600">数量</span>
      <div class="qty-stepper">
        <button
          type="button"
          class="qty-btn"
          :disabled="quantity <= 1 || maxStock === 0"
          @click="changeQty(-1)"
        >
          −
        </button>
        <input
          type="number"
          class="qty-input"
          :value="quantity"
          min="1"
          :max="maxStock || 1"
          :disabled="maxStock === 0"
          @change="onQtyInput"
        />
        <button
          type="button"
          class="qty-btn"
          :disabled="quantity >= maxStock || maxStock === 0"
          @click="changeQty(1)"
        >
          +
        </button>
      </div>
      <span v-if="maxStock === 0" class="text-xs text-red-400">暂无库存</span>
    </div>

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
import { NButton } from 'naive-ui'

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
  name?: string
  spec_text?: string
  price: number
  original_price?: number
  market_price?: number
  stock: number
  image: string
  spec_values?: Record<string, string>
  attributes?: Record<string, string>
}

const props = defineProps<{
  specs: SpecGroup[]
  skus: SkuItem[]
}>()

const emit = defineEmits<{
  confirm: [payload: { sku: SkuItem; quantity: number }]
  change: [payload: { sku: SkuItem | null; quantity: number }]
}>()

const selectedMap = ref<Record<string, number>>({})
const quantity = ref(1)

const hasSpecs = computed(() => props.specs && props.specs.length > 0)
const singleSku = computed(() => (!hasSpecs.value && props.skus.length > 0) ? props.skus[0] : null)

function skuSpecMap(sku: SkuItem | null | undefined): Record<string, string> {
  if (!sku) return {}
  return sku.spec_values || sku.attributes || {}
}

function skuOriginalPrice(sku: SkuItem): number {
  return Number(sku.original_price ?? sku.market_price ?? 0)
}

function formatPrice(val: string | number | undefined | null): string {
  const n = Number(val)
  return Number.isFinite(n) ? n.toFixed(2) : '0.00'
}

function skuMatches(sku: SkuItem, trial: Record<string, number>, requireAll: boolean): boolean {
  return props.specs.every((spec) => {
    const trialId = trial[spec.name]
    if (trialId == null) return !requireAll
    const trialVal = spec.values.find(v => v.id === trialId)
    if (!trialVal) return false
    return skuSpecMap(sku)[spec.name] === trialVal.value
  })
}

const selectedSku = computed<SkuItem | null>(() => {
  if (!hasSpecs.value) return singleSku.value
  if (props.specs.some(s => selectedMap.value[s.name] == null)) return null
  return props.skus.find(sku => skuMatches(sku, selectedMap.value, true)) ?? null
})

const priceRange = computed(() => {
  if (!props.skus.length) return '¥0.00'
  const prices = props.skus.filter(s => s.stock > 0).map(s => Number(s.price))
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

function isSelected(specName: string, valId: number): boolean {
  return selectedMap.value[specName] === valId
}

function isDisabled(specName: string, valId: number): boolean {
  const specVal = props.specs.find(s => s.name === specName)?.values.find(v => v.id === valId)
  if (!specVal) return true
  const trial: Record<string, number> = { ...selectedMap.value, [specName]: valId }
  return !props.skus.some(sku => sku.stock > 0 && skuMatches(sku, trial, false))
}

function selectValue(specName: string, valId: number) {
  if (isDisabled(specName, valId)) return
  if (selectedMap.value[specName] === valId) {
    const updated = { ...selectedMap.value }
    delete updated[specName]
    selectedMap.value = updated
  } else {
    selectedMap.value = { ...selectedMap.value, [specName]: valId }
  }
  quantity.value = 1
}

function clampQty(val: number): number {
  if (!Number.isFinite(val)) return 1
  const max = maxStock.value || 1
  return Math.min(max, Math.max(1, Math.floor(val)))
}

function changeQty(delta: number) {
  if (maxStock.value === 0) return
  quantity.value = clampQty(quantity.value + delta)
}

function onQtyInput(e: Event) {
  const input = e.target as HTMLInputElement
  const next = clampQty(Number(input.value))
  quantity.value = next
  input.value = String(next)
}

function handleConfirm() {
  const sku = hasSpecs.value ? selectedSku.value : singleSku.value
  if (!sku) return
  emit('confirm', { sku, quantity: quantity.value })
}

function applyDefaultSelection() {
  if (!hasSpecs.value || Object.keys(selectedMap.value).length) return
  const firstInStock = props.skus.find(s => s.stock > 0) ?? props.skus[0]
  const values = skuSpecMap(firstInStock)
  const next: Record<string, number> = {}
  if (firstInStock && Object.keys(values).length) {
    props.specs.forEach((spec) => {
      const found = spec.values.find(v => v.value === values[spec.name])
      if (found) next[spec.name] = found.id
    })
  }
  if (!Object.keys(next).length) {
    props.specs.forEach((spec) => {
      if (spec.values?.[0]) next[spec.name] = spec.values[0].id
    })
  }
  selectedMap.value = next
}

watch(() => [props.specs, props.skus], applyDefaultSelection, { immediate: true, deep: true })

watch([selectedSku, quantity], () => {
  emit('change', { sku: selectedSku.value, quantity: quantity.value })
}, { immediate: true })

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

.qty-stepper {
  display: inline-flex;
  align-items: stretch;
  border: 1px solid #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
  background: #fff;
}
.qty-btn {
  width: 28px;
  height: 28px;
  background: #fafafa;
  color: #6b7280;
  font-size: 1rem;
  line-height: 1;
  cursor: pointer;
  border: 0;
  transition: background-color 0.15s;
}
.qty-btn:hover:not(:disabled) {
  background: #f3f4f6;
}
.qty-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
.qty-input {
  width: 48px;
  height: 28px;
  border: 0;
  border-left: 1px solid #e5e7eb;
  border-right: 1px solid #e5e7eb;
  text-align: center;
  font-size: 0.8125rem;
  outline: none;
  -moz-appearance: textfield;
}
.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
