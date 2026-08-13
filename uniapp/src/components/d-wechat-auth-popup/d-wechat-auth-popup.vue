<template>
  <u-popup
    :show="visible"
    mode="bottom"
    :safeAreaInsetBottom="true"
    :customStyle="{ borderRadius: '24rpx 24rpx 0 0' }"
    @close="handleClose"
  >
    <view class="wx-auth">
      <view class="wx-auth__header">
        <text class="wx-auth__title">获取您的昵称、头像{{ showPhone ? '、手机号' : '' }}</text>
        <text class="wx-auth__tips">可按任意顺序授权，全部完成后点击保存并登录</text>
      </view>

      <!-- #ifdef MP-WEIXIN -->
      <view class="wx-auth__row">
        <text class="wx-auth__label">头像<text class="wx-auth__required">*</text></text>
        <button class="wx-auth__avatar-btn" open-type="chooseAvatar" @chooseavatar="onChooseAvatar">
          <image
            v-if="avatarPreview || props.defaultAvatar"
            class="wx-auth__avatar"
            :src="avatarPreview || props.defaultAvatar"
            mode="aspectFill"
          />
          <view v-else class="wx-auth__avatar wx-auth__avatar--placeholder">
            <d-icon name="user" size="56rpx" color="#c0c4cc" />
          </view>
          <text class="wx-auth__arrow">›</text>
        </button>
      </view>
      <view class="wx-auth__row">
        <text class="wx-auth__label">昵称<text class="wx-auth__required">*</text></text>
        <input
          class="wx-auth__input"
          type="nickname"
          v-model="nickname"
          placeholder="请输入昵称"
          maxlength="30"
          @blur="onNicknameBlur"
        />
      </view>
      <view v-if="showPhone" class="wx-auth__row">
        <text class="wx-auth__label">手机号<text class="wx-auth__required">*</text></text>
        <button
          v-if="!phoneDisplay"
          class="wx-auth__phone-btn"
          open-type="getPhoneNumber"
          @getphonenumber="onGetPhone"
        >
          点击授权手机号
        </button>
        <text v-else class="wx-auth__phone-done">{{ phoneDisplay }}</text>
      </view>
      <!-- #endif -->

      <view class="wx-auth__footer">
        <u-button type="primary" block :loading="loading || uploading" @click="handleSubmit">
          保存并登录
        </u-button>
      </view>
    </view>
  </u-popup>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { uploadApi } from '@/api/upload'
import { getToken } from '@/utils/auth'
import { getMissingFields, buildSubmitPayload } from './helpers'

const props = withDefaults(defineProps<{
  modelValue: boolean
  showPhone?: boolean
  phoneDisplay?: string
  defaultAvatar?: string
  defaultNickname?: string
  loading?: boolean
}>(), { showPhone: false, phoneDisplay: '', defaultAvatar: '', defaultNickname: '', loading: false })

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  submit: [payload: { nickname: string; avatar?: string }]
  'phone-auth': [code: string]
  close: []
}>()

const visible = computed({
  get: () => props.modelValue,
  set: (v: boolean) => emit('update:modelValue', v),
})

const nickname = ref(props.defaultNickname === '微信用户' ? '' : props.defaultNickname)
const avatarPath = ref('')
const avatarPreview = ref('')
/** 本地临时路径：无 token 时先预览，拿到 token 后再上传 */
const avatarLocalPath = ref('')
const uploading = ref(false)

watch(() => props.modelValue, (open) => {
  if (open) {
    nickname.value = props.defaultNickname === '微信用户' ? '' : props.defaultNickname
    avatarPath.value = ''
    avatarPreview.value = ''
    avatarLocalPath.value = ''
  }
})

// 手机号授权成功拿到 token 后，若已选头像则自动补传
watch(() => props.phoneDisplay, async (display) => {
  if (display && avatarLocalPath.value && !avatarPath.value && getToken()) {
    await uploadPendingAvatar()
  }
})

