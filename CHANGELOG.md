# Changelog

本项目遵循 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.1.0/) 格式，版本号遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

## [Unreleased]

### Added
- 插件市场支持连接官网账号，按已购权益一键下载并安装 Shop 组件
- `plugin:pack` 将付费组件对应的 admin / PC / uniapp 页面打入 zip 的 `_frontend/`，安装时自动部署；支持 `--all`

### Changed
- PC 端头部 Logo / 网站名称改读系统配置 `site_logo`、`site_name`
- PC 端商品详情数量改为购物车同款「减 / 输入 / 加」
- PC 端我的订单恢复默认头尾，列表改为购物车式表格并展示全部商品
- PC 端意见反馈、账号安全去掉装饰化布局，对齐个人资料卡片表单风格
- 兑换订单并入积分商品插件（`points_product` 1.1.0），不再单独分发 `points_order`；后台仍保留商品与订单两个菜单
- 插件打包输出改为 `server/runtime/plugin-packages/`（`php think plugin:pack`），移除源码树中的 `server/plugin-packs/`
- 分销裂变、AI 商品助手从核心抽出为付费插件；三方同城配送与电子面单仍留在 Apache 核心
- 开源协议由 MIT 调整为 Apache-2.0：新增 `NOTICE` 版权与商标声明；核心免费开源，秒杀/拼团等付费组件不进公开仓
- 秒杀 / 拼团 / 抽奖 / 积分商品不再随仓分发，也不再随全新安装捆绑入册；请从官网市场下载 zip 后在「插件市场」上传安装
- 公开仓库改为 `yuandianxitong/ydshop`（GitHub / Gitee）

### Fixed
- PC 端确认订单添加地址弹窗主按钮被 Tailwind reset 冲成白底；改为主题色，并补齐 Naive 主按钮背景
- PC 端商品详情 Tab 选中色为 Naive 默认绿，与主题色不一致；改为跟随 `--color-primary`，并给 NConfigProvider 设置 primaryColor
- PC 端商品详情无法选择规格：公开详情返回 `specNames` / `spec_value_ids`，页面却读不存在的 `sku.attributes`；改为按 uniapp 同款字段组规格并回写选中 SKU。立即购买补上 `goods_id`，结算页规格名用 `spec_text`
- 插件市场本地上传 zip：`/tmp` 与站点不在同一文件系统时 `rename` 目录失败返回 500；改为跨盘复制并解压到 `runtime/`
- PC 端密码登录：未设置密码的账号（微信/验证码注册）调用 `password_verify(null)` 返回 500；改为提示使用验证码登录
- 短信验证码：消息模板未填短信模板 ID 时静默跳过仍返回「验证码已发送」；改为明确报错，不再假装已发送
- PC 端登录后刷新首页变回未登录：客户端启动时从 `pc_token` 恢复会话，并回写用户信息
- PC 端登录/注册成功后首页仍显示未登录：鉴权失败是 HTTP 200 + body.code=401，公开页请求会把刚写入的会话清掉；Pinia 空 payload 也会冲掉 token。改为 skipHydrate 持久化 token/userInfo，公开页 401 不再登出
- PC 端登录后 `/api/cart` 报 Token 验证失败 Wrong number of segments：请求头同时带了 `Authorization` 与 `authorization`，被合成 `Bearer jwt, Bearer jwt`；改为 Headers.set 只写一次，后端也兼容重复头
- PC 端登录后公开接口 `/api/article/list` 返回 Token验证失败：插件把后台 `article/list`（admin_full）和 C 端同名路由都注册进 `/api`，用户 token 被当成后台 token 校验；改为按当前应用只加载对应路由
- 插件 C 端路由核对：coupon / sign / 协议 / 满减无同路径被后台鉴权抢走；应用名未知或 scope 无法识别时不再把 admin 路由装进 `/api`

### Added
- 插件市场对接官网 `runtime=shop` 组件目录；付费组件启用受权益软门控
- 付费组件 zip 安装时按官方目录校验 SHA256 / RSA 签名，并写入本地 `MarketplaceEntitlement`
- 后台「系统管理 → 产品授权」：录入授权码、绑定域名、激活/校验/清除（对接官网 License API）



### Added
- 订单列表/详情支持修改收货地址（未发货非自提，更新 `address_snapshot`）与软删除已取消/已关闭订单（权限 `order.delete`，v2.16.2）
- 电子面单模版管理（`waybill_templates`）：支持物流公司 / ExpType 业务类型 / TemplateSize 模版样式 CRUD；快递鸟 1007 按模版传参；设置 Tab 校正 EBusinessID/AppKey 文案，并支持 C-Lodop 双端口探测打印
- 电子面单模版字段：邮费支付方式（现付/到付/月结）、快递员上门揽件（映射 IsNotice）、是否默认；常用公司 ExpType/TemplateSize 目录按快递鸟文档补齐；管理页改为配送模块一致的 seg Tab + table-card 列表
- v2.16.1：为已执行 v2.16.0 的库幂等补齐 `waybill_templates.need_pickup` / `is_default`
- 订单发货支持配送方式（物流配送 / 无需物流）与发货方式（手动填写 / 电子面单）；电子面单失败阻断发货并可选立即打印
- 定时任务常驻工人 `php think schedule:work`：支持 `start|stop|restart|status`，`--d` 守护进程后台运行（周期调用现有 `schedule:run-due`）；宝塔/裸机可一键启停，Docker 仍可用 supervisord。后台改任务后无需 restart，下一轮自动读库生效
- 手机号补绑通道 `POST /api/user/bind-mobile`（短信验证码，scene=`bind_mobile`）：微信端自动注册的账号没有手机号，公众号 H5 授权也无法获取手机号（该能力只存在于小程序 `getPhoneNumber`），故改为短信补绑；手机号被其他账号占用时直接拒绝，不做账号合并。uniapp 新增 `d-bind-mobile` 弹窗，入口在「编辑资料 → 手机」（未绑定时可点击），下单前若未绑定提示一次（可跳过，不阻塞下单）
- 框架数据库升级机制 `php think yd:update`：按 `database/updates/vX.Y.Z/` 版本目录依次执行未应用的 `update.sql` / `update.php`，已应用版本记录在 `system_upgrades` 表中，保证幂等、可断点续跑；升级器复用 `core/database/SqlRunner` 自动套用表前缀，安装/升级前缀处理行为一致
- 新增 `core/database/SqlRunner`（零框架依赖的 SQL 执行器：前缀改写 / 语句拆分 / 占位符替换），安装程序与 `yd:update` 共用；`schema.sql` 新增 `system_upgrades` 表，全新安装自动 seed 升级基线
- 插件数据库生命周期统一为 SQL：各插件自带 `database/install.sql`、`database/updates/vX.Y.Z.sql`、`database/uninstall.sql`（裸表名，经 `SqlRunner`）；`PluginManager` 为唯一运行时入口；`plugin_migrations` 只记录 semver；存量环境通过 tip 基线 adopt，不重复建表

### Fixed
- 优惠券全额抵扣后 `pay_amount=0` 的订单卡在待付款：下单事务内自动完成零元支付（`pay_type=free`）并触发 `order.paid`；`payment/create` 对零元单返回 `need_pay=false` 而非 400；uniapp 结账跳过支付弹窗
- `schedule:work` 每轮 tick 前关闭并重建 DB 连接，避免长驻进程踩 MySQL `wait_timeout` 出现 `MySQL server has gone away`；同时开启 `break_reconnect`
- 配送实时地图页高度按壳层（header + tabs + padding）计算，去掉多余 min-height，避免内容区外层滚动
- 秒杀活动列表始终显示「0 件」：列表改为 `withCount(['items'])` 输出 `item_count`；操作列加宽、商品管理弹窗加宽并对齐工具栏
- 拼团活动列表 SKU 列仅显示 `#id`：列表批量补全 `sku.goods_name` / `spec_text`
- 订单详情物流公司/单号为空：改为读取 `order.logistics.*`（与列表页一致），无单号时展示「无需物流」
- admin 弹窗内容超屏时头部/底部跟着滚：全局 `.el-dialog` 改为 flex 列布局 + body `overflow-y: auto`，头尾固定；遮罩层垂直居中并取消默认 `top/15vh`，避免整窗偏上需再滚遮罩（抽奖等长表单与门店新建一致）
- 会员等级页「普通会员」恒显示 0 人：列表 API 补算 `member_count`；将 `member_level_id=0` 的历史用户归入默认启用等级；注册后 `recalculateLevel`；会员列表附带 `member_level` 并支持 `level_id` 筛选
- 用户统计「注册来源」空白：`user_auths` 从未写入导致恒为空；改为按 `users.mini_openid/oa_openid/openid/mobile` 推断渠道并展示人数/占比，无数据时显示空态
- 商品属性更新失败 `Invalid JSON`：`options` 规范为 JSON 数组后再写入；表单预设值改为多选标签输入
- 商品属性所属分组下拉重名难辨：选项与列表列改为「分类名 / 分组名」
- 退款原因保存 422「原因文案必填」：校验/OpenAPI 字段由错误的 `reason` 对齐为库表与前端的 `name`

### Changed
- admin 订单详情页按设计体系改版：页头操作区 + 摘要 KPI + 双栏信息卡 + 通栏商品表，卡片/字号/金额色对齐会员详情与全局 token
- 生产定时任务推荐改用 crontab 每分钟执行 `schedule:run-due`（避免 `schedule:work` 长驻进程踩 MySQL `wait_timeout` 导致 `MySQL server has gone away`、后台无执行日志）；`schedule:work` 仍保留作可选方案
- 微信支付公钥模式完善（v2.15.0）：保留服务器 `*_path` 文件配置（不入库 PEM）；预下单请求声明 `Wechatpay-Serial: PUB_KEY_ID_...`；凭证装配抽离 `WechatPayCredential` 并强制公钥 ID 前缀校验；停用无用 `pay_wechat_api_key` / 历史 `pay_wechat_cert_path`
- uniapp 确认订单页申请发票 UI：改为与其他区块一致的卡片边距，输入框改为行内表单样式，移除「商品明细」内容字段（后端仍默认商品明细）
- uniapp 首页移除硬编码默认排版：进入 `pages/index` 先展示加载态，拉取 DIY 装修数据后再渲染；无装修/失败时展示空状态，下拉可重试
- uniapp 微信小程序快捷登录对齐授权弹窗：点击「其他方式登录」后弹出 `d-wechat-auth-popup`，按微信规范分步收集手机号（`getPhoneNumber`）/ 头像（`chooseAvatar`）/ 昵称（`type=nickname`）；头像与手机号互不阻塞（先选头像仅本地预览，有 token 后再上传），点「保存并登录」时校验缺项并 toast；后端 `wechatQuickLogin` / `wechatBindPhone` 返回 `need_profile`（昵称为空或「微信用户」或头像为空），资料齐全后再进首页
- 微信内置浏览器 H5 授权改为微信身份直登：`AuthService::wechatH5Login` 对首次访问用户按微信昵称头像自动注册并直接返回 token（不再返回 `need_login` 跳登录页），老用户昵称/头像为空时回填；uniapp 授权 scope 改为 `snsapi_userinfo`，token 过期（含 401）在微信内重新静默授权而非打断到登录页，主动退出登录后抑制自动登录
- `PluginMigrationRunner` 改为对 `PluginManager::applyUpdateSql` / adopt 的薄封装，不再执行 PHP/Phinx 迁移
- 插件安装/升级/卸载（含 `--purge`）与 CLI `plugin:install|uninstall|upgrade|enroll-bundled` 均走 `PluginManager` SQL 路径；`schema.sql` 仅保留框架核心与插件运行时表，插件业务表由插件安装包创建
- 会员「权益复核」页 UI 对齐账户资金等列表页；菜单/权限展示名由「历史权益复核」改为「权益复核」（v2.14.1）
- admin 会员详情弹窗加宽至 1280px，去掉顶部固定 `top`，改为 `align-center` 垂直居中显示；弹窗高度固定，切换 Tab 时仅内容区滚动、外框不再伸缩

### Fixed
- 修复订单列表「打印」误触发整页 `window.print()`：改为打开配货单对话框（print-js）
- 修复 admin 订单详情页渲染报错 `Ge.value.filter is not a function`：`GET :id/adjust-logs` 被写在裸 `:id` 之后，请求被详情接口吞掉返回对象；前端对 `adjustLogs` / `items` 增加数组校验
- 修复定时任务执行 `order:auto-cancel` 等命令时报错 `Object of class think\console\Output could not be converted to string`：`CronJobService::runCronJob` 误将 `Console::call()` 返回的 Output 强转字符串，改为 `fetch()` 读取缓冲输出
- 修复支付预下单失败后 `payment_orders` 卡在 `creating`，导致无法重试支付/取消：创建失败后查单确认渠道无交易则立即释放屏障；再次支付或取消遇 `creating` 时先渠道对账再继续（业务订单仍保持待支付）
- 修复微信配置/签名错误时查单也对失败、`creating` 无法对账释放，取消接口一直返回「支付订单正在创建」：对配置与 `SIGN_ERROR` 等确定性拒绝在取消时本地关闭屏障，创建失败时释放回 `pending`（网络超时仍保守保留）
- 修复 admin 编辑单规格商品改价不生效、反而多出一条 SKU：`GoodsSkuRepository::forceDeleteBySpuId` 误用 `delete(true)`，在 SoftDelete 下仍走软删 UPDATE 且 SET 值非法导致清理空操作，每次保存只增不删；改为 `removeOption('soft_delete')->delete()` 物理清理后再重建。单规格回填改为取最新 SKU，已污染数据再次保存即可自愈
- 修复 uniapp 浏览记录页空态重复展示「暂无浏览记录」：去掉页面级 `d-empty`，统一由 `d-list-loader` 处理空态/加载/没有更多
- 修复 uniapp 底部操作栏按钮贴底：`:style="{ paddingBottom: safeAreaBottom + 'px' }"` 覆盖了 CSS 内容底边距（安全区为 0 时变成 0）；改为 `padding-bottom: calc(内容间距 + env(safe-area-inset-bottom))`，覆盖地址编辑/列表、结算、订单详情、积分商城、地址选择器、分销提现等
- 修复 uniapp H5 地址编辑「所在地区」picker 滑动错位：H5 `picker-view` 按 indicator 高度换算选中项，选项行高（原 80rpx）与默认 indicator（约 34px）不一致导致省级索引偏移；统一为 40px 并级联回写 value
- 修复 uniapp `build:h5` 的 Vite 混用导入警告：理顺 `request` / `wechat-oauth` / `auth` 的静态与动态引用，避免循环依赖导致无法拆 chunk
- 修复 uniapp 装修分类导航（`diy-category-nav`）图标与标题横向并排：小程序端 `navigator` 不生效 `flex-direction: column`，改为内层 `view` 纵向排布
- 修复微信跨端账号无法打通：小程序 / 公众号 H5 / PC 扫码各登录通道在「已按单端 openid 命中用户」时不回写 unionid，导致开放平台绑定前注册的老账号 `users.unionid` 长期为空，同一微信号换端登录会被当成新用户重复注册。新增 `UserRepository::backfillUnionid`（条件 UPDATE，只在为空时写入，不覆盖已有值）并在 `loginByMiniApp` / `wechatQuickLogin` / `loginByWechatWeb` / `wechatH5Login` 统一回填；`WechatController::oauthCallback` 关联公众号时不再丢弃 unionid
- 修复公众号网页授权 scope 不生效：`OfficialAccountService::getOAuthUrl` 误用 `redirect($url, $scope)`（Socialite 该方法只接收回调地址，第二参数被静默丢弃），导致请求 `snsapi_base` 仍跳转 `snsapi_userinfo`；`getUserByCode` 统一走 `snsapi_base` 换 openid，再按 scope 补拉 `sns/userinfo`，公众号无 `snsapi_userinfo` 权限时降级为仅 openid 而不中断登录

