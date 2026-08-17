<template>
    <!--
      勿用 class="disabled"：UnoCSS 捷径 disabled = pointer-events-none，
      会导致禁用态卡片上的更多菜单完全无法点击。
    -->
    <div
        class="ap-card"
        :class="{ 'is-plugin-disabled': app.status === 'disabled' }"
        @click="onCardClick"
    >
        <div v-if="app.recommended || app.has_upgrade || app.status === 'disabled'" class="ap-badges">
            <span v-if="app.recommended" class="ap-rec">推荐</span>
            <span v-if="app.status === 'disabled'" class="ap-disabled-badge">已禁用</span>
            <span v-if="app.has_upgrade" class="ap-upgrade">v{{ app.disk_version }} 可升级</span>
        </div>

        <div class="ap-card-top">
            <div class="ap-glyph" :style="app.icon_url ? undefined : glyphStyle">
                <img v-if="app.icon_url" :src="app.icon_url" :alt="app.name" @error="onIconError" />
                <span v-else>{{ glyphLabel }}</span>
            </div>
            <div class="ap-info">
                <div class="ap-name">{{ app.name }}</div>
                <div class="ap-desc">{{ app.description || '—' }}</div>
            </div>
            <el-dropdown class="ap-more-wrap" trigger="click" @command="onCommand" @click.stop>
                <button type="button" class="ap-more" @click.stop>
                    <el-icon><MoreFilled /></el-icon>
                </button>
                <template #dropdown>
                    <el-dropdown-menu>
                        <el-dropdown-item v-if="app.has_upgrade" command="upgrade">升级</el-dropdown-item>
                        <el-dropdown-item :command="app.status === 'installed' ? 'disable' : 'enable'">
                            {{ app.status === 'installed' ? '禁用' : '启用' }}
                        </el-dropdown-item>
                        <el-dropdown-item command="uninstall" divided>卸载（保留数据）</el-dropdown-item>
                        <el-dropdown-item command="uninstall-purge">卸载并清除数据</el-dropdown-item>
                    </el-dropdown-menu>
                </template>
            </el-dropdown>
        </div>

        <div class="ap-foot">
            <span class="ap-foot-lb">版本</span>
            <span class="ap-foot-v">v{{ app.version }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { MoreFilled } from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { pluginApi, type PluginInfo } from '@/api/plugin'

const props = defineProps<{ app: PluginInfo }>()
const emit = defineEmits<{ refresh: [] }>()
const router = useRouter()
const iconFailed = ref(false)

const glyphLabel = computed(() => props.app.name.slice(0, 1))
const app = computed(() => ({
    ...props.app,
    icon_url: iconFailed.value ? null : props.app.icon_url,
}))
const glyphStyle = computed(() => {
    const palette = props.app.palette || ['#94a3b8', '#64748b']
    return { background: `linear-gradient(135deg, ${palette[0]} 0%, ${palette[1]} 100%)` }
})
const onIconError = () => {
    iconFailed.value = true
}
watch(() => props.app.icon_url, () => {
    iconFailed.value = false
})

const onCommand = async (cmd: string) => {
    try {
        if (cmd === 'upgrade') {
            await ElMessageBox.confirm(`升级到 v${props.app.disk_version}？`, '确认升级')
            await pluginApi.upgrade(props.app.code)
            ElMessage.success('升级成功')
            emit('refresh')
        } else if (cmd === 'enable') {
            await pluginApi.enable(props.app.code)
            ElMessage.success('已启用')
            emit('refresh')
        } else if (cmd === 'disable') {
            await pluginApi.disable(props.app.code)
            ElMessage.success('已禁用')
            emit('refresh')
        } else if (cmd === 'uninstall') {
            await ElMessageBox.confirm(
                '卸载后菜单/权限会被清理，业务数据默认保留，重装时可自动恢复。确认卸载？',
                '确认卸载',
                { type: 'warning', confirmButtonText: '卸载（保留数据）' }
            )
            await pluginApi.uninstall(props.app.code, false)
            ElMessage.success('已卸载（数据已保留）')
            emit('refresh')
        } else if (cmd === 'uninstall-purge') {
            await ElMessageBox.confirm(
                '将删除该插件的业务表与专属数据，此操作不可恢复。订单历史中的关联字段（如秒杀 flash_item_id）不会被清除。',
                '危险操作：清除数据',
                { type: 'error', confirmButtonText: '继续' }
            )
            await ElMessageBox.confirm(
                `请再次确认：彻底卸载「${props.app.name}」并清除全部插件数据？`,
                '二次确认',
                { type: 'error', confirmButtonText: '确认清除并卸载' }
            )
            await pluginApi.uninstall(props.app.code, true)
            ElMessage.success('已卸载并清除数据')
            emit('refresh')
        }
    } catch (e: any) {
        if (e?.message && e.message !== 'cancel') ElMessage.error(e.message)
    }
}

const onCardClick = () => {
    if (props.app.status !== 'installed') return
    // Server fills entry_path: /plugin/{code} for workspace plugins,
    // the first menu's path for everything else.
    const target = props.app.entry_path
    if (target) router.push(target)
}
</script>

<style lang="scss" scoped>
.ap-card {
    position: relative;
    padding: 16px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 4px;
    background: var(--el-bg-color);
    cursor: pointer;
    transition: box-shadow 0.2s;

    &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    &.is-plugin-disabled {
        opacity: 0.6;
    }

    .ap-badges {
        position: absolute;
        top: 0;
        left: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        max-width: calc(100% - 48px);
        pointer-events: none;
        z-index: 1;
    }
    .ap-rec,
    .ap-upgrade,
    .ap-disabled-badge {
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.4;
    }
    .ap-rec {
        border-radius: 4px 0 4px 0;
        background: #fef3c7;
        color: #d97706;
    }
    .ap-upgrade {
        border-radius: 4px;
        background: #dbeafe;
        color: #2563eb;
    }
    .ap-disabled-badge {
        border-radius: 4px;
        background: #f3f4f6;
        color: #6b7280;
    }

    .ap-card-top {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }
    .ap-glyph {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: 600;
        flex-shrink: 0;
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    }
    .ap-info {
        flex: 1;
        min-width: 0;
    }
    .ap-name {
        font-weight: 600;
        font-size: 15px;
    }
    .ap-desc {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }
    .ap-more-wrap {
        position: relative;
        z-index: 2;
        flex-shrink: 0;
    }
    .ap-more {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        cursor: pointer;
        color: var(--el-text-color-secondary);
        padding: 4px;
        border: 0;
        border-radius: 4px;
        background: transparent;
        line-height: 1;

        &:hover {
            color: var(--el-text-color-primary);
            background: var(--el-fill-color-light);
        }
    }

    .ap-foot {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--el-border-color-lighter);
        display: flex;
        justify-content: space-between;
        font-size: 12px;
    }
    .ap-foot-lb {
        color: var(--el-text-color-secondary);
    }
}
</style>
