<template>
  <div class="security-page">
    <div class="page-heading">
      <div>
        <div class="eyebrow">ACCOUNT SHIELD</div>
        <h2>账号安全</h2>
        <p>检查账号绑定信息，并定期更新登录密码。</p>
      </div>
      <div class="security-score">
        <span class="score-ring"><i class="i-carbon-security" /></span>
        <div><strong>安全状态正常</strong><small>手机号与密码保护已启用</small></div>
      </div>
    </div>

    <div class="security-overview">
      <article>
        <span class="overview-icon i-carbon-phone" />
        <div><small>绑定手机号</small><strong>{{ maskedMobile }}</strong><p>用于登录与身份核验</p></div>
        <span class="verified">已绑定</span>
      </article>
      <article>
        <span class="overview-icon i-carbon-password" />
        <div><small>登录密码</small><strong>已设置</strong><p>建议每 90 天更新一次</p></div>
        <span class="verified">保护中</span>
      </article>
      <article>
        <span class="overview-icon i-carbon-user-certification" />
        <div><small>登录身份</small><strong>{{ profile?.nickname || '商城用户' }}</strong><p>账号 ID {{ profile?.id || '—' }}</p></div>
        <span class="verified">正常</span>
      </article>
    </div>

    <section class="password-panel">
      <div class="panel-aside">
        <span>CHANGE</span>
        <strong>PASSWORD</strong>
        <p>新密码至少 6 位，建议同时包含字母、数字和符号。</p>
      </div>
      <form class="password-form" @submit.prevent="changePassword">
        <h3>修改登录密码</h3>
        <label>
          <span>当前密码</span>
          <input v-model="form.old_password" type="password" autocomplete="current-password" placeholder="请输入当前密码" />
        </label>
        <label>
          <span>新密码</span>
          <input v-model="form.new_password" type="password" autocomplete="new-password" placeholder="至少 6 位字符" />
        </label>
        <div v-if="form.new_password" class="strength-line">
          <i v-for="level in 4" :key="level" :class="{ active: passwordStrength >= level }" />
          <span>{{ strengthText }}</span>
        </div>
        <label>
          <span>确认新密码</span>
          <input v-model="form.confirm_password" type="password" autocomplete="new-password" placeholder="再次输入新密码" />
        </label>
        <div class="form-footer">
          <span v-if="form.confirm_password && form.confirm_password !== form.new_password" class="form-error">两次输入的新密码不一致</span>
          <span v-else>修改成功后请使用新密码登录。</span>
          <button type="submit" :disabled="!canSubmit || saving">{{ saving ? '更新中...' : '更新密码' }}</button>
        </div>
      </form>
    </section>

    <section class="session-panel">
      <div><span class="i-carbon-logout" /><div><strong>退出当前登录</strong><p>将清除当前浏览器保存的登录凭据。</p></div></div>
      <button @click="logout">安全退出</button>
    </section>
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
.security-page { color: #192434; }.page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 22px; }.eyebrow { margin-bottom: 5px; color: #0c8f69; font-size: 10px; font-weight: 800; letter-spacing: .18em; }.page-heading h2 { margin: 0; font-size: 24px; font-weight: 750; letter-spacing: -.02em; }.page-heading p { margin: 6px 0 0; color: #87909f; font-size: 13px; }.security-score { display: flex; align-items: center; gap: 10px; }.score-ring { display: grid; place-items: center; width: 38px; height: 38px; color: #0b9b70; font-size: 19px; background: #eafaf4; border: 1px solid #bdebdc; border-radius: 50%; }.security-score strong, .security-score small { display: block; }.security-score strong { color: #315144; font-size: 12px; }.security-score small { margin-top: 2px; color: #91a098; font-size: 10px; }
.security-overview { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }.security-overview article { position: relative; display: flex; gap: 11px; min-width: 0; padding: 17px 15px; background: #fff; border: 1px solid #e5e9ee; border-radius: 9px; }.overview-icon { display: grid; place-items: center; flex: 0 0 auto; width: 34px; height: 34px; color: #52647a; font-size: 17px; background: #f1f4f7; border-radius: 8px; }.security-overview small, .security-overview strong, .security-overview p { display: block; }.security-overview small { color: #929ba8; font-size: 10px; }.security-overview strong { margin-top: 2px; overflow: hidden; color: #334155; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }.security-overview p { margin: 3px 0 0; color: #a1a8b2; font-size: 9px; }.verified { position: absolute; top: 10px; right: 10px; color: #0a8b65; font-size: 9px; }
.password-panel { display: grid; grid-template-columns: 190px 1fr; overflow: hidden; margin-top: 18px; background: #fff; border: 1px solid #e3e7ed; border-radius: 11px; }.panel-aside { padding: 28px 24px; color: #d9e2ec; background: #192333; }.panel-aside span, .panel-aside strong { display: block; }.panel-aside span { font-size: 10px; font-weight: 700; letter-spacing: .2em; }.panel-aside strong { margin-top: 2px; color: #fff; font-size: 20px; letter-spacing: -.03em; }.panel-aside p { margin: 24px 0 0; color: #95a4b6; font-size: 11px; line-height: 1.7; }.password-form { padding: 25px 28px 23px; }.password-form h3 { margin: 0 0 17px; font-size: 15px; }.password-form label { display: grid; grid-template-columns: 92px minmax(0, 1fr); align-items: center; margin-bottom: 12px; }.password-form label > span { color: #606c7d; font-size: 12px; }.password-form input { width: 100%; height: 38px; padding: 0 11px; color: #334155; font-size: 12px; background: #fafbfc; border: 1px solid #dfe4ea; border-radius: 6px; outline: none; }.password-form input:focus { background: #fff; border-color: #0b9970; box-shadow: 0 0 0 3px rgba(11,153,112,.08); }.strength-line { display: flex; align-items: center; gap: 4px; padding-left: 92px; margin: -5px 0 10px; }.strength-line i { width: 31px; height: 3px; background: #e6e9ed; border-radius: 2px; }.strength-line i.active { background: #13a478; }.strength-line span { margin-left: 4px; color: #8b95a2; font-size: 9px; }.form-footer { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding-top: 15px; margin-top: 16px; color: #9aa2ae; font-size: 10px; border-top: 1px solid #eef0f3; }.form-footer button { padding: 8px 15px; color: #fff; font-size: 11px; font-weight: 650; background: #0b9970; border-radius: 6px; }.form-footer button:disabled { opacity: .45; cursor: not-allowed; }.form-error { color: #d14343; }
.session-panel { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 16px 18px; margin-top: 14px; background: #fff; border: 1px solid #e7e9ed; border-radius: 9px; }.session-panel > div { display: flex; align-items: center; gap: 11px; }.session-panel > div > span { color: #8b96a5; font-size: 20px; }.session-panel strong { display: block; color: #455164; font-size: 12px; }.session-panel p { margin: 2px 0 0; color: #9ca4af; font-size: 10px; }.session-panel button { padding: 7px 11px; color: #b44343; font-size: 11px; background: #fff6f6; border: 1px solid #f0d4d4; border-radius: 5px; }
@media (max-width: 900px) { .security-overview { grid-template-columns: 1fr; }.password-panel { grid-template-columns: 1fr; }.panel-aside { display: none; } }
@media (max-width: 650px) { .page-heading { align-items: flex-start; flex-direction: column; }.password-form label { grid-template-columns: 1fr; gap: 6px; }.strength-line { padding-left: 0; } }
</style>
