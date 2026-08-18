<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

onMounted(() => {
    const state = route.query.state as string
    const code = route.query.code as string
    const denied = route.query.decision === 'deny' || !code
    if (window.opener) {
        window.opener.postMessage(
            { type: 'marketplace-oauth', state, code, denied },
            window.location.origin
        )
    }
    setTimeout(() => window.close(), 300)
})
</script>

<template>
    <div style="padding: 40px; text-align: center">
        <p>授权处理中，此窗口将自动关闭…</p>
    </div>
</template>
