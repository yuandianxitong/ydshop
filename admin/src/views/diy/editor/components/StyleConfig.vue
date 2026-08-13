<template>
  <div class="config-panel">
    <!-- Background -->
    <div class="config-section">背景设置</div>
    <div class="config-row">
      <span class="config-label">背景类型</span>
      <div class="config-control">
        <el-radio-group v-model="form.background.type" @change="emitUpdate">
          <el-radio value="color">颜色</el-radio>
          <el-radio value="gradient">渐变</el-radio>
          <el-radio value="image">图片</el-radio>
        </el-radio-group>
      </div>
    </div>
    <template v-if="form.background.type === 'color'">
      <div class="config-row">
        <span class="config-label">背景颜色</span>
        <div class="config-control">
          <div class="config-color">
            <el-color-picker v-model="form.background.color" show-alpha @change="emitUpdate" />
            <el-input v-model="form.background.color" @change="emitUpdate" />
            <span class="config-color__reset" @click="form.background.color = 'transparent'; emitUpdate()">重置</span>
          </div>
        </div>
      </div>
    </template>
    <template v-else-if="form.background.type === 'gradient'">
      <div class="config-row">
        <span class="config-label">起始色</span>
        <div class="config-control">
          <div class="config-color">
            <el-color-picker v-model="form.background.gradientStart" @change="emitUpdate" />
            <el-input v-model="form.background.gradientStart" @change="emitUpdate" />
            <span class="config-color__reset" @click="form.background.gradientStart = '#ffffff'; emitUpdate()">重置</span>
          </div>
        </div>
      </div>
      <div class="config-row">
        <span class="config-label">结束色</span>
        <div class="config-control">
          <div class="config-color">
            <el-color-picker v-model="form.background.gradientEnd" @change="emitUpdate" />
            <el-input v-model="form.background.gradientEnd" @change="emitUpdate" />
            <span class="config-color__reset" @click="form.background.gradientEnd = '#000000'; emitUpdate()">重置</span>
          </div>
        </div>
      </div>
      <div class="config-row">
        <span class="config-label">渐变方向</span>
        <div class="config-control">
          <el-radio-group v-model="form.background.gradientDirection" @change="emitUpdate">
            <el-radio value="to right">横向</el-radio>
            <el-radio value="to bottom">纵向</el-radio>
            <el-radio value="to bottom right">左斜</el-radio>
            <el-radio value="to top right">右斜</el-radio>
          </el-radio-group>
        </div>
      </div>
    </template>
    <template v-else-if="form.background.type === 'image'">
      <div class="config-row config-row--top">
        <span class="config-label">背景图</span>
        <div class="config-control">
          <ImageSelect v-model="form.background.image" @update:model-value="emitUpdate" />
        </div>
      </div>
    </template>

    <!-- Border Radius -->
    <div class="config-section">圆角设置</div>
    <div class="config-row">
      <span class="config-label">背景圆角</span>
      <div class="config-control">
        <div class="style-slider-lock">
          <el-slider v-model="form.borderRadius.topLeft" :min="0" :max="100" @change="form.borderRadius.linked ? onLinkedRadiusChange() : emitUpdate()" />
          <el-input-number v-model="form.borderRadius.topLeft" :min="0" :max="100" :controls="false" @change="form.borderRadius.linked ? onLinkedRadiusChange() : emitUpdate()" />
          <span class="style-lock-btn" :class="{ 'style-lock-btn--active': form.borderRadius.linked }" @click="form.borderRadius.linked = !form.borderRadius.linked; onRadiusLinkedChange()">
            <i :class="form.borderRadius.linked ? 'i-ri-lock-line' : 'i-ri-lock-unlock-line'" />
          </span>
        </div>
      </div>
    </div>
    <div v-if="!form.borderRadius.linked" class="config-row config-row--top">
      <span class="config-label"></span>
      <div class="config-control">
        <div class="style-grid-4">
          <div class="style-grid-4__item">
            <i class="i-ri-corner-up-left-line style-grid-4__icon" />
            <el-input-number v-model="form.borderRadius.topLeft" :min="0" :max="100" :controls="false" @change="emitUpdate" />
          </div>
          <div class="style-grid-4__item">
            <i class="i-ri-corner-up-right-line style-grid-4__icon" />
            <el-input-number v-model="form.borderRadius.topRight" :min="0" :max="100" :controls="false" @change="emitUpdate" />
          </div>
          <div class="style-grid-4__item">
            <i class="i-ri-corner-down-left-line style-grid-4__icon" />
            <el-input-number v-model="form.borderRadius.bottomLeft" :min="0" :max="100" :controls="false" @change="emitUpdate" />
          </div>
          <div class="style-grid-4__item">
            <i class="i-ri-corner-down-right-line style-grid-4__icon" />
            <el-input-number v-model="form.borderRadius.bottomRight" :min="0" :max="100" :controls="false" @change="emitUpdate" />
          </div>
        </div>
      </div>
    </div>

    <!-- Margin -->
    <div class="config-section">外边距</div>
    <div class="config-row">
      <span class="config-label">外边距</span>
      <div class="config-control">
        <div class="style-slider-lock">
          <el-slider v-model="marginUnified" :min="0" :max="200" @change="onMarginUnifiedChange" />
          <el-input-number v-model="marginUnified" :min="0" :max="200" :controls="false" @change="onMarginUnifiedChange" />
          <span class="style-lock-btn" :class="{ 'style-lock-btn--active': marginLocked }" @click="marginLocked = !marginLocked; if(marginLocked) onMarginUnifiedChange()">
            <i :class="marginLocked ? 'i-ri-lock-line' : 'i-ri-lock-unlock-line'" />
          </span>
        </div>
      </div>
    </div>
    <div v-if="!marginLocked" class="config-row config-row--top">
      <span class="config-label"></span>
      <div class="config-control">
        <div class="style-grid-4">
          <div class="style-grid-4__item"><i class="i-ri-arrow-up-line style-grid-4__icon" /><el-input-number v-model="form.margin.top" :min="0" :max="200" :controls="false" @change="emitUpdate" /></div>
          <div class="style-grid-4__item"><i class="i-ri-arrow-right-line style-grid-4__icon" /><el-input-number v-model="form.margin.right" :min="0" :max="200" :controls="false" @change="emitUpdate" /></div>
          <div class="style-grid-4__item"><i class="i-ri-arrow-down-line style-grid-4__icon" /><el-input-number v-model="form.margin.bottom" :min="0" :max="200" :controls="false" @change="emitUpdate" /></div>
          <div class="style-grid-4__item"><i class="i-ri-arrow-left-line style-grid-4__icon" /><el-input-number v-model="form.margin.left" :min="0" :max="200" :controls="false" @change="emitUpdate" /></div>
        </div>
      </div>
    </div>

    <!-- Padding -->
    <div class="config-section">内边距</div>
    <div class="config-row">
      <span class="config-label">内边距</span>
      <div class="config-control">
        <div class="style-slider-lock">
          <el-slider v-model="paddingUnified" :min="0" :max="200" @change="onPaddingUnifiedChange" />
          <el-input-number v-model="paddingUnified" :min="0" :max="200" :controls="false" @change="onPaddingUnifiedChange" />
          <span class="style-lock-btn" :class="{ 'style-lock-btn--active': paddingLocked }" @click="paddingLocked = !paddingLocked; if(paddingLocked) onPaddingUnifiedChange()">
            <i :class="paddingLocked ? 'i-ri-lock-line' : 'i-ri-lock-unlock-line'" />
          </span>
        </div>
      </div>
    </div>
    <div v-if="!paddingLocked" class="config-row config-row--top">
      <span class="config-label"></span>
      <div class="config-control">
        <div class="style-grid-4">
          <div class="style-grid-4__item"><i class="i-ri-arrow-up-line style-grid-4__icon" /><el-input-number v-model="form.padding.top" :min="0" :max="200" :controls="false" @change="emitUpdate" /></div>
          <div class="style-grid-4__item"><i class="i-ri-arrow-right-line style-grid-4__icon" /><el-input-number v-model="form.padding.right" :min="0" :max="200" :controls="false" @change="emitUpdate" /></div>
          <div class="style-grid-4__item"><i class="i-ri-arrow-down-line style-grid-4__icon" /><el-input-number v-model="form.padding.bottom" :min="0" :max="200" :controls="false" @change="emitUpdate" /></div>
          <div class="style-grid-4__item"><i class="i-ri-arrow-left-line style-grid-4__icon" /><el-input-number v-model="form.padding.left" :min="0" :max="200" :controls="false" @change="emitUpdate" /></div>
        </div>
      </div>
    </div>

    <!-- Border -->
    <div class="config-section">边框设置</div>
    <div class="config-row">
      <span class="config-label">边框</span>
      <div class="config-control">
        <el-radio-group v-model="borderVisible" @change="onBorderVisibleChange">
          <el-radio :value="false">隐藏</el-radio>
          <el-radio :value="true">显示</el-radio>
        </el-radio-group>
      </div>
    </div>
    <template v-if="borderVisible">
      <div class="config-row">
        <span class="config-label">边框宽度</span>
        <div class="config-control">
          <div class="config-slider-combo">
            <el-slider v-model="form.border.width" :min="1" :max="20" @change="emitUpdate" />
            <el-input-number v-model="form.border.width" :min="1" :max="20" :controls="false" @change="emitUpdate" />
          </div>
        </div>
      </div>
      <div class="config-row">
        <span class="config-label">边框样式</span>
        <div class="config-control">
          <el-radio-group v-model="form.border.style" @change="emitUpdate">
            <el-radio value="solid">实线</el-radio>
            <el-radio value="dashed">虚线</el-radio>
            <el-radio value="dotted">点线</el-radio>
          </el-radio-group>
        </div>
      </div>
      <div class="config-row">
        <span class="config-label">边框颜色</span>
        <div class="config-control">
          <div class="config-color">
            <el-color-picker v-model="form.border.color" @change="emitUpdate" />
            <el-input v-model="form.border.color" @change="emitUpdate" />
            <span class="config-color__reset" @click="form.border.color = '#e0e0e0'; emitUpdate()">重置</span>
          </div>
        </div>
      </div>
    </template>

    <!-- Box Shadow -->
    <div class="config-section">阴影设置</div>
    <div class="config-row">
      <span class="config-label">阴影</span>
      <div class="config-control">
        <el-radio-group v-model="shadowVisible" @change="onShadowVisibleChange">
          <el-radio :value="false">隐藏</el-radio>
          <el-radio :value="true">显示</el-radio>
        </el-radio-group>
      </div>
    </div>
    <template v-if="shadowVisible">
      <div class="config-row">
        <span class="config-label">阴影颜色</span>
        <div class="config-control">
          <div class="config-color">
            <el-color-picker v-model="form.boxShadow.color" show-alpha @change="emitUpdate" />
            <el-input v-model="form.boxShadow.color" @change="emitUpdate" />
            <span class="config-color__reset" @click="form.boxShadow.color = 'rgba(0,0,0,0.1)'; emitUpdate()">重置</span>
          </div>
        </div>
      </div>
      <div class="config-row">
        <span class="config-label">X轴偏移</span>
        <div class="config-control">
          <div class="config-slider-combo"><el-slider v-model="form.boxShadow.x" :min="-50" :max="50" @change="emitUpdate" /><el-input-number v-model="form.boxShadow.x" :min="-50" :max="50" :controls="false" @change="emitUpdate" /></div>
        </div>
      </div>
      <div class="config-row">
        <span class="config-label">Y轴偏移</span>
        <div class="config-control">
          <div class="config-slider-combo"><el-slider v-model="form.boxShadow.y" :min="-50" :max="50" @change="emitUpdate" /><el-input-number v-model="form.boxShadow.y" :min="-50" :max="50" :controls="false" @change="emitUpdate" /></div>
        </div>
      </div>
      <div class="config-row">
        <span class="config-label">模糊半径</span>
        <div class="config-control">
          <div class="config-slider-combo"><el-slider v-model="form.boxShadow.blur" :min="0" :max="100" @change="emitUpdate" /><el-input-number v-model="form.boxShadow.blur" :min="0" :max="100" :controls="false" @change="emitUpdate" /></div>
        </div>
      </div>
    </template>

    <!-- Opacity -->
    <div class="config-section">透明度</div>
    <div class="config-row">
      <span class="config-label">透明度</span>
      <div class="config-control">
        <div class="config-slider-combo">
          <el-slider v-model="form.opacity" :min="0" :max="100" @change="emitUpdate" />
          <el-input-number v-model="form.opacity" :min="0" :max="100" :controls="false" @change="emitUpdate" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import ImageSelect from '@/components/ImageSelect/index.vue'
