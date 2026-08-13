// uniapp/src/components/d-icon/icons.ts
//
// 项目使用的图标注册表（Remix Icon @iconify-icons/ri）
// 按需 import 单个 .js（每个 200-400 字节），保证小程序主包小
// 添加新图标：去 https://icones.js.org/collection/ri 选好，按下面格式追加

import arrowLeftS from '@iconify-icons/ri/arrow-left-s-line'
import arrowRightS from '@iconify-icons/ri/arrow-right-s-line'
import close from '@iconify-icons/ri/close-line'
import search from '@iconify-icons/ri/search-line'
import more from '@iconify-icons/ri/more-2-line'

// 商城核心
import shoppingCartLine from '@iconify-icons/ri/shopping-cart-2-line'
import shoppingCartFill from '@iconify-icons/ri/shopping-cart-2-fill'
import customerService from '@iconify-icons/ri/customer-service-2-line'
import heartLine from '@iconify-icons/ri/heart-3-line'
import heartFill from '@iconify-icons/ri/heart-3-fill'

// Tabbar
import homeLine from '@iconify-icons/ri/home-5-line'
import homeFill from '@iconify-icons/ri/home-5-fill'
import appsLine from '@iconify-icons/ri/apps-2-line'
import appsFill from '@iconify-icons/ri/apps-2-fill'
import userLine from '@iconify-icons/ri/user-3-line'
import userFill from '@iconify-icons/ri/user-3-fill'

// 业务/功能
import errorWarn from '@iconify-icons/ri/error-warning-line'
import checkboxCircle from '@iconify-icons/ri/checkbox-circle-fill'
import checkboxBlankCircle from '@iconify-icons/ri/checkbox-blank-circle-line'
import addCircle from '@iconify-icons/ri/add-circle-line'
import phone from '@iconify-icons/ri/phone-line'
import smartphone from '@iconify-icons/ri/smartphone-line'
import mapPin from '@iconify-icons/ri/map-pin-2-line'
import time from '@iconify-icons/ri/time-line'

// 自提模块
import storeLine from '@iconify-icons/ri/store-2-line'
import qrCodeLine from '@iconify-icons/ri/qr-code-line'
import phoneFill from '@iconify-icons/ri/phone-fill'

// 替换 iconfont 用的图标（profile / settings / 订单 / 支付等）
import alipay from '@iconify-icons/ri/alipay-fill'
import wechatPay from '@iconify-icons/ri/wechat-pay-fill'
import wechatFill from '@iconify-icons/ri/wechat-fill'
import notification from '@iconify-icons/ri/notification-3-line'
import box from '@iconify-icons/ri/inbox-line'
import chat from '@iconify-icons/ri/chat-3-line'
import checkLine from '@iconify-icons/ri/check-line'
import clipboard from '@iconify-icons/ri/clipboard-line'
import diagram from '@iconify-icons/ri/share-forward-line'
import fileText from '@iconify-icons/ri/file-text-line'
import settings from '@iconify-icons/ri/settings-3-line'
import image from '@iconify-icons/ri/image-line'
import accountCircle from '@iconify-icons/ri/account-circle-line'
import question from '@iconify-icons/ri/question-line'
import sendPlane from '@iconify-icons/ri/send-plane-line'
import shareLine from '@iconify-icons/ri/share-line'
import shieldCheck from '@iconify-icons/ri/shield-check-line'
import priceTag from '@iconify-icons/ri/price-tag-3-line'
import coupon from '@iconify-icons/ri/coupon-3-line'
import deleteBin from '@iconify-icons/ri/delete-bin-line'
import lockUnlock from '@iconify-icons/ri/lock-unlock-line'
import wallet from '@iconify-icons/ri/wallet-line'
import starLine from '@iconify-icons/ri/star-line'
import starFill from '@iconify-icons/ri/star-fill'
import editLine from '@iconify-icons/ri/edit-line'
import addLine from '@iconify-icons/ri/add-line'

export interface IconData {
  body: string
  width?: number
  height?: number
  hFlip?: boolean
  vFlip?: boolean
  rotate?: number
}

/**
 * 项目可用图标短名 → IconData
 * 短名设计：去掉 ri 前缀和 -line 后缀，core/common 用 -fill 后缀区分实心
 */
export const ICONS: Record<string, IconData> = {
  // 通用
  'arrow-left': arrowLeftS,
  'arrow-right': arrowRightS,
  'close': close,
  'search': search,
  'more': more,

  // 商城核心
  'cart': shoppingCartLine,
  'cart-fill': shoppingCartFill,
  'customer-service': customerService,
  'heart': heartLine,
  'heart-fill': heartFill,

  // Tabbar
  'home': homeLine,
  'home-fill': homeFill,
  'category': appsLine,
  'category-fill': appsFill,
  'user': userLine,
  'user-fill': userFill,

  // 业务/功能
  'error-warn': errorWarn,
  'check': checkboxCircle,
  'check-blank': checkboxBlankCircle,
  'check-line': checkLine,
  'add-circle': addCircle,
  'phone': phone,
  'mobile': smartphone,
  'location': mapPin,
  'time': time,

  // 自提模块
  'store': storeLine,
  'qr-code': qrCodeLine,
  'phone-call': phoneFill,

  // 替换 iconfont 用
  'alipay': alipay,
  'wechat': wechatFill,
  'wechat-pay': wechatPay,
  'bell': notification,
  'box': box,
  'chat': chat,
  'clipboard': clipboard,
  'diagram': diagram,
  'file-text': fileText,
  'gear': settings,
  'image': image,
  'person-circle': accountCircle,
  'question': question,
  'send': sendPlane,
  'share': shareLine,
  'shield': shieldCheck,
  'tag': priceTag,
  'ticket': coupon,
  'trash': deleteBin,
  'unlock': lockUnlock,
  'wallet': wallet,
  'star': starLine,
  'star-fill': starFill,
  'edit': editLine,
  'add': addLine,
}
