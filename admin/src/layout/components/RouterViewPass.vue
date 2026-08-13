<!--
  目录 / LAYOUT 路由的透传层。
  vue-router 的裸 RouterView 没有单 DOM 根节点，放进 <Transition mode="out-in">
  会导致 leave 卸空、中间内容变成 <!---->。这里固定包一层 div 供动画使用。
-->
<template>
    <div class="router-view-pass">
        <router-view />
    </div>
</template>

<script setup lang="ts">
defineOptions({ name: 'RouterViewPass' })
</script>

<style scoped>
.router-view-pass {
    min-height: 100%;
}
</style>
