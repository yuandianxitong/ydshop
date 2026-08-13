import type { App, Directive, DirectiveBinding, WatchStopHandle } from 'vue'
import { watch } from 'vue'

import { useUserStore } from '@/store'

type PermissionValue = string | string[] | null | undefined
type PermissionMode = 'any' | 'all'
type UserStore = ReturnType<typeof useUserStore>

interface PermissionState {
    mode: PermissionMode
    value: PermissionValue
    userStore: UserStore
    hidden: boolean
    originalDisplay: string
    originalDisplayPriority: string
    stop?: WatchStopHandle
}

const permissionStateKey: unique symbol = Symbol('permissionState')

interface PermissionElement extends HTMLElement {
    [permissionStateKey]?: PermissionState
}

function checkPermission(
    value: PermissionValue,
    mode: PermissionMode,
    userStore: UserStore
): boolean {
    const permissions = Array.isArray(value) ? value : value ? [value] : []

    if (mode === 'all') {
        return userStore.hasAllPermissions(permissions)
    }

    return userStore.hasAnyPermission(permissions)
}

/**
 * 只隐藏无权限元素，始终保留 Vue 管理的 DOM 节点。
 *
 * 直接 removeChild/remove 会破坏虚拟 DOM 与真实 DOM 的对应关系；当同级 v-if、
 * v-for 或异步数据发生更新时，Vue 可能把已移除节点当作插入锚点并抛出
 * insertBefore NotFoundError。真正的权限边界由后端校验，前端指令只负责可见性。
 */
function setPermissionVisibility(el: PermissionElement, allowed: boolean) {
    const state = el[permissionStateKey]
    if (!state) return

    if (allowed) {
        if (!state.hidden) return

        if (state.originalDisplay) {
            el.style.setProperty('display', state.originalDisplay, state.originalDisplayPriority)
        } else {
            el.style.removeProperty('display')
        }
        el.removeAttribute('data-permission-hidden')
        state.hidden = false
        return
    }

    if (state.hidden) {
        // Vue 的动态 style 更新可能覆盖 display，指令更新时需要重新收口。
        el.style.setProperty('display', 'none', 'important')
        return
    }

    state.originalDisplay = el.style.getPropertyValue('display')
    state.originalDisplayPriority = el.style.getPropertyPriority('display')
    el.style.setProperty('display', 'none', 'important')
    el.setAttribute('data-permission-hidden', 'true')
    state.hidden = true
}

function syncPermission(el: PermissionElement) {
    const state = el[permissionStateKey]
    if (!state) return

    setPermissionVisibility(el, checkPermission(state.value, state.mode, state.userStore))
}

function createPermissionDirective(
    mode: PermissionMode
): Directive<PermissionElement, PermissionValue> {
    return {
        beforeMount(el: PermissionElement, binding: DirectiveBinding<PermissionValue>) {
            el[permissionStateKey] = {
                mode,
                value: binding.value,
                userStore: useUserStore(),
                hidden: false,
                originalDisplay: '',
                originalDisplayPriority: ''
            }
            syncPermission(el)
        },

        mounted(el: PermissionElement) {
            const state = el[permissionStateKey]
            if (!state) return

            state.stop = watch(
                () => state.userStore.permissions,
                () => syncPermission(el),
                { deep: true }
            )
        },

        updated(el: PermissionElement, binding: DirectiveBinding<PermissionValue>) {
            const state = el[permissionStateKey]
            if (!state) return

            state.value = binding.value
            syncPermission(el)
        },

        beforeUnmount(el: PermissionElement) {
            el[permissionStateKey]?.stop?.()
        },

        unmounted(el: PermissionElement) {
            delete el[permissionStateKey]
        }
    }
}

const permission = createPermissionDirective('any')
const strictPermission = createPermissionDirective('all')

export default {
    install(app: App) {
        // v-has-perm 指令（OR关系，满足任意一个权限即可）
        app.directive('hasPerm', permission)

        // v-has-all-perm 指令（AND关系，需要满足所有权限）
        app.directive('hasAllPerm', strictPermission)

        // 保持向后兼容
        app.directive('permission', permission)
        app.directive('perms', permission)
    }
}