### Removed
- 框架自身不再使用 think-migration：删除 `server/database/migrations/`（框架 71 个迁移）与 `server/database/seeds/`，代码生成器（后端与前端预览）不再产出迁移文件，框架数据库升级统一改用 `php think yd:update`
- 移除 `topthink/think-migration` 依赖与 `core/database/Migration.php`；删除全部插件 `plugins/*/database/migrations/*.php`（Phinx）遗留文件

## [2.14.0] - 2026-07-19

### Added
- admin 订单拆单/合单/改价（v2.14.0）：①改价——仅待付款且无活跃支付单订单可改商品单价/运费/整单优惠，改价前自动关闭旧支付尝试并在锁内二次断言（`PaymentService::assertBusinessPaymentClosed` 抽取复用），金额经 `OrderItemAmountAllocator` 重新行级分摊；②拆单——已支付未发货订单按商品行整行迁移或数量拆分为子单（分为单位比例拆分、余数归父单，父+子实付恒等于原实付断言），子单经 `payment_root_order_id` 扁平化血缘指向支付根订单，`OrderRefundService` 七处支付解析与退款额度校验升级为订单家族聚合防合计超退，merchant 子单由 `OrderSplitListener` 事务后幂等补建配送单；③合单——同用户多笔待付款同配送方式订单合并入最早存活单，被并订单走专用 `cancelForMerge` 通道直接落终态（跳过取消副作用重放，不回补库存不退优惠券），优惠券 `used_order_id` 改绑存活单，`auto_cancel_at` 重置完整支付窗口。新表 `order_adjust_logs` 审计流水（前后金额快照/操作人/原因）；`order_orders` 加 `parent_order_id`/`relation_type`/`payment_root_order_id`，`order_items` 加 `split_from_item_id`；权限 333-335 + 菜单按钮 815-817。守护：秒杀/拼团禁拆禁合、虚拟商品禁拆、已开发票禁拆、订单级禁全拆、合单需同配送方式（自提同门店）。admin 新增改价/拆单/合单三弹窗（分整数金额计算）、订单详情血缘关系卡片与改价记录时间线，订单列表页副标题恢复「下单、拆单、合单、改价与备注」

## [2.13.0] - 2026-07-18

### Added
- 三方同城配送平台接入（v2.13.0）：达达配送 / 蜂鸟配送 / UU跑腿 / 闪送 / 顺丰同城五平台统一适配器架构（`DeliveryPlatformInterface` + 值对象 + `DeliveryPlatformRegistry` + 5 Adapter），支持下单 / 取消 / 状态查询 / 回调验签。后台「配送设置」弹窗扩为 6 个 tab（每平台独立密钥配置 + 寄件门店信息 + 支付后自动发单开关），配送记录页新增「平台发单」「同步状态」「详情抽屉（骑手信息 + 轨迹时间线）」。`delivery_orders` 新增 9 列三方字段 + 新表 `delivery_order_tracks`（轨迹留痕）。发单用条件 UPDATE 抢占防并发重复下单且网络调用不持数据库锁；平台侧取消回落 `pending` 允许重新发单（商家主动取消才是终态）；三方单中间态禁止手工修改，由回调 / 同步驱动；回调统一入口 `POST /api/delivery/callback/{platform}`（验签失败不落库）。支付后自动发单走独立 `DeliveryAutoDispatchListener`（失败不影响支付主流程）。签名报文按各平台开放平台文档实现，上线前需沙箱联调核实
- 定时任务调度器（v2.13.0）：新增 `schedule:run-due` 命令 + 自研 5 段 cron 表达式匹配器（`CronExpressionMatcher`，11 个单测），supervisord 新增 `scheduler` 常驻进程每分钟轮询 `cron_jobs` 表执行到期任务（同分钟去重）。`CronJobService::ALLOWED_COMMANDS` 白名单从 7 条扩至 14 条（补 order:auto-cancel / order:auto-confirm / order:auto-review / pickup:scan-timeout / group-buy:expire / user-tag:refresh / user-group:refresh）——此前订单超时取消、自动确认收货、佣金结算等任务注册后从未被调度执行
- C 端「我的售后」（v2.13.0）：后端新增 `GET /api/order-refund`（列表）与 `GET /api/order-refund/{id}`（详情）；uniapp 新增售后列表 / 详情页，pc 新增售后列表 / 详情页（NSteps 状态时间轴），`approved` 状态展示退货物流表单调用既有 `POST /api/order-refund/{id}/logistics`，打通后端早已实现但三端均无 UI 的 `approved → returning → received → refunding → refunded` 退货退款全流程
- 拼团失败自动退款（v2.13.0）：`OrderRefundService` 新增系统发起整单退款 `systemRefundOrder`（复用 apply 的额度校验与建单逻辑，幂等），新增 `GroupBuyExpiredRefundListener` 监听 `group_buy.expired`，成团失败自动对已付订单发起 refund_only 全额退款
- PC 端结算配送方式选择（v2.13.0）：新增 `DeliveryTypeSelector` / `PickupStoreDialog` 组件与 `pc/api/store.ts`，结算页支持快递 / 同城配送 / 到店自提（按商品 `delivery_modes` 交集，逻辑对齐 uniapp），此前 PC 端硬编码 express 导致同城与自提不可用
- C 端物流轨迹（v2.13.0）：后端新增 `GET /api/order/{id}/tracking`（归属校验 + 复用 TrackingService），pc 新增物流轨迹页，uniapp 物流页从"订单时间戳合成伪轨迹"升级为真实快递鸟轨迹（失败降级为合成轨迹）

- AI 助手（v2.10.0）：商品编辑页 5 处字段内嵌 AI 按钮（标题/副标题/主图/场景图/商品详情），点击弹右侧 Drawer 流式生成 → 用户「采纳」才写回表单。后端新增 `AiImageDriverInterface` + `QwenImageDriver`（通义万相 wanx2.1-t2i-turbo，DashScope 异步任务 + 90s 同步轮询），生成图通过 `UploadFromUrlService` 拉到 `storage/uploads/ai/{Y-m-d}/` 本地落盘并返回项目域名下完整 URL，避免依赖通义万相 24 小时临时 URL。新增 `/adminapi/ai/text|text/stream|image` 三条路由 + 权限 `ai.text.run` / `ai.image.run`。前端 `<AiTrigger>` 通用组件按 scene 路由到 `AiTextDrawer` / `AiImageDrawer`。
- 帮助中心（v2.9.0）：新增 `help_categories` + `helps` 两张表 + 完整后端分层。admin 在系统管理下新增「帮助管理」L2 分组，含「帮助分类」「帮助列表」两个 CRUD 页面，帮助编辑页含富文本编辑器（@wangeditor）、状态草稿/发布、分类筛选、批量启停。C 端公开 3 接口（categories / list / detail）永不抛错；详情接口累加 view_count。PC 新增 `/help` 文档站（左分类树 + 右列表 + 顶部搜索）+ `/help/:id` 详情页（DOMPurify 富文本 + 相关帮助）。uniapp 新增 `modules/help` 子包含 list + detail 两页面。预置 5 个默认分类（购物问题 / 退换货 / 账号问题 / 支付问题 / 物流配送）。menus 新增 3 行（id=1730/1731/1732）。`pc_header_menu` 中「帮助中心」path 由 `/article?category_id=help` 守卫式 UPDATE 为 `/help`（自定义过的用户不动）。老用户走 `server/database/updates/v2.9.0/update.sql` 幂等升级
- 营销 - 广告 / 广告位管理（v2.8.0）：新增 `marketing_ad_positions` + `marketing_ads` 两张表 + 完整后端分层（Model / Repository / Service / Controller / Validate）。admin 在营销中心下新增「广告管理」L2 分组，含「广告位」「广告」两个 CRUD 页面，支持时间窗调度（start_at/end_at + status 三源头）、轮播开关、批量启停。C 端公开接口 `GET /api/ad/by-position/:code` 永不抛错（任何异常返回空数据，保证 UI 不崩）。新增 DIY `ad-slot` 组件类型，PC / uniapp 端都注册了 DiyAdSlot 渲染器（支持 swiper 轮播 + 链接三分支：外链 / 内链 / 无链接），DiyPageService 在 hydrate 时按 position_code 注入 ad_list。预置 3 个常用广告位（home_top_banner / goods_list_side_1 / cart_footer_promo）。menus 新增 3 行（id=1050/1051/1052），老用户走 `server/database/updates/v2.8.0/update.sql` 幂等升级

### Changed
- AI 任务表：`ai_tasks.type` enum 从 `analysis|restock|description` 调整为 `analysis|text|image`；历史 `description` 记录自动迁到 `text`，历史 `restock` 记录删除。`ai_prompt_templates` 新增 `scene` 列（title/description/detail/cover/gallery 五种细分场景）。`AiPromptTemplateRepository::findDefaultByType()` 替换为 `findDefault(type, scene)`。
- 后台菜单「文案生成」（`/ai/description`）重命名为「文案批量」并改路径为 `/ai/text`，作为无表单上下文 / 批量场景的兜底页（主入口已移到商品编辑表单的字段内嵌按钮）。`ai.description.run` 权限改名为 `ai.text.run`。

### Removed
- 智能补货推荐功能全量移除：删菜单 1150（`/ai/restock`）、权限 640 `ai.restock.run`、路由 `/adminapi/ai/restock`、`AiRestockController`、`AiService::restockRecommendation`、前端 `admin/src/views/ai/restock/`、`aiRestockApi`、`ai_prompt_templates` 中 `type='restock'` 默认模板，历史 `ai_tasks` 中 `type='restock'` 记录一并清除。

