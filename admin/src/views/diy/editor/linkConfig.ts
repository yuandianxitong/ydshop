export interface LinkItem {
    label: string
    path: string
    needSelect?: boolean
    selectType?: string
}

export interface LinkCategory {
    label: string
    key: string
    items: LinkItem[]
}

export const linkCategories: LinkCategory[] = [
    {
        label: '基础页面',
        key: 'basic',
        items: [
            { label: '首页', path: '/pages/index/index' },
            { label: '分类页', path: '/modules/goods/pages/category' },
            { label: '购物车', path: '/modules/cart/pages/cart' },
            { label: '个人中心', path: '/modules/user/pages/profile' },
        ]
    },
    {
        label: '商品',
        key: 'goods',
        items: [
            { label: '商品详情', path: '/modules/goods/pages/detail?id=', needSelect: true, selectType: 'goods' },
            { label: '商品列表', path: '/modules/goods/pages/list' },
        ]
    },
    {
        label: '营销',
        key: 'marketing',
        items: [
            { label: '限时秒杀', path: '/modules/marketing/pages/flash-sale' },
            { label: '拼团活动', path: '/modules/marketing/pages/group-buy' },
            { label: '积分商城', path: '/modules/marketing/pages/points-mall' },
            { label: '优惠券中心', path: '/modules/marketing/pages/coupon' },
            { label: '抽奖活动', path: '/modules/marketing/pages/lottery' },
        ]
    },
    {
        label: '用户中心',
        key: 'user',
        items: [
            { label: '我的订单', path: '/modules/order/pages/list' },
            { label: '签到', path: '/modules/user/pages/sign' },
            { label: '浏览记录', path: '/modules/user/pages/history' },
            { label: '分销中心', path: '/modules/distribution/pages/index' },
        ]
    },
    {
        label: '内容',
        key: 'content',
        items: [
            { label: '文章详情', path: '/modules/content/pages/article?id=', needSelect: true, selectType: 'article' },
            { label: '文章列表', path: '/modules/content/pages/article-list' },
        ]
    },
    {
        label: '专题',
        key: 'topic',
        items: [
            { label: '专题页面', path: '/modules/diy/pages/custom?id=', needSelect: true, selectType: 'topic' },
        ]
    },
]
