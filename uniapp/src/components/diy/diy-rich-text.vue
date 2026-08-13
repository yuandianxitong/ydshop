<template>
  <view v-if="content" class="diy-rich-text">
    <rich-text :nodes="content" />
  </view>
</template>

<script setup lang="ts">
defineProps<{
  content?: string
}>()
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.diy-rich-text {
  padding: 24rpx;
  font-size: 28rpx;
  line-height: 1.6;
  color: $text-color;
}
</style>