### Fixed
- 退货退款死胡同（v2.13.0）：admin 售后列表「确认收货」按钮条件从仅 `received` 放宽为 `returning`/`received`（后端 confirmReceived 本就两态皆收，前端写窄导致 returning 状态无操作入口，退货流程卡死）
- 发货管理页与配送方式割裂（v2.13.0）：发货列表固定只查 `delivery_type=express`，发货按钮与批量发货同步限定，避免对同城配送 / 自提订单误填快递单号；订单列表页副标题移除未实现的「拆单、合单、改价」表述
- cron_jobs 种子数据（v2.13.0）：移除 `clear:cache` / `clear:temp` 幽灵命令（console.php 无对应命令，注册即失败）；`refund:reconcile` 频率从每天一次修正为每 5 分钟（命令语义是查询静默超 10 分钟的退款，每天一次违背设计意图）；payment/finance/distribution/member 四个对账任务提频
- init.sql 补齐 `local_delivery_per_km_fee` 种子数据（此前仅存在于 v2.4.0 增量脚本，全新安装环境每公里费静默按 0 计费）
- `DeliveryStaffService` / `ExpressCompanyService` 批量删除的空 catch 改为记录日志，失败原因不再丢失；清理 uniapp 评价页调试 console.log
- 修复后台页面装修等动态列表在权限按钮被隐藏后触发 `insertBefore` 渲染异常：权限指令不再直接删除 Vue 管理的 DOM 节点，改为保留节点并同步可见性，同时支持权限动态授予与撤销。
- 打通用户消息闭环：Server 为注册、充值、订单状态、退款、反馈回复和公告发布生成幂等站内通知，消息详情与已读操作按当前用户可见范围校验；通知关联 ID 升级为 bigint 并增加事件唯一键，避免越权读取、伪造已读和事件重放产生重复消息。
- 补齐 PC 个人中心的消息通知、意见反馈、账户安全与公告详情页面；UniApp 消息详情改为按 ID 实时读取，反馈页支持提交、历史记录和平台回复，首页公告改用真实接口并可进入详情。
- 后台通用表格拆分“多选”和“批量删除”能力，移除帮助、广告、订单、代码生成及营销记录页为显示勾选列而设置的空回调，避免出现可点击但无业务动作的批量删除按钮。
- 资金链路按可审计证据收口：支付订单支持多次渠道尝试与指数退避对账，部分退款累计金额和失败状态可重试；历史充值成长值、订单会员权益、分销佣金及提现流水在证据不唯一或不完整时持久隔离，后台可提交外部账单依据结案，所有人工结论记录操作人、时间和金额且继续复用原幂等事件键。
- 分销提现只采信唯一且用户、类型、金额、事件键、余额连续性一致的冻结/退回流水；定时对账会发现已打款历史单的缺失、重复或损坏证据，管理员可在佣金、财务提现、分销提现或会员资金页面提交外部凭证结案，提现单持久保存资金事实；已打款单会原子补扣余额并将不足计入佣金债务，系统后续补冻结/退回会推进结论状态，避免重复入账或同一异常反复阻断。
- 分销佣金在 `order → order_items` 锁内先补齐存量订单行优惠/运费/实付分摊，再按商品优惠后净额计算；退款完成后幂等取消或冲正佣金，余额不足形成可审计债务，后续佣金优先抵债，并自动处理超额待审提现；默认启用每日结算任务，幂等补偿发布边界后丢失的完成事件佣金。
- 分销升级优先把已有真实结算流水的历史佣金选为 canonical，禁止 `identity_key=NULL` 重复行被手工、自动或历史补账再次发钱；历史已打款提现按 gross 扣除现有余额并把不足部分计入 `commission_debt`，未打款的无效旧申请自动驳回且不会凭空退余额。
- 后台佣金 KPI 改为服务端按完整筛选结果聚合：分销员去重、GMV 按订单去重、实际净入账及待结算金额/笔数不再随分页变化。
- 修复 Server 文件存储类与 ThinkPHP 门面同名导致的 PHP 致命错误；新人礼包按用户与礼包独立幂等发放，并按实际成功奖励写入日志。
- 统一 Server、PC、UniApp 的分页参数与 `{ list, pagination }` 响应读取，修复订单、收藏、评价、文章、商品、资金流水等列表翻页重复或仅显示首屏。
- 补全 PC 结算与营销链路：接入订单试算、真实运费、优惠券、秒杀/拼团上下文及地区编码；修复优惠券中心、秒杀/拼团价格和入口、退款路由/金额单位，并新增订单评价页。
- PC DIY 优惠券、秒杀、文章与视频组件改为消费 Server 注入数据；后台字典批量停用、排序保存与 CSV 导出改为真实功能，导入组件移除伪造预览/成功回退，UniApp 开发 Playground 不再进入生产分包。
- 电子面单按订单商品生成真实品名与总重量，快递鸟配置读取迁入 Repository，秒杀 API 控制器移除直接 Model 查询。
- uniapp 结算链路补齐优惠券选择、秒杀价和拼团参数传递；订单事务内校验并锁定优惠券，订单项记录秒杀商品 ID，取消订单时同步归还优惠券及秒杀库存。
- 修复电子面单发件详细地址字段不一致、快递公司名称未转换编码及小程序码接口漏注册路由；面单配置仅保留已实现的快递鸟服务商。
- 接入快递鸟即时物流轨迹查询并修复签名二次编码；同城配送配置移除尚未接入的达达/蜂鸟入口，仅保留可用的商家配送。
- 后台清除工作台、文件容量及多处硬编码统计/占位按钮；补齐余额与积分调整、分销商详情/团队/调级、签到记录定位和 AI 报告导出等真实交互。
- uniapp 底部导航购物车数量与未读消息角标改为读取真实接口数据。
- uniapp 登录页其他方式登录入口图标从微信改为灰色手机模型图标。
- uniapp 登录页左上角新增返回图标按钮，支持从登录页返回上一页；无上一页时回到首页。
- uniapp 登录回跳体验：购物车、我的及商品/结算/积分商城/抽奖等入口跳登录时统一携带 redirect，登录成功后返回原目标页；请求 401 不再用 `reLaunch` 清空页面栈；购物车和我的页游客进入不再强制跳登录，购物车仅在点击登录/结算/编辑动作时触发登录。
- uniapp 分类页游客进入时被跳转登录：`request.ts` 的 401 处理新增公开页白名单，首页/分类/商品/文章/帮助/部分营销公开页上的可选登录接口 401 只清 token 不强制 `reLaunch` 登录；分类页游客态不再主动请求购物车接口，避免 style2/style3 的购物车动作条触发登录拦截。
- 后台商品分类删除后仍显示：基类 `Repository::delete()` 改为先取 Model 实例再执行 `delete()`，确保 ThinkPHP SoftDelete 正确写入 `deleted_at`；商品分类仓储的详情、列表、树、by-ids 查询显式排除 `deleted_at`，删除失败时不再让前端误提示成功；广告位删除时级联广告也改为显式软删除。
- 微信支付 APIv3 初始化统一为微信支付公钥模式：SDK `certs` 改为使用 `pay_wechat_public_key_id` + `pay_wechat_public_key_path`，移除平台证书自动下载/缓存流程；回调验签改为校验 `Wechatpay-Serial` 必须匹配配置的 `PUB_KEY_ID_...`。支付配置新增微信支付公钥 ID / 公钥文件路径，并将 `pay_wechat_serial_no` 说明修正为商户 API 证书序列号，避免与旧平台证书序列号混用导致下单报 `Cannot found the serial(PUB_KEY_ID_...)`。
- 插件运行时：lottery 等插件首次入册时未运行 Phinx migrations，导致 `Repository` 实例化抛 "table doesn't exist"，被 `Service::resolveDependencies` 静默吞掉后表现为 "Typed property must not be accessed before initialization"。新增 `core/plugin/PluginMigrationRunner`，在 `PluginManager::install` 与 `plugin:enroll-bundled` 中扫描 `plugins/<code>/database/migrations/*.php` 并执行未记录的 migration；记录在 `plugin_migrations(plugin_code, version)`；遇到 "table already exists" 自动 adopt（兼容老安装站点的 4 个营销表）
- `PluginRegistry::grantToSuperAdmin` 只授权了 permissions，没授权 menus —— 后果：所有插件的菜单（lottery / coupon / article workspace 等共 13 条）都没出现在 super_admin 的 `role_menus`，前端 `/auth/info` 取不到它们，营销侧栏空白，`/plugin/{code}` 工作区 404。补全 menus 授权逻辑
- 抽奖发货页面 404：lottery `plugin.json` 中菜单 path `/marketing/lottery-shipment` 与前端文件夹 `admin/src/views/marketing/lottery-shipments/` 单复数不一致，改为 plural 对齐
- 公告管理 / 反馈管理（1710/1720）原挂在应用管理下不合理 —— 这两个不是插件，改挂到系统管理（parent_id=2）
- 应用管理 AppCard 点击逻辑：只对 workspace 插件（parent_menu=Application）能跳转，其他插件点击无反应。后端 `ApplicationService::list` 新增 `entry_path` 字段（workspace 插件返回 `/plugin/{code}`，其他返回 manifest.menus[0].path），前端 AppCard 用 entry_path 跳转
- `/plugin/{code}` 工作区入口 404：`generatePluginRoutes` 用 `findFirstValidPath` 找 workspace 树的第一个叶子作为 redirect 目标，但该函数会跳过 `meta.hidden=true` 的路由 —— 而 workspace 插件的所有菜单都是 hidden（这正是 workspace 划分的依据）。结果是 redirect 永远不会注册，访问 `/plugin/article` 走到 404。改用 workspace 专用的叶子查找器（不跳过 hidden）
- workspace 模式下 sidebar 第二列为空：`side.vue` 的 `secondRoutes` 对 workspaceMenus 也应用了 `!meta.hidden` 过滤，但 workspace 菜单全是 hidden（划分依据），结果全部过滤掉 → 进入插件后看到空白菜单。workspace 模式下不再做 hidden 过滤
- v2.7.0 新增菜单/插件用到的 lucide 图标未加入 UnoCSS safelist，渲染为空白（UnoCSS 扫源码看不到 DB 里的图标字符串，必须显式 safelist）。补全 10 个：`radio-tower`/`layout-grid`/`layout-template`/`file-text`/`package`/`percent`/`calendar-check`/`user-plus`/`users`/`zap`
- 渠道管理下「小程序」「开放平台」的子菜单丢失：原本 ChannelMiniAppConfig(id=500) / ChannelOpenConfig(id=550) 在用户的旧 init.sql 里存在，被合并丢失。两个 LAYOUT 没有 children 导致 side.vue 把它们渲染成 el-menu-item（看起来像是「公众号」的孙节点），用户感知为「小程序/开放平台都在公众号下面」。补回这两条子菜单 + 授权 super_admin
- v2.7.0 init.sql 漏掉了菜单重排：缺 Channel(id=16) 一级菜单、缺 AppInstalled/AppMarket 子菜单、应用管理 redirect 仍指向 `/app/region`、公众号/小程序/开放平台仍挂在应用管理下、区域管理未迁入物流配送、应用版本未迁入系统管理。补齐全部菜单结构，新装站点直接得到正确布局
- v2.7.0 update.sql 中 AppInstalled/AppMarket 用了 IDs 50/51，与 init.sql 中 SystemMenu 占用的 50/51 冲突，老用户升级会撞 PRIMARY KEY；改用 1820/1821，新增 super_admin 角色授权语句
- 底部导航保存接口 `/adminapi/system/config/batch-update` 报「配置不存在: tabbar_colors」—— 前端写两个 key（`tabbar_config` + `tabbar_colors`），init.sql 只预置了前者；`batchUpdateConfigs` 要求 key 必须先存在。init.sql 补 `tabbar_colors`（json 类型，默认 `{"text":"#94a3b8","active":"#4f6bff","bg":"#ffffff"}`）
- 底部导航保存后图标无法回选 + 导航项变成上百条空行：`admin/.../diy/tabbar/index.vue` 提交时对 json 配置值做了 `JSON.stringify`，而后端 `SystemConfigService::batchUpdateConfigs` 在 `config_type=json` 时会再 `json_encode` 一次，DB 落入双层编码字符串。重载时 `JSON.parse` 得到的是字符串而非数组，`v-for="(item, i) in items"` 当作字符迭代成百行空项。前端改为直接传原始对象（与 `diy/style` 等保持一致），并在 `loadConfig` 里加自愈逻辑（若 parse 结果是 string 再 parse 一次）兼容已被污染的数据；`ConfigBatchUpdateItem.config_value` 类型放宽到 `object | array`
- uniapp 启动时原生 tabbar 闪现：`App.vue:onLaunch` 把 `uni.hideTabBar` 排在 `await appStore.getConfig()` 之后，等配置加载完才隐藏导致首屏闪一下。改为同步先 hide 再 await；`pages.json` 的 `tabBar` 加 `"custom": true` 让微信小程序端直接跳过原生 tabbar 渲染（最彻底）
- 后台 `/admin/diy/tabbar` 配的底部导航在 uniapp 端不生效：`d-tabbar.vue` 只用静态 `tabbar.config.ts`，完全没读 `appStore.config.tabbar_config / tabbar_colors`。改为优先读后台配置（admin 字段 `{name, path, icon, activeIcon}`），按 pagePath 回退到内置 d-icon 名；icon 字段是 URL 则渲染 `<image>`，是图标名则渲染 `<d-icon>`；颜色用 `tabbar_colors.{text, active, bg}`，无配置时回退到 `theme_primary_color` / 默认灰；`onTap` 在 pages.json 静态 tab.list 内用 `switchTab`，否则 `navigateTo`
- init.sql 默认 `tabbar_config` 第 4 项 path `/pages/user/index` 与 pages.json 实际页面 `/pages/my/index` 不一致，对齐
- mp-weixin 启动报 `hideTabBar:fail custom Tabbar`：pages.json 加 `custom:true` 后微信不再渲染原生 tabbar，此时 `uni.hideTabBar` 调用会失败。`uniapp/App.vue` 给 `hideTabBar` 加 `fail` 回调静默吞掉错误（custom 模式下 fail 是预期的）；H5/App 端仍走真正隐藏路径
- 后台 tabbar 配置不下发到 uniapp：`/api/common/config` 端点白名单 `$diyPublicKeys` 只放了 `category_page_style_uniapp`，没暴露 `tabbar_config / tabbar_colors`，导致 `appStore.config.tabbar_config` 永远 undefined、d-tabbar 走静态回退。补全两个 key 进白名单
- `/admin/diy/page` 页面装修卡片 PC/移动端用同一张封面：拆分为 `page-cover-pc.png`（取自原 `diy-themes/pc-home-1.png`）和 `page-cover-mobile.png`（原 `page-cover.png` 重命名），卡片 `<img>` 按 `p.platform === 'pc'` 选择
- install 默认 `category_page_style_uniapp` 从 `style1` 改为 `style2`（更现代的分类页骨架）
- PC 端 `/pc/goods` 商品列表展示像静态数据：实际接口正常返回 73 件商品，但前端 `GoodsItem` 类型与后端 `GoodsSpuRepository::getPublicPageList` 实际 shape 不一致 —— 后端返回 `images[]/min_price/max_price/sales_count/subtitle` 聚合字段，前端却按 `cover/price/original_price/sales` 解析，导致 GoodsCard 的图片、价格、销量全部 undefined（被空值兜底显示为 ¥0.00），整页看起来像没渲染。同时分页 total 读的是 `res.data.total`，后端实际放在 `res.data.pagination.total` 下，分页器永远只显示第 1 页。修法：`pc/api/goods.ts` 重写 `GoodsItem` 与新增 `GoodsListResponse` 类型对齐后端实际 shape；`GoodsCard.vue` 改用 `images[0]` 作 cover、`min_price/max_price` 渲染价格区间、`sales_count` 显示销量；`pages/goods/index.vue` 读 `pagination.total`。同套字段供 DIY goods-grid 复用（`getByIds/getListByCategory/getListByTag` 字段同形），首页 DIY 商品组件一并修复
- PC 端 DIY 首页（`DiyRenderer.vue`）渲染时撑满整个屏幕宽度：外层只有 `class="diy-page"` 没任何 max-width 约束。补 `mx-auto max-w-1200px px-4`，与非 DIY 回退分支（`pages/index.vue`）的 1200 容器对齐
- PC 端 `GoodsCard.vue` 在 DIY 首页 goods-grid 数据缺价格字段时抛 `Cannot read properties of undefined (reading 'toFixed')`：把 `goods.price.toFixed(2)` 改成 computed `Number(price ?? 0).toFixed(2)`，对 DECIMAL 字段可能为 string 的情况一并兜底
- PC 端 401 拦截器白名单未考虑 `nuxt.config.ts` 的 `app.baseURL = '/pc/'`：`window.location.pathname` 永远是 `/pc/...`，但白名单正则匹配的是 `/`、`/goods` 这种应用内路径，导致游客进 `/pc/` 也被认定为受保护页面跳转 `/pc/login?redirect=%2Fpc%2F`。加 `BASE_PATH = '/pc'` + `stripBase()` 先剥前缀再走白名单；redirect 参数也存应用内路径（`/user` 而非 `/pc/user`），登录后 `router.push` 由 Nuxt 自动补回 baseURL，避免双前缀
- PC 端一进入页面就被强制跳转到登录页：`pc/composables/useRequest.ts` 的 HTTP 401 拦截器无条件 `navigateTo('/login')`，只要游客带过期 token 进入首页 / 商品页 / 文章页等公开页，onMounted 里任一接口返回 401（包括 DIY 首页接口、公告列表等）都会立刻把用户踢走。新增 `PUBLIC_PATH_PATTERNS` 白名单（首页 / login / register / goods / category / search / article / marketing），HTTP 401 与业务码 401 共用 `redirectToLogin()`：公开页只清 token 不跳转，受保护页跳 `/login?redirect=...`；补全 `pages/order/index.vue`、`pages/order/[id].vue` 缺失的 `middleware: 'auth'`（原本未登录进入会被接口 401 间接踢走且丢失 redirect 参数）；`pages/goods/[id].vue` 的加购 / 立即购买 / 收藏在未登录跳转时改为带 `redirect=fullPath`，登录后能回到原商品页
- `/admin/diy/style` 商城风格主题色在 mp-weixin 端不生效：`theme-vars.ts` 的 mp 分支只把 themeVars 写到 globalData/storage，没真正应用到样式；而 `tokens.scss` 里 `:root, page { --color-primary: ...; }` 与 `pages.json` 的 `navigationBarBackgroundColor` 都是**编译期**写死，运行时无法被 JS 覆盖。新增 `composables/useThemePageStyle.ts`：把 `appStore.themeVars` 拼成 `--color-primary:#xxx;...` 串，并派生 `navBg`/`navText`（按主色亮度算白/黑字）；composable 内部 `onShow` + `watch(navBg)` 调用 `uni.setNavigationBarColor` 直接同步原生 nav bar（比 `<page-meta>` 在 vue3 setup 下的属性绑定时序更可靠 — page-meta 经常滞后于 nav 首次渲染）。在 4 个 tab 页加 `<page-meta :page-style>` 注入 CSS 变量 + `useThemePageStyle()` 触发 nav 同步。watch 兜首屏：首次 onShow 时 getConfig 还没 resolve，themeVars 仍是默认；config 到位后 watch 触发补一次 setNavigationBarColor

## [2.11.0] - 2026-05-23

### Added
- PC 首页 DIY 预设（Banner + 5 列魔方）：系统安装时为 `diy_pages` 表 PC `home` 页（id=2）预置完整内容 —— 1 张主图 banner + 5 张一字排开的图片魔方 + 热销 / 新品 goods-grid 分区。新装站点首次访问 PC 端 `/` 即可看到完整商城首页布局，无需后台再点装修
- `image-cube` 组件新增 `marginTop` / `marginBottom` props（默认 16px），PC + admin preview 两端同步实现，admin 「图片魔方」面板新增「上外边距 / 下外边距」滑块控件（0-80px）
- PC 端 `DiyImageCube` 组件：`pc/components/diy/DiyImageCube.vue` 用 CSS Grid 实现，与 uniapp 端 `diy-image-cube.vue` 共享同一份 props 契约（`rows / cols / gap / borderRadius / items[].image,link,rowStart,colStart,rowSpan,colSpan`），后台 `/admin/diy/page` 编辑器无需改动即可同时驱动两端渲染。link 跳转：外链 `window.open(_blank)`，内链 `router.push()`
- 6 张系统预设图片入仓：`server/public/storage/diy-defaults/home/pc/{banner/01.png,cube/01-05.png}`。`/storage/` 路径由 web 服务器直接 serve，无需安装时复制流程
- `DiyRenderer.vue` `componentMap` 注册 `image-cube` 类型

### Changed
- `init.sql` `diy_pages` id=2 PC home 行：components 由 6 个空内容占位组件升级为完整预设；老用户走 `server/database/updates/v2.11.0/update.sql` 升级，**该 SQL 带「未自定义」幂等守护**（仅当 components 为 NULL / 空数组 / 等于 v2.10.x 默认占位结构时才覆盖），已自定义过 PC 首页的用户不受影响

## [2.10.1] - 2026-05-23

### Fixed
- `/adminapi/ai/config` PUT 接口形状与 GET 不对称：GET 返回 `{ drivers: { openai: {...}, deepseek: {...} } }` 嵌套，PUT 却从 `$data[$driver]` 顶层平铺读取，导致前端发的 `{ drivers: {...} }` payload 所有驱动配置 `isset` 全部 continue，`system_configs` 一行都没写就返 200 「保存成功」。表现：用户在 `/admin/ai/config` 填 DeepSeek API Key 点确定，再次打开密码框仍是空，后续 AI 调用 401。修法：PUT 优先读 `$data['drivers'][$driver]`，与 GET 形状对称；保留顶层平铺作为兼容回退
- 通义万相默认模型名拼错：`wanx-v2.1-text2image-turbo` 不是 DashScope 合法 model ID，调用直接 HTTP 400 `Model not exist`。正确名是 `wanx2.1-t2i-turbo`（无 `v` 前缀、`t2i` 而非 `text2image`）。已修：`QwenImageDriver::$defaultModel`、`init.sql`、`v2.10.0` migration、`updates/v2.10.0/update.sql`、对应 README。已升级到 v2.10.0 的站点需手动跑：`UPDATE system_configs SET config_value='wanx2.1-t2i-turbo' WHERE config_key='ai.qwen.image_model';`
- `<AiTrigger>` 组件 3 个根节点（el-tooltip + AiTextDrawer + AiImageDrawer）导致父组件传的 `style="margin-left: 16px"` 被 Vue 3 静默丢弃 —— 多根组件不会自动透传 attrs。表现：商品名称/副标题输入框与 AI 图标之间没间距。修法：用 `<span class="ai-trigger-root">` 包成单根，drawers 走 Teleport 不影响布局