async function uploadPendingAvatar(): Promise<boolean> {
  const local = avatarLocalPath.value
  if (!local) return !!avatarPath.value
  if (avatarPath.value) return true
  if (!getToken()) return false

  uploading.value = true
  try {
    const result = await uploadApi.uploadImage(local)
    avatarPath.value = result.path || result.url
    return true
  } catch {
    uni.showToast({ title: '头像上传失败，请重试', icon: 'none' })
    return false
  } finally {
    uploading.value = false
  }
}

async function onChooseAvatar(e: any): Promise<void> {
  const tempPath = String(e?.detail?.avatarUrl ?? '')
  if (!tempPath) return
  avatarPreview.value = tempPath
  avatarLocalPath.value = tempPath
  avatarPath.value = ''

  // 已有 token（资料补全 / 已绑手机）立刻上传；否则只预览，互不影响手机号授权顺序
  if (getToken()) {
    await uploadPendingAvatar()
  }
}

function onNicknameBlur(e: any): void {
  const v = String(e?.detail?.value ?? '')
  if (v) nickname.value = v
}

function onGetPhone(e: any): void {
  if (e?.detail?.errMsg === 'getPhoneNumber:ok' && e.detail.code) {
    emit('phone-auth', String(e.detail.code))
  } else {
    uni.showToast({ title: '取消授权将无法完成登录', icon: 'none' })
  }
}

async function handleSubmit(): Promise<void> {
  if (props.loading || uploading.value) return

  const missing = getMissingFields(
    nickname.value,
    avatarPath.value,
    avatarPreview.value,
    props.showPhone,
    props.phoneDisplay,
  )
  if (missing.length > 0) {
    uni.showToast({ title: `请先完善：${missing.join('、')}`, icon: 'none' })
    return
  }

  // 已选头像但尚未上传（先选头像后绑手机的场景）：提交前补传
  if (!avatarPath.value && avatarLocalPath.value) {
    const ok = await uploadPendingAvatar()
    if (!ok) return
  }

  if (!avatarPath.value) {
    uni.showToast({ title: '请先完善：头像', icon: 'none' })
    return
  }

  emit('submit', buildSubmitPayload(nickname.value, avatarPath.value))
}

function handleClose(): void {
  visible.value = false
  emit('close')
}
</script>

<style lang="scss" scoped>
@import '@/styles/variables.scss';

.wx-auth {
  padding: 40rpx 32rpx calc(20rpx + env(safe-area-inset-bottom));

  &__header {
    text-align: left;
    padding-bottom: 24rpx;
    border-bottom: 1rpx solid $border-color;
  }
  &__title {
    display: block;
    font-size: 32rpx;
    font-weight: 600;
    color: $text-color;
  }
  &__tips {
    display: block;
    margin-top: 8rpx;
    font-size: 24rpx;
    color: $text-color-secondary;
  }
  &__row {
    display: flex;
    align-items: center;
    padding: 24rpx 0;
    border-bottom: 1rpx solid $border-color;
  }
  &__label {
    width: 120rpx;
    font-size: 28rpx;
    color: $text-color;
  }
  &__required {
    color: $primary-color;
    margin-left: 4rpx;
  }
  &__avatar-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: transparent;
    padding: 0;
    margin: 0;
    border: none;
    line-height: 1;
    &::after { border: none; }
  }
  &__avatar {
    width: 96rpx;
    height: 96rpx;
    border-radius: 12rpx;
    background: $bg-color;
    &--placeholder {
      display: flex;
      align-items: center;
      justify-content: center;
    }
  }
  &__arrow {
    font-size: 32rpx;
    color: #cccccc;
  }
  &__input {
    flex: 1;
    height: 72rpx;
    font-size: 28rpx;
  }
  &__phone-btn {
    flex: 1;
    text-align: left;
    background: transparent;
    padding: 0;
    margin: 0;
    border: none;
    font-size: 28rpx;
    color: $primary-color;
    line-height: 72rpx;
    height: 72rpx;
    &::after { border: none; }
  }
  &__phone-done {
    flex: 1;
    font-size: 28rpx;
    color: $text-color;
  }
  &__footer {
    margin-top: 40rpx;
  }
}
</style>
