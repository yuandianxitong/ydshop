/** @deprecated 插件 API 已拆到独立文件；此处保留再导出以免旧 import 断裂 */
export { couponApi } from './coupon'
export { fullDiscountApi } from './full-discount'
export { flashSaleApi } from './flash-sale'
export { groupBuyApi } from './group-buy'
export { pointsProductApi, pointsOrderApi } from './points-product'
export { lotteryApi, lotteryShipmentApi } from './lottery'
export type {
    LotteryPrizeReq, LotteryActivityReq, LotteryActivityInfo, LotteryActivityQuery,
    LotteryRecordItem, LotteryRecordQuery, LotteryCouponOption,
    LotteryShipmentItem, LotteryShipmentQuery, LotteryShipReq
} from './lottery'
export { signConfigApi, signLogApi } from './sign'
export type { SignConfig, SignLogStats, SignLogItem, SignLogQuery } from './sign'
export { newUserGiftApi } from './new-user-gift'
export type {
    NewUserGift, NewUserGiftQuery, NewUserGiftRules, NewUserGiftStats,
    NewUserGiftLog, NewUserGiftLogQuery
} from './new-user-gift'