import { defaultComponentStyle, type ComponentStyle } from '../styleUtils'

const props = defineProps<{ modelValue: Partial<ComponentStyle> }>()
const emit = defineEmits(['update:modelValue'])
function mergeDefaults(val: Partial<ComponentStyle>): ComponentStyle {
    return {
        margin: { ...defaultComponentStyle.margin, ...val.margin },
        padding: { ...defaultComponentStyle.padding, ...val.padding },
        background: { ...defaultComponentStyle.background, ...val.background },
        borderRadius: { ...defaultComponentStyle.borderRadius, ...val.borderRadius },
        boxShadow: { ...defaultComponentStyle.boxShadow, ...val.boxShadow },
        border: { ...defaultComponentStyle.border, ...val.border },
        opacity: val.opacity ?? defaultComponentStyle.opacity,
    }
}

const form = ref<ComponentStyle>(mergeDefaults(props.modelValue || {}))
watch(() => props.modelValue, (val) => { form.value = mergeDefaults(val || {}) }, { deep: true })

function emitUpdate() { emit('update:modelValue', JSON.parse(JSON.stringify(form.value))) }

// Margin unified
const marginLocked = ref(true)
const marginUnified = ref(form.value.margin.top)
function onMarginUnifiedChange() {
    form.value.margin = { top: marginUnified.value, right: marginUnified.value, bottom: marginUnified.value, left: marginUnified.value }
    emitUpdate()
}