## [2.7.1] - 2026-05-20

### Added
- PC 端头部导航 + 底部菜单后台化：新增 `/admin/diy/pc-menu` 双 Tab 编辑器，支持头部菜单（label/path）拖拽排序、底部列上下移 + 列内链接拖拽排序、版权文本（`{YEAR}` 占位符）。后端复用 `system_configs` 两个 json key（`pc_header_menu` / `pc_footer_config`），`CommonController::$diyPublicKeys` 白名单追加；PC 新增 `useAppStore` 集中拉配置 + `useHeaderMenu()/useFooterConfig()` composable 暴露 reactive 配置，AppNavMenu / AppFooter 改读 composable，配置缺失 / 接口失败 / 数据脏数据自动 fallback 到内置 DEFAULT_HEADER / DEFAULT_FOOTER（与改造前 UI 一致）。menu id=1095 (DiyPcMenu)，老用户走 `server/database/updates/v2.7.1/update.sql` 幂等升级
- PC 端头部下方新增导航行 + 首页 banner 左侧浮动分类面板
  - `AppNavMenu.vue`（新）：固定在 layout AppHeader 下方，左侧 210px 红色「全部商品分类」砖位，右侧硬编码 8 项横向导航（首页/热销榜单/新品推荐/好物优选/限时秒杀/领券中心/商城资讯/帮助中心），active 状态按 `route.path.startsWith` 判定
  - `HomeCategorySidebar.vue`（新）：拉 `/api/category/tree`，取顶层前 8 个分类，半透明黑底（rgba(0,0,0,.55) + backdrop-blur）浮在首页 banner 左侧；每项 hover 右侧弹出二级分类白底飞出面板；点击跳 `/goods?category_id=xxx`。仅首页渲染，其他页面不出
  - `DiyRenderer.vue` 拆分 `firstComponent / restComponents`，把第一个 DIY 组件（首屏 banner）包在 `relative` 容器里并暴露 `#firstOverlay` slot；`pages/index.vue` 在 slot 里注入 HomeCategorySidebar，sidebar `position: absolute; height: 100%` 自动跟随 banner 实际高度（460px），banner 保持 1200 全宽不变，sidebar 半透明覆盖左侧 210px
- PC 端整站头部改版（电商风）：拆分为两层 + 浮动入口
  - `AppTopBar.vue`：黑色顶栏（h-8），左侧公告跑马灯（拉 `/api/announcement/list`，CSS animation 35s 一周期，hover 暂停；列表无缝拼两遍做循环），右侧未登录显示「登录 | 注册」、已登录显示「Hi · 昵称 | 我的订单 | 个人中心 | 退出」；进入站点若 token 存在但 userInfo 为空会自动拉 `/api/auth/info` 填充昵称
  - `AppHeader.vue` 重写：左侧 Logo「元点Shop」，右侧 360px 主题色描边搜索框（回车 / 点搜索跳 `/goods?keyword=...`）+ 购物车入口；购物车角标基于 `cartApi.getCartList().length`，监听 `userStore.isLoggedIn`（退出归零）+ `route.fullPath`（加购后跳转刷新）；未登录不发请求
  - `BackToTop.vue`：右下角浮动按钮，滚动 > 400px 显示，平滑滚动到顶；passive 监听 + `onBeforeUnmount` 解绑
  - `layouts/default.vue` 串成 TopBar → Header → main → Footer → BackToTop
- `/admin/diy/template` 模板中心「新建模板」功能落地：原按钮只 toast「即将上线」，现在打开 `CreateTemplateDialog`，支持名称 / 平台 / 页面类型 / 封面上传，并可三选一作为初始内容来源 — 空白 / 基于已有模板（同平台同类型筛选）/ 基于已有页面（同平台同类型筛选）。提交时拉取来源 detail 复制 components 数组，调 `POST /adminapi/diy/template`（is_system 强制 0，仅创建用户自定义模板）

### Changed
- `PluginManager::install` 与 `plugin:enroll-bundled`：将 DDL（`install.sql` + Phinx migrations）移出 `Db::startTrans` 事务（MySQL DDL 自动 commit，包在事务里会误导回滚语义）
- 安装默认移动端首页（diy_pages id=1）重做：新增轮播图素材 3 张、10 项分类导航（限时秒杀/拼团/分类/签到/浏览记录/抽奖/积分/分销/订单/优惠券）带预设链接、优惠券组件、限时秒杀组件、2×2 图片魔方；素材入 `server/public/storage/diy-defaults/home/{banner,category,cube}/`，新装站点即得完整首页骨架

## [2.7.0] - 2026-05-16

### Added — 应用管理 / 插件运行时重构

#### 插件运行时核心
- 新增 `core/plugin/PluginManifest`：plugin.json 解析 + 校验（code/version/category SemVer 与字段约束、display_mode 推断）
- 新增 `core/plugin/PluginManager` 完整生命周期：boot/install/uninstall/upgrade/enable/disable + applications 表过滤 + plugins_cache.php 启动加速
- 新增 `core/plugin/PluginRegistry`：菜单/权限注入与孤儿清理、super_admin 自动授权、依赖冲突检测
- 新增 `core/plugin/PluginInstaller`：zip 解包 + 平铺单层目录包裹 + 落地到 plugins/<code>/
- 新增 `core/plugin/PluginException` 与错误码常量

#### 11 个 bundled 插件改造
- 营销九件套全部迁入 `plugins/`：coupon / full_discount / flash_sale / group_buy / lottery / new_user_gift / sign / points_product / points_order
- 内容资讯两件套迁入 `plugins/`：content_mgmt（协议管理）/ article（文章资讯）
- 每个插件提供完整 plugin.json（code / category / parent_menu / palette / requires / menus / permissions / routes）
- 目录命名统一为下划线：`flash-sale → flash_sale`、`group-buy → group_buy`、`full-discount → full_discount`，命名空间同步规范化（fullDiscount → full_discount）

#### 后端 lifecycle API
- `GET /adminapi/application/list`：返回已安装应用 + 磁盘 plugin.json 推断的 `has_upgrade`
- `GET /adminapi/application/logs`：审计日志分页查询
- `DELETE /adminapi/application/:code`：卸载（拒绝带反向依赖的卸载）
- `POST /adminapi/application/:code/upgrade|enable|disable`：升级 / 启停
- `POST /adminapi/market/upload`：本地 zip 包上传安装
- 命令行 `php think plugin:enroll-bundled`：扫描 plugins/ 一键入册到 applications 表（幂等）

#### 前端应用管理
- `/app/installed` 已安装应用页：KPI 三栏 + 按 category 分组栅格 + 关键字搜索 + AppCard 操作下拉（升级 / 启停 / 卸载）
- `/app/market` 应用市场页：本地 zip 拖拽上传 + 安装进度 + 远程市场占位
- 工作区模式：parent_menu=Application 的插件菜单从主侧边栏隐藏；点应用卡片或访问 `/plugin/<code>` 进入工作区，侧边栏二级菜单切换为该插件子菜单 + 「← 返回应用列表」按钮
- API client `admin/src/api/application.ts`：typed 接口与上传进度回调

#### 后端响应字段
- `/auth/info` 响应新增 `workspace_menus: Record<plugin_code, MenuTree[]>` 字段
- `MenuRepository::getPartitionedFrontendRoutes`：一次查询，把隐藏的工作区菜单按 plugin_code 分组返回

#### 菜单重排
- 「渠道管理」新一级菜单（id=16，sort=70）：公众号 / 小程序 / 开放平台归位
- 区域管理（id=1800）从应用管理迁到「物流配送」下
- 应用版本（id=1810）迁到「系统管理」下
- 应用管理（id=8）下新增「已安装应用」「应用市场」两个固定子菜单
- 一级菜单 sort 重新分配：营销 50 / 应用 60 / 渠道 70 / 装修 80 / AI 90

### Changed
- `init.sql` 移除 11 个插件的硬编码菜单 + 旧名权限（marketing.* / agreement.* / article.*），改由 `plugin:enroll-bundled` 注入
- 安装器（`public/install/install.class.php`）通过 `proc_open` 调 `php think plugin:enroll-bundled`，新装站点自动入册
- `PluginRegistry::syncMenus` 的菜单查找改为「按 name + (plugin_code IS NULL OR = 当前 code)」，老 DB 升级时收编已有行，新装时插入

### Database
- 新增表：`applications` / `application_install_logs` / `plugin_migrations`
- `menus` / `permissions` 表新增 `plugin_code` 列与索引
- 详见 `server/database/updates/vNext/update.sql`

### Migration
- 老用户升级流程：备份 DB → 执行 `update.sql` → `php think plugin:enroll-bundled` → 重启 PHP-FPM
- 完整指引：`server/database/updates/vNext/README.md`

### Tests
- 新增 `tests/plugin/` 测试套件：PluginManifestTest（6）/ PluginBootTest（2）/ PluginRegistryTest（7）/ PluginEnrollBundledTest（2）/ PluginInstallerTest（2）/ PluginManagerLifecycleTest（1）
- PluginManifest + PluginInstaller 用例可在不依赖数据库的环境下通过；其余依赖数据库

## [2.6.0] - 2026-05-09

### Added — 抽奖活动插件全闭环 + 浏览记录 + DIY 链接补完

#### 抽奖活动（lottery 插件）
- 后端新建 `server/plugins/lottery/`：plugin.json、4 张表（`marketing_lottery_activities` / `_prizes` / `_records` / `_shipments`）、Service、RewardDispatcher、LotteryShipmentService、Controllers、Phinx 迁移
- API 端点：
  - `GET /api/marketing/lottery/active`（公开：进行中活动列表）
  - `GET /api/marketing/lottery/detail/:id`（公开：活动详情含奖品）
  - `GET /api/marketing/lottery/quota/:id`（登录：剩余次数）
  - `POST /api/marketing/lottery/draw/:id`（登录：抽奖核心）
  - `GET /api/marketing/lottery/my-records`（登录：我的抽奖记录）
  - `POST /api/marketing/lottery/shipments/claim`（登录：实物奖品填地址）
  - `GET /api/marketing/lottery/shipments`（登录：我的实物奖品列表）
  - `GET /api/marketing/lottery/shipments/:id`（登录：发货单详情）
  - `POST /api/marketing/lottery/shipments/:id/confirm`（登录：用户确认收货）
- Admin 端点：
  - 抽奖活动 CRUD `/adminapi/marketing/lottery`、`GET :id/records`、`GET coupons`
  - 发货管理 `/adminapi/marketing/lottery/shipments`（list/detail/ship/cancel）
- 抽奖核心：事务 + `FOR UPDATE` 锁活动/奖品；权重抽取；库存原子递减；并发抢空降级到「谢谢参与」；优惠券即时发放（写 `marketing_coupon_users` + 增 `used_count`）；积分即时发放（写 `points_logs` + 更新 `users.points/total_points`）；type=4 实物推迟到用户填地址再建 shipment
- 实物发货闭环：用户填地址 → admin 录单号 → 用户确认收货；超期未填地址自动 `expired` 并退奖品库存（lazy sweep with `FOR UPDATE` 防并发重复退）；admin 取消（pending/shipped）也退库存（不退积分）
- 主项目 `app/model/user/PointsLog`：新增 `TYPE_LOTTERY_PRIZE` / `TYPE_LOTTERY_DEDUCT` 常量与 TYPE_MAP 文案
- 管理后台 `admin/src/views/marketing/lottery/`：列表 + 弹窗表单（8 格九宫格奖品 + 类型 1/2/3/4 + 优惠券下拉 + 图片上传 + 地址有效天数）+ 中奖记录抽屉
- 管理后台 `admin/src/views/marketing/lottery-shipments/`：跨活动发货管理页 + ShipDialog（接 `expressCompanyApi.getOptions()` 提供快递公司下拉，支持 allow-create + 取消退库存）
- uniapp `uniapp/src/modules/marketing/pages/`：lottery / lottery-detail（CSS 九宫格 + 转动动画 + 实物中奖时显示"立即领取"双按钮 + d-address-picker）/ lottery-records（type=4 显示"查看发货"链接）/ lottery-shipments / lottery-shipment-detail（含确认收货 + 复制单号）

#### 浏览记录
- 新增表 `member_browse_histories`、Service / Repository / Controller / Route
- uniapp 商品详情页自动埋点；会员中心新增「浏览记录」入口

#### DIY 链接
- DIY 编辑器营销组追加「抽奖活动」入口；用户中心组追加我的订单 / 签到 / 浏览记录 / 分销中心

#### 数据库
- 新增表 `member_browse_histories`、`marketing_lottery_activities`、`marketing_lottery_prizes`、`marketing_lottery_records`、`marketing_lottery_shipments`
- `marketing_lottery_activities` 增加 `address_expire_days` 列
- 新增权限 550-554（`marketing.lottery.*`）+ 菜单 1056-1059
- 新增权限 555-558（`marketing.lottery_shipment.*`）+ 菜单 1170-1172（避开 1080 已被 DIY 占用）
- 老用户升级使用 `server/database/updates/v2.6.0/update.sql`

#### Composer
- `composer.json` 新增 PSR-4 命名空间 `plugins\\lottery\\`，升级时需 `composer dump-autoload -o`

#### 路由别名
- 此前为 `flash-sale` / `group-buy` 插件添加前端兼容别名（`/active`、`/:id`），并入主线

## [2.5.0] - 2026-05-07

### Added — 会员详情全闭环 + DIY 编辑器视觉升级 + 门店地址结构化

#### 数据库
- 新增表 `user_operation_logs`：用户操作日志统一聚合（登录 / 资产 / 订单 / 等级 / 客服 / 资料）
- 新增表 `member_remarks`：会员运营备注（软删除）
- v2.5.0 升级脚本一并合入此前 vNext 三批：`vNext-spec-template`（规格模板）、`vNext-distribution-level`（三级佣金 + 等级 CRUD）、`vNext-user-tag-rules`（标签规则引擎 + cron 自动打标）
- 新增权限 403-406（`member.sms` / `member.coupon` / `member.remark` / `member.address.update`）+ 菜单 912-915

#### 后端 (server)
- `OrderOrderRepository` 增加 2 个聚合方法：
  - `getUserPreference(userId)`：90 天消费趋势 / TOP 5 品类 GMV / 支付方式占比 / 24h 时段热力 / 90 天退款率 / 复购率
  - `getUserLifecycleAnchors(userId)`：首单/末单/已完成订单数（生命周期推导）
- `MemberAddressRepository` 增加 `districtDistribution`、`countByUserId`、`setDefault`
- `UserManageService` 扩展：
  - `getUserDetail` 附加 `tags / level_name / address_count / remarks_count`
  - 新增 `getUserPreference / getUserLifecycle / getOperationLogs / updateProfile`
  - `adjustBalance / adjustPoints` 触发 `user.balance.adjusted` / `user.points.adjusted` 事件
- 新增 Service：`UserOperationLogService`（统一封装 icon/tone）、`MemberRemarkService`、`MemberSmsService`（复用 MessageService）、`AdminCouponIssueService`
- 新增 Models：`UserOperationLog` / `MemberRemark` / `MarketingCoupon` / `MarketingCouponUser`
- 新增 4 个 Listener，写入 `user_operation_logs`：登录、支付成功、余额调整、积分调整
- `AddressBookService` 增加 `create / update / setDefault`，对应 `AddressBookController` 3 个端点
- `MemberController` 新增端点：`preference / lifecycle / operationLogs / update / remarks(CRUD) / coupons / issueCoupon / sendSms / smsTemplates / issuableCoupons`

