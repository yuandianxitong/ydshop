<template>
  <view class="agreement-page">
    <scroll-view scroll-y class="agreement-page__scroll">
      <view class="agreement-page__content">
        <view class="section">
          <text class="section__title">用户协议</text>
          <text class="section__body">{{ agreementText }}</text>
        </view>

        <view class="section">
          <text class="section__title">隐私政策</text>
          <text class="section__body">{{ privacyText }}</text>
        </view>

        <view style="height: 80rpx" />
      </view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
const agreementText = `欢迎使用本应用。请您在使用本服务前仔细阅读本协议。

1. 服务说明：本应用为用户提供商品浏览、下单、支付、配送、售后等服务。

2. 用户行为：您应遵守相关法律法规，不得利用本服务从事违法、违规活动。

3. 知识产权：本应用所提供的内容版权归本公司所有。

4. 免责声明：因不可抗力、第三方原因、设备故障等导致的服务中断，本公司不承担责任。

如有问题请联系客服。`

const privacyText = `我们重视您的隐私。本政策说明我们如何收集、使用和保护您的个人信息。

1. 收集信息：我们仅收集为完成服务所必需的信息（如手机号、收货地址、订单信息）。

2. 使用信息：仅用于订单处理、服务通知、安全验证等目的。

3. 保护信息：我们采取行业标准的安全措施保护您的信息。

4. 第三方共享：除非取得您的明确同意，我们不会向第三方分享您的个人信息。

5. 您的权利：您有权访问、更正、删除您的个人信息，可通过设置页"账号注销"或联系客服处理。

如有疑问请联系客服。`
</script>

<style lang="scss" scoped>
.agreement-page {
  min-height: 100vh;
  background: #fff;
  &__scroll { height: 100vh; }
  &__content { padding: 40rpx 32rpx; }
}
.section {
  margin-bottom: 48rpx;
  &__title {
    display: block;
    font-size: 32rpx;
    font-weight: 600;
    color: #18181b;
    margin-bottom: 24rpx;
  }
  &__body {
    display: block;
    font-size: 26rpx;
    color: #52525b;
    line-height: 1.8;
    white-space: pre-wrap;
  }
}
</style>
