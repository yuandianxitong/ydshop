<template>
  <d-page :safe-area="true">
    <view class="edit-profile">
      <!-- 头像 -->
      <view class="avatar-card">
        <d-avatar-upload v-model="form.avatar" />
      </view>

      <!-- 基本信息 -->
      <view class="form-card">
        <view class="row">
          <text class="row__label">昵称</text>
          <input
            v-model="form.nickname"
            class="row__input"
            placeholder="请输入昵称"
            placeholder-class="row__placeholder"
            maxlength="30"
          />
        </view>

        <view class="row" @tap="showGenderPicker = true">
          <text class="row__label">性别</text>
          <text class="row__value" :class="{ 'row__value--placeholder': !genderSelected }">
            {{ genderLabel }}
          </text>
          <d-icon name="arrow-right" size="28rpx" color="#c0c4cc" />
        </view>

        <view class="row" @tap="showBirthdayPicker = true">
          <text class="row__label">生日</text>
          <text class="row__value" :class="{ 'row__value--placeholder': !birthdayLabelResolved }">
            {{ birthdayLabel }}
          </text>
          <d-icon name="arrow-right" size="28rpx" color="#c0c4cc" />
        </view>

        <view class="row">
          <text class="row__label">邮箱</text>
          <input
            v-model="form.email"
            class="row__input"
            type="text"
            placeholder="请输入邮箱（可选）"
            placeholder-class="row__placeholder"
          />
        </view>

        <view class="row row--last" @tap="onMobileCellClick">
          <text class="row__label">手机</text>
          <text
            class="row__value"
            :class="{ 'row__value--placeholder': !form.mobile, 'row__value--link': !form.mobile }"
          >
            {{ form.mobile || '未绑定，点击绑定' }}
          </text>
          <d-icon v-if="!form.mobile" name="arrow-right" size="28rpx" color="#c0c4cc" />
        </view>
      </view>

      <view
        class="submit"
        :class="{ 'submit--loading': loading }"
        @tap="!loading && handleSave()"
      >
        <text class="submit__text">{{ loading ? '保存中...' : '保存' }}</text>
      </view>
    </view>

    <d-bind-mobile v-model:visible="showBindMobile" @success="onMobileBound" />

    <u-picker
      :show="showGenderPicker"
      :columns="genderColumns"
      keyName="label"
      @confirm="onGenderConfirm"
      @cancel="showGenderPicker = false"
      @close="showGenderPicker = false"
    />

    <u-datetime-picker
      :show="showBirthdayPicker"
      v-model="form.birthday"
      mode="date"
      :maxDate="maxDate"
      @confirm="onBirthdayConfirm"
      @cancel="showBirthdayPicker = false"
      @close="showBirthdayPicker = false"
    />
  </d-page>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useUser } from '../composables/useUser'
import { useUserStore } from '@/store/user.store'

const userStore = useUserStore()
const { loading, loadProfile, updateProfile } = useUser()

const genderOptions = [
  { label: '未知', value: 0 },
  { label: '男', value: 1 },
  { label: '女', value: 2 },
]

const genderColumns = [genderOptions]
const maxDate = new Date().getTime()

const showGenderPicker = ref(false)
const showBirthdayPicker = ref(false)
const showBindMobile = ref(false)

const form = reactive({
  avatar: '',
  nickname: '',
  gender: 0,
  birthday: '' as string | number,
  email: '',
  mobile: '',
})

function emailValid(s: string): boolean {
  if (!s) return true
  return /^[\w._-]+@[\w.-]+\.[a-zA-Z]{2,}$/.test(s)
}

const genderSelected = computed(() => form.gender === 1 || form.gender === 2)

const genderLabel = computed(() => {
  const opt = genderOptions.find(o => o.value === form.gender)
  return opt ? opt.label : '请选择性别'
})

const birthdayLabelResolved = computed(() => !!formatBirthday(form.birthday))

const birthdayLabel = computed(() => {
  const str = formatBirthday(form.birthday)
  return str || '请选择生日'
})

function formatBirthday(val: string | number): string {
  if (!val) return ''
  const d = typeof val === 'number' ? new Date(val) : new Date(val)
  if (isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function parseBirthday(val: string): number {
  if (!val) return 0
  const d = new Date(val.replace(/-/g, '/'))
  return isNaN(d.getTime()) ? 0 : d.getTime()
}

function onGenderConfirm(e: { value: any[] }) {
  const selected = e.value[0]
  if (selected) {
    form.gender = selected.value
  }
  showGenderPicker.value = false
}

function onBirthdayConfirm(e: { value: number }) {
  form.birthday = e.value
  showBirthdayPicker.value = false
}

function onMobileCellClick() {
  if (form.mobile) return
  showBindMobile.value = true
}

function onMobileBound(mobile: string) {
  form.mobile = mobile
  if (userStore.userInfo) {
    userStore.userInfo.mobile = mobile
  }
}

onMounted(async () => {
  try {
    const profile = await loadProfile()
    form.avatar = profile.avatar || ''
    form.nickname = profile.nickname || ''
    form.gender = profile.gender ?? 0
    form.birthday = parseBirthday(profile.birthday || '')
    form.email = profile.email || ''
    form.mobile = profile.mobile || ''
  } catch {
    // error handled
  }
})

async function handleSave() {
  if (!form.nickname.trim()) {
    uni.showToast({ title: '请输入昵称', icon: 'none' })
    return
  }
  if (!emailValid(form.email)) {
    uni.showToast({ title: '邮箱格式不正确', icon: 'none' })
    return
  }

  await updateProfile({
    avatar: form.avatar,
    nickname: form.nickname,
    gender: form.gender,
    birthday: formatBirthday(form.birthday),
    email: form.email,
  })
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

$radius: 12rpx;

.edit-profile {
  padding-bottom: 40rpx;
}

.avatar-card {
  background: #ffffff;
  border-radius: $radius;
  border: 1rpx solid $border-color;
  padding: 32rpx 24rpx;
  margin-bottom: 20rpx;
}

.form-card {
  background: #ffffff;
  border-radius: $radius;
  border: 1rpx solid $border-color;
  overflow: hidden;
  margin-bottom: 48rpx;
}

.row {
  display: flex;
  align-items: center;
  min-height: 96rpx;
  padding: 0 24rpx;
  border-bottom: 1rpx solid #f0f2f5;
  box-sizing: border-box;

  &--last {
    border-bottom: none;
  }

  &__label {
    flex-shrink: 0;
    width: 120rpx;
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
  }

  &__input {
    flex: 1;
    min-width: 0;
    height: 96rpx;
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
    text-align: right;
    background: transparent;
  }

  &__placeholder {
    color: #c0c4cc;
    font-size: 28rpx;
  }

  &__value {
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    color: var(--color-text, #{$text-color});
    text-align: right;
    margin-right: 8rpx;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;

    &--placeholder {
      color: #c0c4cc;
    }

    &--link {
      color: var(--color-primary, #{$primary-color});
    }
  }
}

.submit {
  height: 88rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-primary, #{$primary-color});
  border-radius: $radius;
  box-shadow: 0 8rpx 20rpx -8rpx rgba(41, 121, 255, 0.45);

  &:active {
    opacity: 0.92;
  }

  &--loading {
    opacity: 0.7;
  }

  &__text {
    font-size: 30rpx;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: 2rpx;
  }
}
</style>