#### Admin 前端 (admin)
- 重写 `UserDetailDrawer.vue`：删除全部静态 mock（消费趋势 / 品类 / 支付 / 时段 / 地区 / 优惠券 / 备注 / 生命周期 / 操作日志），替换为真实接口 + 懒加载
- 新增 4 个子弹窗组件：`SendSmsDialog` / `IssueCouponDialog` / `EditProfileDialog` / `AddressFormDialog`
- 头部「发短信 / 送优惠券 / 编辑资料」三个按钮接通后端
- 订单 Tab 搜索筛选（关键字 / 状态 / 时间范围）接通过滤
- 标签 / 生命周期 Tab：「所属用户分组」改为基于已打标签透出；「生命周期轨迹」+「下一阶段建议」接真实推导
- 地址 Tab：新增 / 编辑 / 设为默认 / 删除 全部接通；「收货地区分布」改为真实聚合
- 操作日志 Tab：分类计数 + 分页 + 真实日志条目（按 6 个 category 过滤）
- `api/member.ts` 扩展 14 个新方法 + 7 个 TS 类型；`api/address-book.ts` 增加 `create / update / setDefault`

### Added

#### 后端 (server)
- AI 配置新增 `enabled_drivers` 字段持久化：`AiConfigController::getConfig` 返回当前启用的驱动列表，`updateConfig` 接收并保存到 `system_configs` 的 `ai.enabled_drivers`（JSON 数组）。
- 财务模块新增 5 个 Excel 导出端点：
  - `GET /adminapi/user/balance-logs/export`（余额流水）
  - `GET /adminapi/user/points-logs/export`（积分流水）
  - `GET /adminapi/finance/transactions/export`（资金流水）
  - `GET /adminapi/finance/overview/export-month`（财务月报）
  - `GET /adminapi/distribution/withdrawal/export`（提现单）
- 引入 `phpoffice/phpspreadsheet ^2.3` 作为 xlsx 生成依赖
- 公共服务 `app\service\common\ExcelExportService::streamXlsx`，含 50000 行硬上限
- 5 个独立 export 权限（IDs 463-467）

#### Admin 前端 (admin)
- 5 个 finance 页面（balance-log / points-log / transaction / withdrawal / overview）的"导出"按钮接通真实 xlsx 下载
- 新增公共 composable `useExport`（fetch + blob 下载方式）

#### 后端 (server)
- 会员模块新增 2 个 Excel 导出端点：
  - `GET /adminapi/member/address/export`（地址簿）
  - `GET /adminapi/member/fund/export?tab=bal|rech|wd`（账户对账单，按当前 tab dispatch）
- 2 个独立 export 权限（IDs 468-469）
- `MemberRechargeOrderRepository::getAllForExport`、`MemberAddressRepository::getAllForExport`

#### Admin 前端 (admin)
- member-list 详情按钮改为 Element Drawer 显示用户信息（取消 console.log 占位）
- address-book 与 account-fund 导出按钮接通真实 xlsx 下载
- user-group "分组模板"按钮新增模态选择 5 个预设模板（近 30 天活跃 / 高消费 / 沉睡 / 新注册 / 高积分），选模板后预填新建分组表单

#### 后端 (server)
- 新增 3 个 Excel 导出端点：
  - `GET /adminapi/delivery/order/export`（配送记录）
  - `GET /adminapi/delivery/staff/export`（配送员花名册）
  - `GET /adminapi/distribution/commission/export`（佣金记录）
- 3 个独立 export 权限（IDs 470-472）
- `DeliveryOrderRepository::getAllForExport`、`DeliveryStaffRepository::getAllForExport`

#### Admin 前端 (admin)
- distribution/commission 页"详情"按钮新增 WithdrawalDetailDrawer，
  显示提现完整字段（无新接口，直接复用 row 数据）
- delivery/order、delivery/staff、distribution/commission 三页"导出"按钮接通真实 xlsx 下载

#### 后端 (server)
- 新增财务模块"积分规则总览"端点 `GET /adminapi/finance/points-rules`，聚合返回注册赠送 / 签到 / 消费等级 3 类规则当前配置
- 新增权限 `finance.points.rules`（ID 473）+ 菜单项 ID 1245

#### Admin 前端 (admin)
- 新建页面 `/finance/points-rules`：readonly 总览，3 张卡片显示规则配置值，各带"去配置"按钮跳转到对应原配置页
- `finance/points-log` 页"积分规则"按钮接通跳转到上述新页（原 handleNotImplemented 占位）

#### 后端 (server)
- `UserGroupService::refreshGroup` 支持排除条件（`exclude: true`），按业界 CRM 通用语义：包含条件按 AND/OR 组合，排除条件始终 AND-NOT 踢除
- 新增 `negateOp` 私有方法支持 6 种操作符取反（`=`/`!=`/`>`/`>=`/`<`/`<=` 两两对偶，预留 `in`/`not in`）

#### Admin 前端 (admin)
- user-group 编辑表单每行条件新增"包含 / 排除"segmented 切换（`el-radio-button`）；移除原"添加排除条件"占位按钮
- 现有 5 个分组模板向前兼容新增 `exclude: false` 字段
- 列表卡片预览处对排除条件加"非 "前缀，便于一眼区分

#### 数据库
- 新表 `user_login_logs`（id / user_id / login_at / login_ip + 2 索引）用于会员留存计算

#### 后端 (server)
- `MemberStatisticsService` + `MemberStatisticsRepository` 新建（重构 `MemberStatisticsController` 5 个旧端点全部走三层）
- 新增端点 `GET /adminapi/member/statistics/source-distribution`：基于 user_auths.platform 真实统计 5 种注册渠道占比
- 新增端点 `GET /adminapi/member/statistics/retention-matrix`：8 周 cohort × D1/D3/D7/D14/D30 留存矩阵
- `UserLoginListener` 双写日志 + 更新 `users.last_login_time` / `last_login_ip` / `login_count`，修复 DAU/MAU 端点恒返回 0 的隐藏 bug（之前 `last_login_time` 永远为 NULL）

#### Admin 前端 (admin)
- member/statistics 页"注册来源"条状图删除 5 个硬编码 label + level-distribution 占位回退，对接真实端点
- member/statistics 页"留存矩阵"表格删除 5 行 mock 数据，对接真实端点；表头从"7 日留存矩阵"改为"留存矩阵"
- member/statistics 页趋势图删除"月活"伪折线（系数 0.6 + 偏移 20 占位序列）

#### 数据库
- 新表 `delivery_exception_tickets`（异常工单：ticket_no/type/status/title/description/evidence + 处理跟踪字段 handled_by/handled_at/resolution_note）

#### 后端 (server)
- 新增 6 个 delivery exception ticket 端点（list/show/create/update/transition/delete），路径 `/adminapi/delivery/exception-tickets[/:id[/transition]]`
- 4 个新权限（IDs 474-477：`delivery.exception.{list,create,update,delete}`）
- 菜单 ID 1360 + 3 个子按钮（1361-1363：新增/处理/删除）
- DeliveryExceptionTicket{Model, Repository, Service, Controller, Validate} — 标准三层架构

#### Admin 前端 (admin)
- 新建页面 `/delivery/exception`：tabs+列表+创建 dialog+详情 drawer+状态流转
- 类型定义 DeliveryExceptionTicket / Type / Status / Query
- API client 加 deliveryExceptionTicketApi 对象（6 方法）

#### 数据库
- delivery_orders 加 dest_lat/dest_lng 字段（geocoded 收货地址坐标）+ idx_dest_geo 索引
- delivery_staff 加 current_lat/current_lng/location_updated_at 字段（埋数据基础，留 follow-up "配送员端"）
- system_configs 新增 4 条 amap.* seed（web_api_key / js_api_key / js_security_code / default_city）

#### 后端 (server)
- 新建 AmapService（geocode + batchGeocode REST 封装，支持 chunk 切分 10/批，AMap key 未配置优雅降级）
- 新建 MapDataService（getMapConfig + getOrdersForMap with lazy geocode + 缓存写回）
- DeliveryOrderRepository::getForMap + ::updateGeoCoords 新方法
- DeliveryOrder Model 加 dest_lat/dest_lng 到 fillable + float cast
- 2 新端点 GET /adminapi/delivery/map/{config,orders}（沿用 delivery.order.list 权限）
- 菜单 ID 1370 /delivery/map（实时地图）

#### Admin 前端 (admin)
- 引入 @amap/amap-jsapi-loader 依赖
- 新建页面 `/delivery/map`：高德地图 + 订单点位 marker（6 状态着色）+ InfoWindow + 状态/时间筛选
- 类型定义 DeliveryMapConfig / DeliveryMapOrder
- API client 加 deliveryMapApi（getConfig + getOrders）

#### 数据库
- 新表 delivery_shifts（班次：staff_id/weekday/start_time/end_time/remark，周模板）

#### 后端 (server)
- 新增 5 个 delivery shift CRUD 端点 + 1 个 auto-dispatch 端点
- 2 个新权限（IDs 478-479：delivery.shift.{list,manage}）+ 菜单 1380 + 子按钮 1381
- DeliveryShift{Model, Repository, Service, Controller, Validate}
- DeliveryShiftRepository::getActiveStaffIds（按 weekday + time 筛在班 staff_id）
- DeliveryStaffRepository::findActiveByIds + DeliveryOrderRepository::countActivePerStaff（派单候选 + 工作量统计）
- DeliveryOrderService::autoDispatch（批量按工作量最少分配 pending 订单）
- DeliveryOrderController::autoDispatch + POST /adminapi/delivery/order/auto-dispatch（沿用 delivery.order.update 权限）

#### Admin 前端 (admin)
- 新建页面 /delivery/shift：列表 + 创建/编辑 dialog（el-time-picker）+ 软删
- 配送记录页"一键自动派单"按钮：取本页 pending 订单批量派
- 类型 DeliveryShift / DeliveryShiftQuery / AutoDispatchResult
- API client 加 deliveryShiftApi（5 方法）+ deliveryOrderApi.autoDispatch

#### 后端 (server)
- 新建 WaybillProviderInterface + KdniaoProvider + WaybillResult 值对象（工厂模式留多服务商扩展点）
- WaybillService::generate 重构为 dispatch 模式（读 system_configs.waybill_provider 选实现），成功后写回 order_logistics.waybill_no
- WaybillService::batchGenerate（串行批量调用，收集 success/failed）
- 新增端点 POST /adminapi/order/waybill/batch-generate
- 新权限 order.waybill.print（ID 484）

#### Admin 前端 (admin)
- order-ship 页"批量打印"按钮接通快递鸟 API：成功 HTML 通过 Blob URL 在新窗口 window.print()，失败明细弹框
- 单次 50 单上限（前后端双校验）
- API client 加 orderApi.batchGenerateWaybill；类型 WaybillBatchResult

#### 后端 (server)
- 商品/分类选择器水合端点：`GET /goods/goods-spu/by-ids` 与 `GET /goods/goods-category/by-ids`，返回顺序与入参一致，最多 100 个 ID

#### Admin 前端 (admin)
- 全局组件 `<GoodsPicker>` / `<CategoryPicker>`：弹窗式商品/分类选择器，支持多选/单选、limit、跨页保留勾选、缩略图回显、已删除/已下架状态显示

### Removed

#### Admin 前端 (admin)
- statistics 页头删除 3 个无 handler 占位按钮（时间 / 导出 / 订阅周报），等独立 statistics BI 子项目完整实现时再补回
- finance/overview 页头删除 "对账中心" / "订阅日报" 两个无 handler 占位按钮（对应 follow-up 已从 roadmap 移除）

### Fixed

#### 后端 (server)
- 修复全新安装在导入商城演示数据时报错 `Duplicate entry '' for key 'goods_units.uk_code'`：`demo-shop.sql` 的 `goods_units` INSERT 缺失 `code` 列，导致所有行写入空字符串冲突；现按拼音 / 国际单位补齐唯一 `code`，并将原重复 '条' 行修正为 '段'。
- 修复 AI 配置保存全部静默失败：`init.sql` 缺少所有 `ai.*` system_configs seed（`ai.default_driver` / `ai.{driver}.api_key|model|base_url`），而 `SystemConfig::setConfigValue` 对不存在的 key 返回 `false`。补齐 17 行 seed。
- `SystemConfig::setConfigValue` 对不存在 key 改抛 `BusinessException`（原 silent return false，admin 改配置 UI 显示成功但实际未存）；caller Controller 自动通过基类 try/catch 转 4xx。
- Dashboard 7 个 cache key 加 `date('Ymd')` 后缀（如 `ecommerce_dashboard_stats_v2_<Ymd>`），避免午夜 0:00-0:02 内服务前一天数据。涉及方法：`getStats` / `getRealtimeKpi` / `getSalesTrend` / `getOrderStatusDistribution` / `getHotProducts` / `getPendingTasks` / `getPaymentMix`。
- `FinanceTransactionRepository::getMonthlyReport` 性能优化：30 天循环 × 3 = 90 queries → 1 个 `GROUP BY DATE(created_at) + SUM(CASE WHEN type=...)` 单查询。
- `users.last_login_time` 永远为 NULL 的隐藏 bug（DAU/MAU 端点 `member/statistics/active` 之前恒返回 0；UserService::login 调用了 UserRepository 中实际不存在的 `updateLastLogin` 方法）— 移交 `UserLoginListener` 双写处理

#### Admin 前端 (admin)
- 修复 marketing/flash-sale、marketing/group-buy 列表 stats 计算混用 `start_time/end_time`（后端模型仅有 `start_at/end_at`），统一为 `start_at/end_at`。
- `account-fund` 页 `handleApprove` / `handlePay` 加 try/catch（仿同文件 `confirmReject` 模式），取消 ElMessageBox 不再产生浏览器 console unhandled rejection。

### Changed

#### 后端 (server)
- 分层重构：`FinanceService` / `DistributionService` / `DistributionSettleCommand` 全部 Model 静态调用替换为 Repository 实例调用，遵循 CLAUDE.md "Service/Command 不直接调 Model 静态"
  - `DistributionWithdrawalRepository` 加 `sumPending`
  - 新建 `DistributionCommissionRepository`（6 方法：create / getPageList / getUserPageList / getUserStats / getAllForExport / bulkSettle）
- `DistributionSettleCommand` 用 `bulkSettle` 1 个 UPDATE 替代逐条 save 的 N+1 query
- `FinanceTransactionRepository::getDailyTrend` 性能优化：N 天循环 × 2 = 2N queries → 1 个 `GROUP BY DATE(created_at) + SUM(CASE WHEN type=...)` 单查询

#### Admin 前端 (admin)
- distribution/commission 佣金规则卡删除"团队整体 2%"假第 3 级
  （后端 distribution_levels 仅支持 first_rate/second_rate 两级），
  标题"三级佣金规则"改为"佣金规则"
- 页面装修菜单合并：移动端装修 / PC端装修 合并为单一「页面装修」页面，使用 `erp-tabsel` 端切换 + `row-13` 卡片网格 + 设备 mock 预览，对齐 SHOP 设计稿。
- 删除旧版 `views/diy/uniapp/index.vue` 与 `views/diy/pc/index.vue`，新版位于 `views/diy/page/index.vue`。
- 页面装修子模块全部 6 个页面对齐 SHOP 设计稿（page-head + row-14 KPI + filter-bar）：
  - 专题管理：单行 filter-bar + 表格 + 平台 / 状态 tag。
  - 主题管理：row-14 主题卡片网格 + 平台切换。
  - 分类页样式：row-13 样式选择卡 + 选中态高亮。
  - 链接管理：左侧分类导航 + 右侧链接表格 + 类型 tag。
  - 底部导航：双栏（编辑 + 手机预览）+ 拖拽排序。
  - 商城风格：双栏（色板 / 预设 + 实时预览手机）+ 5 个预设方案。
- 满减、优惠券表单的"指定分类/指定商品"从 ID 文本输入改为 `<CategoryPicker>` / `<GoodsPicker>` 选择器
- DIY 编辑器商品/分类选择从局部组件迁至全局 `<GoodsPicker>` / `<CategoryPicker>`，删除 `views/diy/editor/components/{Goods,Category}Picker.vue`

## [2.4.0] - 2026-05-06

> uniapp Stage 2：交易主路径重做 + 自提门店全栈 + 同城配送闭环

### Added