// Padding unified
const paddingLocked = ref(true)
const paddingUnified = ref(form.value.padding.top)
function onPaddingUnifiedChange() {
    form.value.padding = { top: paddingUnified.value, right: paddingUnified.value, bottom: paddingUnified.value, left: paddingUnified.value }
    emitUpdate()
}

// Border radius
function onLinkedRadiusChange() {
    const v = form.value.borderRadius.topLeft
    form.value.borderRadius.topRight = v
    form.value.borderRadius.bottomRight = v
    form.value.borderRadius.bottomLeft = v
    emitUpdate()
}
function onRadiusLinkedChange() {
    if (form.value.borderRadius.linked) onLinkedRadiusChange()
    else emitUpdate()
}

// Border visibility
const borderVisible = ref(form.value.border.width > 0)
function onBorderVisibleChange(val: string | number | boolean | undefined) {
    if (!val) { form.value.border.width = 0; form.value.border.style = 'none'; emitUpdate() }
    else { form.value.border.width = 1; form.value.border.style = 'solid'; emitUpdate() }
}

// Shadow visibility
const shadowVisible = ref(form.value.boxShadow.blur > 0 || form.value.boxShadow.x !== 0 || form.value.boxShadow.y !== 0)
function onShadowVisibleChange(val: string | number | boolean | undefined) {
    if (!val) { form.value.boxShadow = { x: 0, y: 0, blur: 0, color: 'rgba(0,0,0,0.1)' }; emitUpdate() }
    else { form.value.boxShadow.blur = 10; emitUpdate() }
}

</script>

<style scoped>
@import '../config-ui.scss';

.style-slider-lock {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}
.style-slider-lock .el-slider { flex: 1; }
.style-slider-lock .el-input-number { width: 64px; flex-shrink: 0; }

.style-lock-btn {
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    cursor: pointer;
    color: #c0c4cc;
    font-size: 16px;
    transition: all 0.2s;
}
.style-lock-btn:hover { color: var(--el-color-primary); background: var(--brand-50); }
.style-lock-btn--active { color: var(--el-color-primary); }

.style-grid-4 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.style-grid-4__item {
    display: flex;
    align-items: center;
    gap: 4px;
}
.style-grid-4__icon {
    font-size: 14px;
    color: #c0c4cc;
    flex-shrink: 0;
}
.style-grid-4__item :deep(.el-input-number) { width: 100%; }
.style-grid-4__item :deep(.el-input-number .el-input__inner) { text-align: left; }

.bg-preview { width: 100%; max-height: 80px; object-fit: cover; border-radius: 6px; }
</style>
