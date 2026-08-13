// uniapp/src/components/d-tabbar/tabbar.config.ts
//
// 自定义 tabbar 配置（保持与 pages.json 中 tabBar 顺序一致）
// icon / iconActive 字段为 d-icon 注册表中的图标名（见 components/d-icon/icons.ts）

export interface TabbarItem {
  key: string
  text: string
  pagePath: string
  icon: string         // d-icon 名（线性款）
  iconActive: string   // d-icon 名（实心款）
  badgeKey?: 'cart' | 'message'  // 红点来源（参考 spec §5.3）
}

export const tabbarItems: TabbarItem[] = [
  {
    key: 'home',
    text: '首页',
    pagePath: '/pages/index/index',
    icon: 'home',
    iconActive: 'home-fill',
  },
  {
    key: 'category',
    text: '分类',
    pagePath: '/pages/category/index',
    icon: 'category',
    iconActive: 'category-fill',
  },
  {
    key: 'cart',
    text: '购物车',
    pagePath: '/pages/cart/index',
    icon: 'cart',
    iconActive: 'cart-fill',
    badgeKey: 'cart',
  },
  {
    key: 'my',
    text: '我的',
    pagePath: '/pages/my/index',
    icon: 'user',
    iconActive: 'user-fill',
    badgeKey: 'message',
  },
]