#### 后端 / admin（自提门店）
- 新增 `stores` 表 + Store Model/Repository/Service/Controller，支持距离排序（Haversine）
- 订单表加 7 个自提字段：`delivery_type` / `pickup_store_id` / `pickup_code` / `pickup_at` / `pickup_verified_by` / `pickup_status` / `pickup_timeout_at`，加 `idx_pickup` 索引
- 商品表 `goods_spu` 加 `delivery_modes` JSON 字段
- `system_configs` 加 3 个 pickup 配置项（超时天数 + 默认城市坐标）
- 自提码生成（同门店 pending 订单 unique 6 位 + 碰撞重试 3 次）+ 7 天软超时 + admin 手动核销
- 定时任务 `pickup:scan-timeout`（每天 03:00）扫超时订单
- 事件 `order.pickup_code.generated` / `order.pickup_timeout` + Listener 接入 message-templates 模块（订阅消息 + 短信）
- admin 门店管理页（列表 + 编辑，含高德地图选点 + Geocoder 反查 + 营业时间 7 天编辑器，跨午夜 22:00-02:00 时段也能正确判定营业中）
- admin 商品编辑接 `delivery_modes` 多选（快递/即时/自提） + 列表批量开关自提
- admin 订单详情自提区块 + 手动核销按钮
- 门店管理菜单（顶级 ID 1400 + 子菜单 1410-1414，含 store.list / store.create / store.edit / store.delete / order.pickup.verify 五个权限）

#### uniapp 移动端
- 新增 5 个 d-* 组件（其中 4 个新建 + 1 个复用现有）：`d-store-card` / `d-store-map`（uniapp `<map>` 包装 + marker 弹卡） / `d-pickup-banner`（大码 + QR + 门店卡） / `d-delivery-chip`（express/merchant/pickup chip 组），支付方式 popup 复用 v2.1.0 已交付的 `d-payment-popup`
- 新增 `pickup-store.vue` 门店选择页（列表/地图 segment + 距离排序 + 营业状态徽标 + readonly 预览模式 + 歇业二次确认）
- d-icon 补 `store / qr-code / phone-call` 图标
- 详情页"配送"cell 接入数据：仅快递 / 快递+自提（X 个门店）/ 仅自提（X 个门店）三态文案，点击进 pickup-store readonly

### Changed

#### uniapp 移动端
- 7 个交易页 B 方案视觉重做（保留 logic / data / 接口调用，仅重写 template + style，token 化）：cart / checkout / order list/detail/refund/logistics / pay-result
- checkout 接入配送方式 chip + 自提门店 cell；100% 保留 v2.1.0 沉淀（满减 popup / 优惠券 popup / 发票表单 / 已减行 / 支付方式 popup）
- 订单列表 / d-order-card 自提订单加"自提"徽标
- 订单详情自提订单顶部展示自提码 banner（大码 + QR 占位 + 门店卡 + 3 步时间线）；隐藏收货地址段
- 物流页自提订单替换为时间线（已下单 → 待自提 → 已自提）；快递订单原有时间线保留
- 支付结果页自提订单展示自提码 banner

#### 后端
- `OrderService::create` 加自提分支：`delivery_type=pickup` 时自动生成自提码 + 写超时时间 + 触发事件
- `OrderService::calculate` 加 `$deliveryType = 'express'` 可选参数，自提强制免运费
- `OrderService::verifyPickup` 新公开方法（admin 核销用，4 个 phpunit case 覆盖：成功 / 错码 / 已核销 / 已超时）

#### 数据库
- `order_orders` 加 7 字段 + `idx_pickup` 索引
- `goods_spu` 加 `delivery_modes` JSON 字段
- `system_configs` 加 3 项 pickup 配置
- 老订单 `delivery_type` 通过 `DEFAULT 'express'` 自动回填，老商品 `delivery_modes IS NULL` 由 Repository 兜底视为 `["express"]`

#### 同城配送闭环（2026-05-06 追加）

##### 后端
- `OrderPaidListener` 加 merchant 分支：付款后自动建 `delivery_orders(pending)`，admin 派单页可见
- `DeliveryOrderService::assign / updateStatus` 同步 `OrderOrder.status`：派单 → shipped、配送完成 → completed（+ receive_time），按状态秩序避免回退
- `OrderService::computeMerchantFreight`：用户地址 → 最近启用门店 haversine 距离 → 校验 radius → 起步费 + ceil(km) × 每公里费 → 满 free_amount 减免；create / calculate 均接入
- `OrderController::calc` 透传 delivery_type / lng / lat
- `member_addresses` 加 `lng` / `lat` decimal(10,6) 字段
- `system_configs` 新增 `local_delivery_per_km_fee`（每公里费）
- `OrderOrder::deliveryOrder()` hasOne 关联 + Repo `with(['deliveryOrder'])` + toArray key normalize（camelCase → snake_case 给前端）

##### admin
- 订单列表新增"配送方式"列；merchant 单走「派单」按钮（选骑手 dialog）替代填快递单号；`shipped` 状态加「标记送达」按钮
- 支付/物流副行：merchant 显骑手姓名 / pickup 显到店自提
- `/admin/delivery/local-config` 表单加「每公里费」字段，文案修正起步费 + 公式 tip

##### uniapp
- 地址编辑页加「地图选点」cell（uni.chooseLocation 三端通用），选完写 lng/lat + 自动回填 detail；mp-weixin manifest 加 `requiredPrivateInfos: ['chooseLocation', 'getLocation']`，h5 sdkConfigs 替换占位 key
- 订单详情新增「配送进度」时间轴（待派单 → 已派单 → 配送中 → 已送达）+ 骑手卡片（姓名 + 电话拨号）
- 订单卡片 merchant 单加「同城配送」warning tag
- 订单列表加「退款/售后」tab（虚拟状态，按 order_refunds 关联过滤）
- checkout 切配送方式 / 切地址自动重新试算运费；超出范围 / 缺经纬度 红字提示 + 提交阻塞

### Fixed

#### 同城配送 / 订单
- `OrderController::create` 透传 `delivery_type / pickup_store_id / buyer_remark`（之前只透传 `coupon_user_id`，前两者被吞，buyer_remark 字段名 mobile 错配）
- `AddressController::store / update` 字段白名单加 `phone / lng / lat`（之前用旧名 `mobile`，提交的 phone 经纬度全部静默丢失）
- `GoodsSpuService::extractSpuData` 写入白名单加 `delivery_modes`（admin 编辑商品勾选的配送方式之前永远存不进去）
- `MemberCartService::getList / getSelectedItems` 响应补 `delivery_modes`（结算页同城配送 chip 不出的根因之一）
- `Store::getIsOpenNowAttr` 识别 `think\model\type\Json` 对象（type cast 后 `is_array` 永远 false 导致门店"已歇业"）
- `StoreService::create / update` 写入前白名单过滤，剔除 `is_open_now / status_text` 等虚拟字段（避免 `fields not exists` 报错）
- `DeliveryOrderRepository` `with(['order'])` 字段名 `total_amount` → `pay_amount`（OrderOrder schema 实际字段）
- `OrderOrderRepository` toArray 后把 camelCase 关联 key `deliveryOrder` 转 snake_case 给前端，修订单详情"配送进度"不刷新

#### 支付
- `PaymentSuccessListener::handleOrder` 强制 `(float)$amount` cast，修 strict_types + bcdiv 字符串触发的 TypeError
- `OrderPayService::handlePaid` 兜底 INSERT order_payments 时反查 payment_orders.id（之前写死 0）
- `PaymentService::createOrder` 复用已 pending 的 payment_order，避免重试支付撞 unique_no
- 新增 `payment:resync` CLI 命令：扫描 paid 但业务订单未联动的卡单，可一键修复

#### 评价 / 退款
- review.vue / refund.vue 改用 `onLoad` 接 query 参数（setup 顶层 `getCurrentPages` 在 mp 当前页注册前 options 是空）
- query 参数读 `goods_id`（detail.vue 跳转用的字段），保留 `order_item_id` 兜底
- http 拦截器已解包：去掉 `res.code === 200 / res.data` 套层
- `OrderItem` 字段 `goods` → `items`（对齐后端 `with(['items'])`）
- `createReview` 字段 `order_goods_id` → `order_item_id`（对齐 OrderReviewService）
- refund 金额单位 bug：前端原本发分（`Math.round(amountVal * 100)`），后端按元 min 截断。改发元

#### admin 列表
- 订单列表 5 处优化（会员头像 + 商品图 + 快递公司下拉 + 选择打印 + pay_method → pay_type）
- 售后管理列表原订单/会员展示（`row.order?.order_no` / `row.user?.nickname`），类型枚举 `refund_return` → `return_refund` 对齐后端
- 发货管理收件人/地址改读 `address_snapshot` 字段（之前读不存在的 `recipient_name / shipping_address`）
- 订单列表 user 关联去掉不存在的 `username` 字段，避免 SQL 报错；列表 with logistics

#### 地址 / 用户
- 地址字段 mobile → phone 三端统一（含历史 `address_snapshot.mobile` 兜底）
- my 页订单 quick entry 加 `pending / paid / shipped / refunding` 4 个角标计数

### Removed

- 删除死代码 `OrderPayController` + `OrderPayService::createPayment`（已被 `/api/payment/*` 取代）

## [2.3.0] - 2026-05-03

> uniapp Stage 5：DiyRenderer 16 装修组件视觉对齐 + token 化 + 空数据降级

### Changed

#### uniapp 移动端 (uniapp)
- DIY 16 组件全量 token 化：硬编码颜色（`#fff` / `#18181b` / `#666` / `#2979ff` / `#ef4444`）替换为 `var(--color-*, #{$scss-variable})`；圆角统一 16rpx（卡片）/ 8rpx（小元素），padding 统一 28rpx（卡片内）。
- 媒体类 4 组件（banner / image-ad / image-cube / video）：无数据 / 无 src 时整组件自动隐藏，不留空白。
- 数据列表 4 组件（goods-grid / coupon-list / seckill / article-list）：空数据显占位（"暂无 X" + d-icon），文案/图标统一。
- 导航类 3 组件（category-nav / nav-grid / float-button）：无 items 自动隐藏；float-button 默认 position=right-bottom。
- 结构类 3 组件（title-bar / divider / search-bar）：默认值对齐（title 32rpx 加粗、divider solid+2rpx、search 胶囊 999rpx）。
- 杂类 2 组件（rich-text / notice）：空 content / 空 items 自动隐藏。
- DiyRenderer 容器加 props 守卫：在 `list` computed 里 filter 掉 `props == null` 的 comp，避免运营配空组件时模板炸 undefined。
- playground 页注入 16 组件 × 多 case 共 30 个 demo（有数据 + 空数据），用于三端视觉走查。

---

## [2.2.0] - 2026-05-03

> uniapp Stage 3：用户中心打磨 + Stage 2 续单零散修补

### Added

#### uniapp 移动端 (uniapp)
- 新组件 `commonApi.uploadImage`（封装 `uni.uploadFile` 到 `/api/common/upload/image`，三端通用）。
- 新页面 `modules/about/pages/agreement.vue`（用户协议 + 隐私政策两节静态文本，`<text>` 段落 mp 兼容方案）。
- 修改密码页加密码强度提示（弱/中/强 三档 + 颜色条 + 200ms 过渡）。
- 余额 / 积分流水按月分组（YYYY 年 M 月）+ 当月收入/支出汇总 + 空态。
- 个人资料 profile 页：5 字段展示（昵称/性别/生日/邮箱/手机）+ 未设置态 + 性别中文映射。
- 个人资料编辑 edit-profile：email 字段 + 生日 picker `:maxDate` 限当日 + 邮箱格式校验（可选）+ 头像上传通过已有 `d-avatar-upload` 组件。
- 设置页：分组 cell（账号/通用/关于）+ 清理缓存 + 红色退出登录 + 二次确认 modal。
- feedback 页：字数计数（200 上限）+ 空内容校验 + 提交成功 toast。
- about 页加协议入口 cell（用户协议 / 隐私政策）。

### Changed

#### 后端 (server)
- `Response::success / error / paginate / list` 显式声明 `Content-Type: application/json; charset=utf-8`（ThinkPHP `contentType()` 自动追加 charset，仅传 `'application/json'` 即可），解决 uni.uploadFile 在部分平台用 latin-1 解析响应导致中文 mojibake 的问题。

#### uniapp 移动端 (uniapp)
- `UserInfo` interface 加 `email` 字段；`userApi.updateProfile` 类型加 email。
- 积分流水 type 字段从数字映射为中文（`PointsLog::TYPE_*` 1-6 常量映射）。

### Fixed

#### 后端 (server)
- `group-buy` 路由 `:id` / `activity/:id` / `group/:id` 加 `\d+` pattern，`group/:id` 排在 `:id` 通配前（同 v2.1.0 flash-sale 修法），防止字面路径被通配抢占走 admin_auth。
- `UserService::updateProfile` 加 `email` 进允许字段（之前被 array_intersect_key 过滤丢）；空 `birthday` / `email` 转 null（DATE 列对空串严格，会报 `Incorrect date value: ''`）。

#### uniapp 移动端 (uniapp)
- `useUpload.chooseAndUpload` resolve 改为 `result.url`（后端实际返 `{url, size}`，无 `path` 字段，导致头像组件 emit undefined → form.avatar 为空 → 头像不显示）。
- `modules/order/pages/refund.vue` + `review.vue` OrderItem 字段对齐（cover→goods_image / name→goods_name / sku_name→spec_text；formatPrice 不再除 100）。

---

## [2.1.0] - 2026-05-03

> uniapp Stage 2：营销模块（发票 / 满减 / 秒杀）补齐 + 信息修补 + 走查 bug 集中修复

### Added

#### 后端 (server)
- 新增用户端发票申请接口（`POST /api/order/invoice` 提交、`GET /api/order/invoice` 列表、`GET /api/order/invoice/:id` 详情）；`OrderInvoiceController` + `OrderInvoiceValidate`，Service 端复用既有 `OrderInvoiceService`，新增 `createForUser / getUserList / getUserDetail` 方法。
- 订单取消联动软删发票（`OrderCancelledInvoiceListener` + `event.php` 注册到 `order.cancelled`，含 try-catch + 失败日志）。
- 新增"可领取优惠券"接口 `GET /api/marketing/coupon/receivable`，与结算页 `available` 区别开（不依赖订单上下文）；新增 `MarketingCouponRepository::getReceivable`。
- `plugins/full-discount/controller/api/FullDiscountController` 新建：`GET /api/marketing/full-discount/goods/:spuId`（按 SPU 查命中的满减活动）。
- `plugins/flash-sale/controller/api/FlashSaleController::byGoods` 新增：`GET /api/marketing/flash-sale/goods/:spuId`（按 SPU 查命中的秒杀活动 + matched_item）。
- 新增订单试算接口 `POST /api/order/calc`（cart/checkout 共用），`OrderService::calculate` 复用 hook 链 `order.calc_discount`，返回 goods_amount/freight_amount/discount_amount/pay_amount。
- `PointsLog` 新增常量 `TYPE_POINTS_MALL = 6`（积分商城兑换扣减）。

#### uniapp 移动端 (uniapp)
- 我的发票管理列表页（`modules/order/pages/invoice-list.vue`），含状态徽章（pending/processing/issued/cancelled）+ H5/MP 条件编译下载链接。
- checkout 页发票申请表单（开关 + personal/company tab + 抬头/税号/邮箱/内容；订单提交时附带 invoice 子对象）。
- 新组件 `d-promo-popup`（满减规则半屏 popup，多档 reduce/percent/freight 展示）。
- 新组件 `d-flash-sale-banner`（详情页价格区秒杀融合：秒杀价 + 划线原价 + 倒计时 hh:mm:ss + 库存进度条 + 倒计时归零自动 fallback）。
- 新组件 `d-address-picker`（公用地址选择 popup，积分商城兑换 + checkout 选地址共用，含默认地址自动选中、新增地址跳转）。
- 详情页"优惠"cell 接入满减 `d-promo-popup`，价格区命中秒杀时切换 `d-flash-sale-banner`。
- 购物车底部加"已减 ¥X"行 + checkout 价格明细加"满减 -¥X"行（均通过 `orderApi.calc` 试算）。
- my 宫格新增三个入口：签到 / 积分商城 / 发票管理（修复 Stage 0 遗留的孤儿页可达性问题）。
- 新增 invoiceApi / orderApi.calc / marketingApi.getReceivableCoupons / getFullDiscountRules / getFlashSaleByGoods。

