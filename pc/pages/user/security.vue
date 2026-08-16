<template>
  <div>
    <h2 class="text-xl font-bold text-gray-900 mb-6">账号安全</h2>

    <div class="card mb-4">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <div>
          <div class="text-sm text-gray-500 mb-0.5">手机号</div>
          <div class="text-sm text-gray-800">{{ maskedMobile }}</div>
        </div>
        <span class="text-xs text-gray-400">已绑定</span>
      </div>
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <div>
          <div class="text-sm text-gray-500 mb-0.5">密码</div>
          <div class="text-sm text-gray-800">已设置</div>
        </div>
        <span class="text-xs text-gray-400">已绑定</span>
      </div>
      <div class="flex items-center justify-between px-6 py-4">
        <div>
          <div class="text-sm text-gray-500 mb-0.5">昵称</div>
          <div class="text-sm text-gray-800">{{ profile?.nickname || '商城用户' }}</div>
        </div>
        <span class="text-xs text-gray-400">已绑定</span>
      </div>
    </div>

    <div class="card p-6">
      <h3 class="text-base font-semibold text-gray-800 mb-4">修改密码</h3>
      <form class="max-w-600px" @submit.prevent="changePassword">
        <div class="mb-4">
          <label class="block text-sm text-gray-600 mb-1">当前密码</label>
          <input
            v-model="form.old_password"
            type="password"
            autocomplete="current-password"
            class="form-input"
            placeholder="请输入当前密码"
          />
        </div>
        <div class="mb-4">
          <label class="block text-sm text-gray-600 mb-1">新密码</label>
          <input
            v-model="form.new_password"
            type="password"
            autocomplete="new-password"
            class="form-input"
            placeholder="至少 6 位字符"
          />
          <div v-if="form.new_password" class="flex items-center gap-1 mt-2">
            <i
              v-for="level in 4"
              :key="level"
              class="strength-bar"
              :class="{ 'strength-bar--active': passwordStrength >= level }"
            />
            <span class="text-xs text-gray-400 ml-1">{{ strengthText }}</span>
          </div>
        </div>
        <div class="mb-4">
          <label class="block text-sm text-gray-600 mb-1">确认新密码</label>
          <input
            v-model="form.confirm_password"
            type="password"
            autocomplete="new-password"
            class="form-input"
            placeholder="再次输入新密码"
          />
          <p v-if="form.confirm_password && form.confirm_password !== form.new_password" class="text-xs text-red-500 mt-1">
            两次输入的新密码不一致
          </p>
        </div>
        <button type="submit" class="btn-primary text-sm" :disabled="!canSubmit || saving">
          {{ saving ? '更新中...' : '更新密码' }}
        </button>
      </form>
    </div>

    <div class="mt-6">
      <button type="button" class="btn-outline text-sm" @click="logout">退出登录</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useMessage } from 'naive-ui'
import type { UserInfo } from '~/api/auth'
import { userApi } from '~/api/user'
import { useUserStore } from '~/store/user'

const toast = useMessage()
const router = useRouter()
const userStore = useUserStore()
const profile = ref<UserInfo | null>(null)
const saving = ref(false)
const form = reactive({ old_password: '', new_password: '', confirm_password: '' })

const maskedMobile = computed(() => {
  const mobile = String(profile.value?.mobile || '')
  return mobile.length >= 7 ? `${mobile.slice(0, 3)} **** ${mobile.slice(-4)}` : mobile || '未获取'
})

const passwordStrength = computed(() => {
  const value = form.new_password
  if (!value) return 0
  let score = value.length >= 6 ? 1 : 0
  if (/[A-Za-z]/.test(value) && /\d/.test(value)) score++
  if (/[^A-Za-z0-9]/.test(value)) score++
  if (value.length >= 12) score++
  return Math.min(4, score)
})
const strengthText = computed(() => ['', '较弱', '一般', '较强', '强'][passwordStrength.value])
const canSubmit = computed(() =>
  form.old_password.length > 0
  && form.new_password.length >= 6
  && form.new_password === form.confirm_password
)

const loadProfile = async () => {
  const res = await userApi.getProfile()
  if (res.code === 200) profile.value = res.data
}

const changePassword = async () => {
  if (!canSubmit.value) return
  saving.value = true
  try {
    const res = await userApi.changePassword({
      old_password: form.old_password,
      new_password: form.new_password,
    })
    if (res.code === 200) {
      toast.success('密码已更新')
      Object.assign(form, { old_password: '', new_password: '', confirm_password: '' })
    }
  } finally {
    saving.value = false
  }
}

const logout = async () => {
  await userStore.logout()
  toast.success('已安全退出')
  router.push('/login')
}

onMounted(loadProfile)
</script>

<style scoped>
.strength-bar {
  display: inline-block;
  width: 32px;
  height: 3px;
  background: #e5e7eb;
  border-radius: 2px;
}
.strength-bar--active {
  background: var(--color-primary);
}
</style>
