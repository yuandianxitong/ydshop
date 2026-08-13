<template>
  <u-popup
    :show="visible"
    mode="bottom"
    :safe-area-inset-bottom="true"
    :custom-style="{ borderRadius: '24rpx 24rpx 0 0', maxHeight: '75vh', display: 'flex', flexDirection: 'column' }"
    @close="emit('update:visible', false)"
  >
    <view class="d-spec-selector">
      <!-- Header -->
      <view class="d-spec-selector__header">
        <view class="d-spec-selector__sku-image">
          <image
            v-if="previewImage"
            :src="previewImage"
            mode="aspectFill"
            class="d-spec-selector__img"
          />
          <view v-else class="d-spec-selector__img-placeholder" />
        </view>
        <view class="d-spec-selector__sku-info">
          <d-price :main="numericPrice" size="lg" />
          <text v-if="selectedSku" class="d-spec-selector__stock">库存 {{ selectedSku.stock }} 件</text>
          <text v-else class="d-spec-selector__hint">请选择规格</text>
        </view>
        <view class="d-spec-selector__close" @tap="emit('update:visible', false)">
          <d-icon name="close" size="36rpx" color="#71717a" />
        </view>
      </view>

      <!-- Body: spec chip groups（仅规格，不再含数量行） -->
      <scroll-view class="d-spec-selector__body" scroll-y>
        <view
          v-for="spec in specs"
          :key="spec.name"
          class="d-spec-selector__group"
        >
          <text class="d-spec-selector__group-name">{{ spec.name }}</text>
          <view class="d-spec-selector__chips">
            <view
              v-for="val in spec.values"
              :key="val.id"
              class="d-spec-selector__chip"
              :class="{
                'd-spec-selector__chip--selected': isSelected(spec.name, val.value),
                'd-spec-selector__chip--disabled': isDisabled(spec.name, val.value),
              }"
              @tap="onChipTap(spec.name, val.value)"
            >
              {{ val.value }}
            </view>
          </view>
        </view>
      </scroll-view>

      <!-- Quantity（移出 scroll-view，作为独立 fixed 行确保 mp 上 layout 稳定） -->
      <view class="d-spec-selector__qty-row">
        <text class="d-spec-selector__qty-label">数量</text>
        <view class="d-spec-selector__qty-stepper">
          <view
            class="d-spec-selector__qty-btn"
            :class="{ 'd-spec-selector__qty-btn--disabled': quantity <= 1 }"
            @tap="onQtyDec"
          >−</view>
          <input
            class="d-spec-selector__qty-input"
            type="number"
            :value="String(quantity)"
            :disabled="confirmDisabled"
            @blur="onQtyBlur"
          />
          <view
            class="d-spec-selector__qty-btn"
            :class="{ 'd-spec-selector__qty-btn--disabled': quantity >= (maxStock || 1) }"
            @tap="onQtyInc"
          >+</view>
        </view>
      </view>

      <!-- Footer button -->
      <view class="d-spec-selector__footer">
        <view
          class="d-spec-selector__btn"
          :class="{ 'd-spec-selector__btn--disabled': confirmDisabled }"
          @tap="onConfirm"
        >
          {{ confirmDisabled ? '暂无库存' : '确定' }}
        </view>
      </view>
    </view>
  </u-popup>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useAppStore } from '@/store/app.store'
import type { SkuItem } from '@/api/goods'

interface SpecGroup {
  name: string
  values: { id: number; value: string }[]
}

const props = defineProps<{
  visible: boolean
  specs: SpecGroup[]
  skus: SkuItem[]
  defaultImage?: string
  action?: 'cart' | 'buy'
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  confirm: [payload: { sku: SkuItem; quantity: number; action: 'cart' | 'buy' }]
}>()

const appStore = useAppStore()

// 已选 spec：{ specName: value }
const selected = ref<Record<string, string>>({})
const quantity = ref(1)

// 当前匹配的 SKU
const selectedSku = computed<SkuItem | null>(() => {
  if (Object.keys(selected.value).length !== props.specs.length) return null
  return (
    props.skus.find(sku =>
      Object.entries(selected.value).every(
        ([name, val]) => sku.spec_values?.[name] === val
      )
    ) ?? null
  )
})

const maxStock = computed(() => selectedSku.value?.stock ?? 0)
const confirmDisabled = computed(() => !selectedSku.value || maxStock.value === 0)

const numericPrice = computed(() => {
  if (selectedSku.value) return selectedSku.value.price
  // 未选时显示价格区间最低
  if (!props.skus?.length) return 0
  const inStock = props.skus.filter(s => s.stock > 0)
  if (!inStock.length) return Math.min(...props.skus.map(s => s.price))
  return Math.min(...inStock.map(s => s.price))
})

const previewImage = computed(() => {
  const img = selectedSku.value?.image || props.defaultImage || ''
  return img ? appStore.getImageUrl(img) : ''
})

function isSelected(name: string, value: string) {
  return selected.value[name] === value
}

// 已选其它 spec 的情况下，该 value 是否会导致无库存 SKU
function isDisabled(name: string, value: string) {
  const trial = { ...selected.value, [name]: value }
  return !props.skus.some(sku =>
    Object.entries(trial).every(([n, v]) => sku.spec_values?.[n] === v) &&
    sku.stock > 0
  )
}

function onChipTap(name: string, value: string) {
  if (isDisabled(name, value) && !isSelected(name, value)) return
  if (isSelected(name, value)) {
    delete selected.value[name]
    selected.value = { ...selected.value }
  } else {
    selected.value = { ...selected.value, [name]: value }
  }
}