### Changed

#### 后端 (server)
- `order_invoices` 表新增 `UNIQUE KEY uk_order_id`（一单一票数据完整性兜底，原 `idx_order_id` 普通索引移除）。增量 SQL 在 `server/database/updates/v2.1.0/`。
- 订单路由顺序调整：`POST /order/calc`、发票路由放到通配 `:id` 之前；`points-mall` / `flash-sale` / `full-discount` 等路由的 `:id` 加 `\d+` pattern 约束（避免被 `goods` / `my-orders` 等字面路径误匹配走 admin_auth 中间件）。
- `flash-sale` api 路径 `by-goods/:spuId` → `goods/:spuId`（规避连字符路径解析问题）。
- `CouponService::claim` 兼容 `total_count` / `per_user_limit = 0` 视为不限。
- `MarketingCouponRepository::getReceivable` 库存/限领判断注释与代码语义对齐。
- `OrderInvoiceController::index/show` 加 try-catch（与 submit 一致）；`OrderInvoiceService::createForUser` 加订单已付款校验 + 标注 Service→Model 静态调用 TODO；`OrderInvoiceRepository::paginateByUser` query 链 count + order + page 顺序修正。
- `group-buy/activityStore` 数值校验 `min:` 改 `egt:`（ThinkPHP `min:` 默认按字符串长度，非数值）。
- `GoodsSkuRepository::findByIds` 字段名 `spec_values` 改 `spec_value_ids + spec_text`（`goods_sku` 表无 `spec_values` 列）。

#### uniapp 移动端 (uniapp)
- `icons.ts`：`wechat` 短名映射改回 `@iconify-icons/ri/wechat-fill`，新增 `wechat-pay` 短名指向 wechat-pay-fill 给真正需要支付图标的地方用。
- 优惠券中心切到 `getReceivableCoupons` 接口（不再调结算页 `available`）；`CouponItem` interface 字段对齐 `marketing_coupons` 表（`type` enum / `start_at` / `end_at` / `used_count` / `total_count` / `per_user_limit`）；模板按字符串枚举比较 + 加防御取值。
- "我的优惠券"列表 `MyCouponItem` 字段拍平嵌套 `coupon.*` + 状态字符串（`unused/used`）。
- 收藏接口字段对齐后端：`is_favorite` → `favorited`、`name/cover/price` → `spu_name/spu_image/min_price`；my 页"收藏商品"计数改走 favorite api `pagination.total`。
- 订单字段全面对齐：`status` 数字 → 枚举字符串（pending/paid/shipped/completed/cancelled/closed）；`order.goods` → `order.items`；`name/cover/sku_name` → `goods_name/goods_image/spec_text`；`address` → `address_snapshot`；金额字段 `number → string`；`formatPrice` 不再除 100。
- 积分商城兑换 popup：商品打开时自动加载默认地址；地址选择从跳转列表页改为 popup-in-popup（用 `d-address-picker`）。
- checkout 选地址同步迁移到 `d-address-picker`，废弃 `globalData` 跨页传递方案。
- `claimCoupon` 改为 `POST /coupon/claim` body 传 `coupon_id`（对齐后端路由）。

### Fixed

#### 后端 (server)
- coupon 模块 `claim` 时总量/每人限领设置为"不限"（0/null）报"已领完"的死锁。
- 订单 `/api/order/invoice` 列表被通配 `:id` 路由抢占报"订单不存在"。
- 积分商城 `/api/points-mall/my-orders` 被 `:id` 路由抢占报"商品不存在或已下架"。
- 积分商城兑换 500 错误（`PointsLog::TYPE_POINTS_MALL` 常量未定义）。

#### uniapp 移动端 (uniapp)
- 签到日历不显示已签状态（http util 已提取 `data` 字段，前端再读 `res.data` 是 undefined）。
- 商品详情页点收藏成功但 toast 显示"已取消收藏"（响应字段 `is_favorite` 与后端 `favorited` 不一致）。
- 收藏列表商品图片/名称/价格不展示（`FavoriteItem` 字段错位）。
- my 页"收藏商品"计数恒为 0（之前从 `userApi.getPoints()` 取不存在的 `.favorites` 字段）。
- 购物车底部 SCSS 报 `Undefined variable $text-color-secondary`（`pages/cart/index.vue` style 未 import variables.scss）。
- group-buy admin 创建活动报"group_size 长度不能小于 2"（ThinkPHP `min:` 默认字符串长度规则）。
- `flash-sale` `/api/marketing/flash-sale/goods/:spuId` 报 401 `Token验证失败`（admin 路由 `:id` 通配抢占走 admin_auth；通过加 `\d+` pattern 修复）。

### 数据库

- `server/database/updates/v2.1.0/update.sql`：`order_invoices` 表 `idx_order_id` → `UNIQUE KEY uk_order_id`。
- 同步更新 `server/public/install/data/schema.sql`。

### 后续 RC 阶段补充

#### Added
- admin 端补齐秒杀商品 items CRUD 接口：`GET/POST /adminapi/marketing/flash-sale/:id/items` + `PUT/DELETE /:id/items/:itemId`，列表数据自动注入 `goods_name / spec_text / goods_image / original_price`，添加时同 SKU 防重。

#### Changed
- admin 秒杀商品表单：从"输入 SKU ID"改为"GoodsPicker 选商品 + el-select 选规格"（显示 spec_text/价格/库存）。编辑模式下商品/规格不可改。
- 详情页秒杀 banner 字段对齐 (`flash_price/flash_stock/sold_count`)；`end_at` 字符串解析转 ISO 8601 'T' 分隔（兼容 iOS Safari / mp）。
- 满减规则前后端字段读取兼容 admin 表单存的 `min_amount`（旧 `min` 也兼容）。

#### Fixed
- admin flash-sale items 列表为空：路由 `:id/items` 必须排在 `:id` 通配之前（`route_complete_match=false` 部分匹配抢占问题）。
- 满减 popup 显示"满 undefined 减 N"：tier 字段名错位（admin 存 `min_amount`，前后端都按 `min` 读）。
- 详情页秒杀价不显示：`FlashSaleMatched.matched_item` 字段名 `sale_price/stock/sold` 与后端 `flash_price/flash_stock/sold_count` 错位。
- `GoodsSpuService::getSkusByIds` 残留对旧字段 `spec_values` 的处理（B6 改 Repository 后未同步 Service，反而把已返回的 `spec_text` 覆盖为空）。

#### Known limitation
- 秒杀商品**展示**走 flash_price，**实际下单**仍按原 SKU 价（前端 detail.vue 已加 TODO 注释，留下个版本接 `OrderService` 做完整集成）。

---

## [1.7.0] - 2026-05-01

### Added
- DIY 页面装修支持「历史版本」:发布时自动快照 components/title/page_settings,单页保留最近 50 条
- 编辑器顶栏新增「历史版本」抽屉,支持预览(只读)/恢复操作
- 发布按钮支持可选填备注,作为版本元数据
- 新增权限 `diy.page.version.list` / `diy.page.version.restore`,自动赋予原 publish 角色

### Changed
- `PUT /adminapi/diy/page/{id}/publish` 接受可选 `note` 参数(向后兼容)

## [1.6.4] - 2026-05-01

### Changed
- 分类页配置菜单名缩短为「分类配置」(4 字)
- 分类配置 3 张骨架卡片改为一行 4 列
- 商城风格「重置默认」改为带确认 + 立即写库,真正持久化恢复

### Removed
- 删除页面装修「组件市场」按钮(占位)
- 删除模板中心「模板市场 / 排期日历 / 主题排期」相关 UI 与数据(占位)
- 删除底部导航「预览小程序 / 预览 APP」按钮(实时预览已存在)

## [1.6.3] - 2026-05-01

### Changed
- 分类页配置 3 张骨架预览改为 9:16 竖长方形(资源 560×1000),卡片渲染高度 200px → 500px,与页面装修/模板中心保持一致

## [1.6.2] - 2026-05-01

### Changed
- 页面装修 / 模板中心 列表封面改为 9:16 竖长方形(模拟手机)，渲染 500px,资源 560×1000
- 页面装修卡片移除 mock 预览,改用占位 PNG 图片(`/storage/diy-defaults/page-cover.png`)
- 模板中心 8 张系统封面占位 PNG 重生为 560×1000

## [1.6.1] - 2026-05-01

### Changed
- 页面装修 / 模板中心 列表改 4 列,封面渲染高度 275px(资源 2x retina 550px)
- 分类页骨架预览从内联 SVG 改为占位 PNG
- 模板中心 8 张系统封面占位 PNG 重生为 560×550

### Added
- 编辑器 7 个 Preview 组件(Banner/ImageAd/ImageCube/NavGrid/CategoryNav/GoodsGrid/ArticleList)加默认占位图回退
- 新增 12 张通用占位 PNG 至 `/storage/diy-defaults/`(供设计师后期替换)

### Removed
- 分类页配置底部「保存」按钮(头部已有)

## [1.6.0] - 2026-05-01

### Added
- DIY 页面管理支持每端多个首页 + `is_default` 切换默认
- 新建页面 Dialog 支持选择"空白模板"或现有模板创建
- 分类页配置(仅移动端)支持 3 种骨架可视化选择
- PC 端首页接入 DIY 渲染
- 模板中心(原主题管理改名)新增 8 套系统预设
- 装修编辑器新增"保存为模板"按钮

### Changed
- 「主题管理」改名为「模板中心」,路径 `/diy/theme` 迁移至 `/diy/template`
- 「分类管理」改名为「分类页配置」
- 「页面装修 / 专题管理」合并为「页面管理」(tab 切首页/专题)
- 系统配置 `category_page_style` 重命名为 `category_page_style_uniapp`

### Removed
- 删除「专题管理」「链接管理」独立菜单
- 删除老 `diy.topic.*` 权限(自动迁移到 `diy.page.*`)
- 移除专题页 KPI 块中"月 PV"占位指标

## [1.5.6] - 2026-04-26

### Added

#### Admin 前端 (admin)
- 用户管理新增「地址簿」页面：会员地址统一查看 / 删除，含总数、默认地址、覆盖会员等 KPI。
- 用户管理新增「账户资金」页面：单页面集成余额变动 / 充值记录 / 提现申请三个 tab，统一 KPI（账户余额总额、本月充值、本月提现、待审核提现），提现支持通过 / 拒绝 / 打款。
- 会员中心 8 个页面（会员管理 / 会员等级 / 用户标签 / 用户分组 / 充值套餐 / 分销员管理 / 地址簿 / 账户资金）UI 排版统一对齐 SHOP 设计稿（page-head + row-14 KPI + 单行 filter-bar）。

#### 后端 (server)
- 新增 `MemberAddressRepository / MemberRechargeOrderRepository / DistributionWithdrawalRepository` 数据访问层，遵循项目分层规范。
- 新增 `AddressBookService`（admin 端地址簿）、`AccountFundService`（聚合余额日志 / 充值订单 / 提现申请）。
- 新增 8 个 admin 路由：`/adminapi/member/address/*`、`/adminapi/member/fund/*`。
- 不新增表：账户资金页直接复用既有 `balance_logs` / `member_recharge_orders` / `distribution_withdrawals`。
- 新增 5 个权限：`member.address.list / member.address.delete / member.fund.list / member.fund.withdraw.approve / member.fund.withdraw.pay`。
- 新增 5 个菜单（970 地址簿、971 删除按钮、975 账户资金、976 审核提现、977 提现打款）。

### Removed

#### Admin 前端 (admin)
- 删除已被 `views/member/` 取代的旧版 `views/user/{user,balance-log,points-log}` 页面（`api/user.ts` 保留供其他模块复用）。

## [1.5.3] - 2026-04-10

### Changed

#### 后端 (server)
- 数据库全部表的字符集排序规则从 `utf8mb4_unicode_ci` 升级为 `utf8mb4_0900_ai_ci`（需 MySQL 8.0+）

### Fixed

#### Admin 前端 (admin)
- 修复请求错误冒泡到 ErrorBoundary 的问题
- 修复菜单父级选项类型错误

## [1.5.2] - 2026-04-08

### Fixed

#### 后端 (server)
- 修复安装时启用数据库表前缀（如 `PREFIX=yd_`）后 admin 登录接口崩溃的问题（"Typed property AdminService::\$adminRepository must not be accessed before initialization"）。根因是 ThinkPHP-ORM 4.x 下 `protected $table` 会绕过 `database.prefix`：
  - 31 个 Model 全部从 `protected $table = 'xxx'` 迁移到 `protected $name = 'xxx'`，让 ThinkPHP 自动应用前缀
  - 8 个 Repository 的 `Db::table('literal')` 改为 `Db::name()`；`NotificationRepository` 的 `whereExists` 子查询和原生 `INSERT INTO` 改用 `(new NotificationRead())->getTable()` 拼装物理表名
  - `CodeGeneratorService` 模板从 `$table` 改为 `$name`，新增 `stripTablePrefix()` 在生成 Model 时剥离物理表前缀，未来更换前缀也无需修改 Model
- `core\base\Service` / `core\base\Controller` 的 `resolveDependencies()` 在 `APP_DEBUG=true` 下记录被吞掉的注入异常，避免下次类似问题再次表现为误导性的 typed property 错误

### Removed

#### 后端 (server)
- 移除 3 个全工程零引用的早期遗留死代码：
  - `core/auth/Role.php`：8 处 `Db::table()` 字面量 + 引用了 schema 里不存在的 `user_roles` 表，已被 `RoleRepository` 替代
  - `core/database/Connection.php`：`Db` facade 的薄封装
  - `core/database/Migration.php`：`think\migration\Migrator` 的薄封装

## [1.5.1] - 2026-04-07

### Added

#### 后端 (server)
- 新增 `MenuChangedListener`，统一处理菜单相关操作后的缓存失效

### Changed

#### 后端 (server)
- `createMenu`/`updateMenu`/`deleteMenu`/`batchDeleteMenu`/`batchSort` 成功后触发 `menu.changed` 事件
- `MenuRepository` 移除缓存失效副作用，迁移至 `MenuChangedListener`
- 移除 `MenuRepository` 中无调用方的 `deleteWithChildren` 和已废弃的 `collectChildrenIds` 方法

#### Admin 前端 (admin)
- 菜单表单改造为单列布局，dialog 宽度收窄至 600px，组件路径输入框添加前后缀 UI 提示

### Fixed

#### Admin 前端 (admin)
- 修复菜单新增/编辑/删除后列表缓存未失效、页面不刷新的问题
- 修复 `IconSelect` SVG 图标 glob 路径错误导致 SVG tab 一直空白的问题
- 修复 `IconSelect` `popoverWidth` prop 传入百分比时 popover 撑满视口的问题

## [1.5.0] - 2026-04-07

### Added

#### 后端 (server)
- `core\base\Service`：新增 `extractPagination()`、`findOrFail()`、`runInTransaction()` 三个基类方法
- `core\base\Repository`：新增 `buildPagination()` 统一分页响应构造
- `core\base\Model`：新增默认 `getStatusTextAttr()` 实现
- 新增 `AbstractLedgerLogRepository` 账目流水抽象基类（BalanceLog/PointsLog 复用）
- `MessageService::sendToUser()`：封装按用户 ID 发送模板消息
- `RoleService::batchDeleteRole()`、`DictionaryService::batchDeleteDictionary()`：批量删除带事务
- `AdminService::batchDeleteAdmin()`：批量删除带事务
- 代码生成器生成的 Model 自动包含 `$append = ['status_text']` 声明
- CronJobService 命令白名单 + Console::call 进程内调用替代 exec
- 新增语言键：wechat_open_platform_not_configured / wechat_auth_failed / cron_command_empty / cron_command_not_allowed

#### Admin 前端 (admin)
- 新增 `hooks/useListPage.ts`：列表页通用 composable（分页/搜索/删除/批删/状态切换）
- 新增 `hooks/useFormDialog.ts`：表单弹窗通用 composable
- 新增 `utils/createCrudApi.ts`：标准 CRUD API 工厂
- 新增 `styles/crud-layout.scss`：全局列表页布局样式
- 新增 `constants/options.ts`：状态选项 hooks（useStatusOptions）
- 错误页面白名单含 404/500，loadRouteView 找不到组件时降级到 404

#### 移动端 (uniapp)
- 新增 `hooks/useCountdown.ts`：SMS 倒计时 composable
- 新增 `hooks/usePagingList.ts`：自动注册 onShow + onPullDownRefresh
- 新增 `hooks/useMessageList.ts`：消息列表共享逻辑 + messageCache 跨页面传递
- 新增 `utils/time.ts`：日期格式化工具（formatDate/formatDateTime/formatRelativeTime）
- 新增 `utils/platform.ts::getStatusBarHeight()`：状态栏高度封装
- 新增 `components/d-ledger-list/`：账目/积分流水列表通用组件
- 全局样式新增 .d-submit-btn / .d-section-card / .d-section-title

### Changed

#### 后端 (server)
- 分层架构合规：UserService 多处直接 Model 操作迁至 Repository
- User Model 删除静态查询方法（findByMobile/findByOpenid/findByMiniOpenid），逻辑迁至 UserRepository
- AdminLoginLog/AdminOperationLog 静态 record 方法迁至 Repository
- SystemConfig 静态查询逐步迁移至 Repository（保留 core 层使用的静态方法，避免反向依赖）
- MenuRepository.getAllChildrenIds 改为"一次全量查询 + BFS"消除 N+1
- NotificationRepository.markAsRead/markAllAsRead 改为批量 SQL 消除 N+1
- AlipayDriver create() 返回结构改为嵌套 data 字段，与 WechatPayDriver 对齐
- AdminController/MenuService.batchDelete 加事务包裹
- Listener 全部改为构造函数 DI（FeedbackCreated/UserRegister/PaymentSuccess/MessagePush/AdminLoginSuccess/AdminLoginFailed）
- DashboardService 删除 getRelativeTime，统一使用 DateHelper::diffForHumans
- ArticleCategoryService/DepartmentService 删除 buildTree，统一使用 ArrayHelper::toTree
- upload 路由添加 admin_log 中间件
- DashboardService 删除重复字段 newAdmins/newRoles/newMenus
- Admin Model 移除冲突的 setPasswordAttr，密码 hash 统一在 Service 层
- AuthController.wechatWebLogin 下沉至 UserService，使用 Guzzle 替代 file_get_contents
- 8 个 Service 应用 extractPagination 消除分页参数解构样板
- 5 处 findOrFail 替换样板代码
- UserManageService.adjustBalance/adjustPoints 改用 runInTransaction
- UserRepository.updateLastLogin 用 Db::raw 实现 login_count 原子自增

#### Admin 前端 (admin)
- 22 个列表页迁移到 useListPage（admin/role/department/cron-job/notification/dictionary/log×2/permission/file/announcement/agreement/article/article-category/feedback/region/version/auto-reply/balance-log/points-log/user/message-log/message-template）
- 15 个 Form 组件迁移到 useFormDialog
- v-has-perm 改用 removeChild 完全从 DOM 移除元素
- API 类型定义集中到 types/api.d.ts（UserItem/BalanceLogItem/PointsLogItem）
- usePaging 修复 res.data.list 数据解构对齐
- settings.store 清理 @ts-ignore，改用 $patch
- i18n 补全 feedback/article/article-category/announcement 等模块，合并 userMgmt.common.* 到顶层 common.*
- 首页快速导航重新排版

#### 移动端 (uniapp)
- LoginResult 类型字段从 user 改为 user_info（与后端对齐）
- user.store 新增 register 方法封装注册流程
- balance.vue/points.vue 接入 d-ledger-list
- register.vue 接入 useCountdown，移除手动 timer 管理
- announcement-list 接入 usePagingList 自动注册生命周期
- my/index.vue 头部高度通过 createSelectorQuery 动态测量替代硬编码
- settings.vue 接入真实的 useVersionCheck，删除假"检查更新"实现
- wechat-oauth 存储改用 uni.getStorageSync 跨端兼容
- upload.ts BASE_URL 与 request.ts 对齐（H5 DEV 代理）
- d-wechat-login 移除废弃的 uni.getUserProfile 调用
- 安全区域适配修复（加 env(safe-area-inset-bottom)）
- usePaging 新增 hasLoaded 状态供空状态组件防闪烁

#### PC 网站 (pc)
- 余额充值流程支持支付宝 PC page 支付（DOMParser 解析表单 + 新窗口手动提交）

### Fixed

#### 后端 (server)
- 修复 CodeGeneratorService.getTableColumns SQL 注入风险（白名单校验）
- 修复 FileService.deleteFile 物理文件删除失败未记录日志
- 统一 SystemConfigService 异常类为 BusinessException

#### Admin 前端 (admin)
- 修复 file/index.vue 模板语法错误导致页面无法加载

#### 移动端 (uniapp)
- 修复 balance.vue 支付字段名 payment_data 类型
- 修复 usePaging 初始 loading=true 导致 getList 阻塞（改为 hasLoaded 方案）
- 修复 useMessageList 从 modules 子包移到 hooks 主包（修复主包不能引用子包的限制）

### Removed

#### Admin 前端 (admin)
- 移除 ThemePicker 组件及 theme/apply.ts 遗留代码

#### 移动端 (uniapp)
- 移除 profile.vue 孤儿页面

## [1.4.0] - 2026-04-05

### Added
- 新增 `CacheableRepository` Trait，Repository 层声明式缓存抽象
- 新增 Redis 队列异步处理（操作日志、消息通知）
- 新增 `log:archive` 命令，定期清理过期管理员日志
- 新增前端 `useDebounceRequest` Hook，搜索请求防抖
- 新增前端 GET 请求去重机制

### Changed
- 缓存驱动由 file 切换为 Redis
- 字典数据增加 7200s Redis 缓存
- 菜单树增加 3600s Redis 缓存
- SystemConfigRepository / Permission 缓存迁移到标签化管理
- 操作日志由同步写 DB 改为异步队列
- 消息通知由同步 API 调用改为异步队列
- 余额/积分日志改用 eager loading，消除 enrichListWithNames 额外查询
- AdminRepository.getDetailWithPermissions 改用 eager loading 消除 N+1
- 前端删除 Auth Guard 冗余 menuApi.getAdminRoutes() fallback 请求

### Fixed
- 修复 admin_login_logs 缺少 (admin_id, login_time) 复合索引

## [1.3.0] - 2026-04-01

### Added
- API 文档支持后台管理 API / 前端应用 API 切换，C 端 11 个控制器添加 OpenAPI 注解
- UniApp 注册页新增手机短信验证码（与 PC 端注册流程统一）

### Changed
- UniApp 微信快捷登录按钮改为圆形绿色微信图标
- UniApp 引入 iconify 图标系统（@iconify-json/ri + presetIcons）
- 重构 Controller/Service/Repository 分层，消除架构违规（Controller 不再直接调用 Model，Service 不再绕过 Repository）
- Menu/Permission Model 查询逻辑迁移至 Repository 层
- AdminLogMiddleware 改用 Repository 记录操作日志
- 统一 Repository 调用风格（`$this->getModel()::` → `$this->model->`）
- `RequestCodeEnum` 更新为与后端一致的 HTTP 状态码（200/400/401/403/500）
- 超级管理员权限标识前后端对齐（后端注入 `'*'` 通配符）
- 事务 catch 类型统一为 `\Throwable`（RoleService、AdminService、DictionaryService）
- MessageLog Model 改为继承 `core\base\Model`
- `ConfigInfo` TypeScript 类型定义与实际 API 响应字段对齐
- `app.store.getConfig()` 返回值统一为 config 数据对象

### Fixed
- 修复开放平台配置保存成功但刷新后值为空（`system_configs` 缺少 `wechat_open` 组初始数据）
- 修复 `batchUpdateConfigs()` 对不存在的配置键静默跳过，改为抛出异常提示具体键名
- 修复异常类在 PHP 8.4 下隐式 nullable 参数弃用警告（BusinessException、ApiException、PermissionException）
- 修复 `server/public/static/fonts` 未纳入 git 版本控制
- 补充英文语言包缺失的 `config_group_wechat_open` 翻译
- 修复 Upload 组件响应码检查（`code == 1` → `code == 200`），错误消息字段（`msg` → `message`）
- 修复上传路由重复定义（移除 `common.php` 中多余的 upload 路由组）
- 修复超级管理员 `v-hasPerm` 指令不生效（后端未向前端发送 `'*'` 权限标识）
- 修复 21 个 Model 缺少 `$append` 声明导致访问器字段不出现在 API 响应
- 修复日志 Model 缺少 `$updateTime = false`（AdminLoginLog、AdminOperationLog）
- 修复静默吞掉异常的 catch 块（FileService、CodeGeneratorService、UploadController），改为 Log::warning
- 修复 `api-doc` 页面硬编码 localStorage key 获取 token
- 修复 `workbench` 页面 `v-for` + `v-if` 同元素（Vue 3 不允许）
- 修复 Vite 开发模式新页面首次访问触发依赖重优化整页刷新（改用动态解析组件样式路径）
- 修复 Dashboard stats 接口报 "Undefined array key 'login_result'"（AdminLoginLog 访问器加 isset 防御）
- 修复多个 Model 访问器在字段缺省时抛出 "Undefined array key" 警告（Admin、Role、Menu、Dictionary、DictionaryItem、Permission、BalanceLog、PointsLog）

### Removed
- 移除重复的消息模块视图（`views/message/`，保留 `views/system/message/`）
- 移除未使用的路由 guard 文件（`router/guards/init.ts`）
- 移除死代码：`getWorkbench()`、`getGlobalConfigs()`、`ThemePicker/demo.vue`
- 注册 3 个孤立事件到 `event.php`（`announcement.created`、`article.created`、`user.notification.created`）

## [1.2.1] - 2026-03-28

### Added
- 新增系统版本配置文件 `server/config/version.php`
- 新增数据库升级目录 `server/database/updates/` 及通用升级指南
- 新增 v1.2.1 数据库升级脚本（权限系统修复）

### Changed
- `CLAUDE.md` 新增发版数据规范章节

### Fixed
- 修正菜单权限命名不一致（type=2 菜单添加 `.list` 后缀）
- 补充缺失的 type=3 按钮菜单权限
- 为超级管理员角色分配新增按钮权限

### Removed
- 移除 `server/public/install/data/fix_permissions.sql`（内容已合并至 init.sql 和 updates/v1.2.1）

## [1.2.0] - 2026-03-24

### Added
- 仪表盘新增「最近活动」和「活跃用户排行」数据端点（`/adminapi/dashboard/recent-activities`、`/adminapi/dashboard/active-ranking`）
- DashboardRepository 新增用户统计、排行榜、最近活动查询方法
- DashboardService 新增用户注册/活跃统计、最近活动聚合、活跃排行逻辑

### Changed
- 仪表盘前端整体重设计：渐变玻璃态风格（gradient glassmorphism）
- KPI 卡片调整为冷色系渐变配色（左亮右暗）
- 移除系统信息卡片，快捷导航扩展为 4×2 网格布局
- 简化仪表盘整体布局，放大关键数字排版
- 移除仪表盘区域背景色覆盖
- 更新仪表盘相关 TypeScript 类型定义与 API 函数
- 更新仪表盘 i18n 多语言翻译

### Fixed
- 修复仪表盘中 `appStore` 属性名引用错误

## [1.1.0] - 2026-03-23

### Added
- 微信支付多端适配：小程序 JSAPI、公众号 JSAPI、H5 MWEB、APP、PC Native 五种支付方式自动路由
- 客户端平台识别：`X-Client-Type` 请求头（miniapp/wechat_h5/h5/app/pc），后端白名单校验
- 多 AppID 支付配置：按平台自动选择小程序/公众号/开放平台/移动应用 AppID
- JSAPI/APP 支付参数二次签名：`buildJsapiParams()`、`buildAppParams()` 方法
- 微信平台证书自动下载与缓存（无需手动配置 cert_path）
- 小程序微信快捷登录 + 手机号绑定（`wechatQuickLogin`、`wechatBindPhone` 接口）
- H5 公众号 OAuth 静默授权获取 oa_openid（`wechat-oauth.ts`）
- H5 微信浏览器 WeixinJSBridge 调起支付
- PC 端充值二维码展示 + 轮询支付状态（qrcode 库）
- 用户表新增 `oa_openid` 字段，支付订单表新增 `client_type` 字段
- 注册成功后自动登录（token + userInfo 同步写入 store）
- `notify_url` 支持相对路径，运行时自动补全域名

### Changed
- `PaymentManager::getWechatConfig()` 改为 public，新增多端 appid 配置加载
- `WechatPayDriver::create()` 支持动态 appid 参数
- `WechatPayDriver::query()` 使用 URI 模板避免订单号大写被 normalize 转义
- `PaymentService::createOrder()` 存储 client_type 到订单记录
- `UserController::recharge()` 根据客户端类型自动路由支付方式
- `PaymentController::query()` 不再强制要求 channel 参数，自动从订单记录获取
- `WechatController::oauthCallback()` 支持 SPA 重定向模式和 JSON 模式
- `OfficialAccountService::getUserByCode()` 返回 unionid 字段

### Fixed
- 修复微信支付未启用时返回 500 错误（改为友好提示）
- 修复微信支付 V3 SDK certs 参数为空导致初始化失败
- 修复微信 WXSS 编译错误（UnoCSS presetUno → presetWeapp）
- 修复发现页 tabs 四周边距不合理及多余 scroll-view
- 修复 H5 微信 OAuth 死循环（前端直接处理 code 参数）
- 修复 el-tree-select `value` 属性 TS 类型错误（改用 `node-key`）
- 修复 el-tag type 属性 TS 联合类型不匹配
- 修复微信支付查询订单号大写被转为 kebab-case（W → -w）
- 修复 ORDER_NOT_EXIST 轮询报错暴露给用户（静默返回 pending）

## [1.0.0] - 2026-03-20

### Added

#### Admin 后台管理
- 基于 Vue 3 + TypeScript + Element Plus + Vite + Pinia 的管理后台
- 动态路由系统，通过后端菜单数据自动生成
- 用户管理、角色权限、菜单管理
- 文章管理（分类、标签、封面、富文本编辑）
- 公告管理、反馈管理、协议管理
- 系统配置（站点设置、上传配置、支付配置等）
- 余额记录、积分记录管理
- 控制台仪表盘（统计卡片、登录趋势图表）
- 操作日志、登录日志
- 代码生成器（自动生成 CRUD 全栈代码）

#### Server 后端服务
- 基于 ThinkPHP 8 + PHP 8.0+ 的 RESTful API 服务
- 分层架构：Controller → Service → Repository → Model + Listener + Job
- 自动依赖注入（DI）
- JWT 认证与 RBAC 权限控制
- 支付系统（微信支付、支付宝）
- 余额/积分体系
- 消息通知系统（站内信、短信）
- 事件驱动的副作用处理（Listener 机制）
- 文件上传（本地、阿里云 OSS、腾讯云 COS、七牛云）
- 安装向导（含演示数据与动态 URL 替换）
- 开放平台（OAuth 第三方登录）

#### PC 前台网站
- 基于 Nuxt 3 (SPA) + Naive UI + UnoCSS 的前台网站
- 文章列表与详情（分类筛选、标签、阅读量）
- 用户中心（个人资料、密码修改、余额充值、积分明细）
- 登录注册（密码、短信、微信扫码）
- 全局错误页面（404/500）

#### UniApp 移动端
- 基于 uni-app + Vue 3 + wot-design-uni 的移动端应用
- 首页（轮播图、公告栏、功能入口、最新文章）
- 发现页（文章分类筛选、下拉刷新、上拉加载）
- 消息中心
- 个人中心（资料编辑、余额、积分）
- 文章详情（富文本渲染、标签展示）
- 反馈、公告、协议页面