function onConfirm() {
  if (confirmDisabled.value || !selectedSku.value) return
  emit('confirm', {
    sku: selectedSku.value,
    quantity: quantity.value,
    action: props.action ?? 'cart',
  })
  emit('update:visible', false)
}

// 数量加减（inline 实现，逻辑参考 d-amount-stepper）
function clampQty(v: number): number {
  if (Number.isNaN(v)) return 1
  return Math.min(maxStock.value || 1, Math.max(1, v))
}
function onQtyDec() {
  if (quantity.value <= 1) return
  quantity.value = clampQty(quantity.value - 1)
}
function onQtyInc() {
  if (quantity.value >= (maxStock.value || 1)) return
  quantity.value = clampQty(quantity.value + 1)
}
function onQtyBlur(e: any) {
  const raw = Number(e?.detail?.value ?? e?.target?.value ?? quantity.value)
  quantity.value = clampQty(raw)
}

// 弹层重新打开时重置数量 + 默认选中第一个有库存 SKU 的规格组合
watch(
  () => props.visible,
  (v) => {
    if (!v) return
    quantity.value = 1
    // 优先选第一个有库存的 SKU；若全无库存，选第一个 SKU
    const firstInStock = props.skus.find(s => s.stock > 0) ?? props.skus[0]
    if (firstInStock?.spec_values && Object.keys(firstInStock.spec_values).length) {
      selected.value = { ...firstInStock.spec_values }
    } else {
      // SKU 没有 spec_values 字段时（极旧 API），按 specs 第一项默认
      const fallback: Record<string, string> = {}
      props.specs.forEach(spec => {
        if (spec.values?.[0]) fallback[spec.name] = spec.values[0].value
      })
      selected.value = fallback
    }
  }
)
</script>

<style lang="scss" scoped>

.d-spec-selector {
  display: flex;
  flex-direction: column;
  max-height: 75vh;

  &__header {
    display: flex;
    align-items: flex-start;
    padding: var(--space-4);
    border-bottom: 1rpx solid var(--color-border-2);
  }
  &__sku-image {
    width: 140rpx;
    height: 140rpx;
    margin-right: var(--space-3);
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--color-bg-3);
    flex-shrink: 0;
  }
  &__img { width: 100%; height: 100%; }
  &__img-placeholder { width: 100%; height: 100%; background: var(--color-bg-3); }

  &__sku-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: var(--space-1);
    min-height: 120rpx;
  }
  &__stock {
    font-size: var(--font-sm);
    color: var(--color-text-2);
  }
  &__hint {
    font-size: var(--font-sm);
    color: var(--color-text-3);
  }
  &__close {
    width: 64rpx;
    height: 64rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-3);
    font-size: 36rpx;
  }

  &__body {
    flex: 1;
    padding: 0 var(--space-4);
  }
  &__group {
    padding: var(--space-3) 0;
    & + & { border-top: 1rpx solid var(--color-border-2); }
  }
  &__group-name {
    display: block;
    font-size: var(--font-sm);
    color: var(--color-text-2);
    margin-bottom: var(--space-2);
  }
  &__chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-1);
  }
  &__chip {
    padding: 12rpx 24rpx;
    border: 1rpx solid var(--color-border-1);
    border-radius: var(--radius-md);
    font-size: var(--font-sm);
    color: var(--color-text-1);
    background: var(--color-bg-1);

    &--selected {
      background: var(--color-primary);
      border-color: var(--color-primary);
      color: #fff;
    }
    &--disabled {
      opacity: 0.4;
      color: var(--color-text-3);
    }
  }

  // 数量行：移出 scroll-view，独立 fixed 行 + 显式 width
  &__qty-row {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: var(--space-3) var(--space-4);
    border-top: 1rpx solid var(--color-border-2);
    box-sizing: border-box;
  }
  &__qty-label {
    flex-shrink: 0;
    font-size: var(--font-base);
    color: var(--color-text-1);
  }
  &__qty-stepper {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    flex-shrink: 0;
    width: 180rpx;
    height: 56rpx;
    border-radius: var(--radius-sm);
    overflow: hidden;
    background: var(--color-bg-3);
    box-sizing: border-box;
  }
  &__qty-btn {
    width: 50rpx;
    min-width: 50rpx;
    max-width: 50rpx;
    height: 56rpx;
    line-height: 56rpx;
    text-align: center;
    font-size: 32rpx;
    color: var(--color-text-1);
    flex-shrink: 0;
    flex-grow: 0;
    box-sizing: border-box;

    &:active { background: var(--color-border-1); }
    &--disabled { color: var(--color-text-3); &:active { background: var(--color-bg-3); } }
  }
  &__qty-input {
    width: 80rpx;
    min-width: 80rpx;
    max-width: 80rpx;
    height: 56rpx;
    line-height: 56rpx;
    text-align: center;
    background: var(--color-bg-1);
    font-size: var(--font-base);
    color: var(--color-text-1);
    flex-shrink: 0;
    flex-grow: 0;
    box-sizing: border-box;
    padding: 0;
    margin: 0;
    border: none;
  }

  &__footer {
    padding: var(--space-3) var(--space-4);
    padding-bottom: calc(var(--space-3) + env(safe-area-inset-bottom));
    border-top: 1rpx solid var(--color-border-2);
  }
  &__btn {
    height: 88rpx;
    line-height: 88rpx;
    text-align: center;
    background: var(--color-primary);
    color: #fff;
    font-size: var(--font-md);
    font-weight: 600;
    border-radius: var(--radius-lg);

    &--disabled {
      background: var(--color-bg-3);
      color: var(--color-text-3);
    }
  }
}
</style>
