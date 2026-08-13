# AGENTS.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目架构

这是一个前后端分离的管理系统项目，采用以下技术栈：

### 前端 (admin/)
- **技术栈**: Vue 3 + TypeScript + Element Plus + Vite + Pinia
- **路由系统**: 动态路由，通过后端菜单数据生成
- **状态管理**: Pinia，主要的store在 `src/store/modules/` 目录
- **样式系统**: SCSS + UnoCSS
- **组件库**: Element Plus + @element-plus/icons-vue

### 前端 (pc/)
- **技术栈**: Nuxt 3 (SPA模式) + TypeScript + Naive UI + UnoCSS (presetWind3)
- **请求工具**: ofetch，封装在 `composables/useRequest.ts`，token 存储在 localStorage (`pc_token`)
- **API 响应格式**: `{ code: 200, message: "...", data: {...} }`（与后端统一）
- **开发代理**: `vite.server.proxy` 转发 `/api` 和 `/storage` 到后端

### 后端 (server/)
- **技术栈**: ThinkPHP 8.0 + PHP 8.0+
- **数据库**: MySQL，时间戳字段使用 `created_at/updated_at/deleted_at` 格式
- **API版本**: v1，路由组织在 `app/adminapi/` 和 `app/api/` 下
- **架构模式**: Controller → Service → Repository → Model + Listener + Job

## 后端分层架构

### 架构总览
```
请求 → Controller → Service → Repository → Model
                       ↓
                    Listener（事件监听器，处理副作用）
                    Job（异步队列任务）
```

### 各层职责

| 层 | 目录 | 基类 | 职责 |
|---|---|---|---|
| Controller | `app/adminapi/controller/v1/` | `core\base\Controller` | 接收请求、参数校验、调用 Service、返回响应 |
| Service | `app/service/` | `core\base\Service` | 业务逻辑编排、事务管理、触发事件 |
| Repository | `app/repository/` | `core\base\Repository` | 数据访问封装、所有 ORM 查询集中于此 |
| Model | `app/model/` | `core\base\Model` | ORM 映射、关联关系、访问器/修改器 |
| Listener | `app/listener/` | — | 事件监听器，处理副作用（日志、通知、缓存清理） |
| Validate | `app/adminapi/validate/v1/` | — | 表单验证规则 |

### 依赖注入（DI）
Controller 和 Service 的基类都内置了自动 DI。子类只需声明带类型的 `protected` 属性，基类会自动从容器注入：
```php
class AdminService extends Service
{
    protected AdminRepository $adminRepository;   // 自动注入
    protected TokenManager $tokenManager;          // 自动注入
}
```

### 数据流向规则

**严格遵守：**
- Controller **只调用 Service**，不直接操作 Repository 或 Model
- Service **只调用 Repository**，不直接使用 `Db::table()` 或 Model 静态方法（如 `::where()`、`::find()`、`::create()`）
- Repository 封装所有 ORM 查询，是唯一与 Model 交互的层
- `Db::startTrans() / commit() / rollback()` 事务管理放在 Service 层
- `Db::query()` 仅允许在 CodeGeneratorService 中使用（元编程/Schema 内省）

**副作用处理：**
- Service 中通过 `$this->trigger('event.name', $data)` 触发事件
- 副作用逻辑（日志记录、缓存清理、通知发送等）放在 Listener 中
- 判断标准：如果该操作失败不影响主流程 → 放 Listener；必须成功 → 留在 Service

### 事件系统
事件配置在 `app/event.php`，事件 → 监听器映射：
```php
'listen' => [
    'admin.login.success' => [AdminLoginSuccessListener::class],
    'admin.login.failed'  => [AdminLoginFailedListener::class],
    'config.changed'      => [ConfigChangedListener::class],
    'user.register'       => [UserRegisterListener::class],
    'user.login'          => [UserLoginListener::class],
    'payment.success'     => [PaymentSuccessListener::class],
]
```

添加新的副作用只需在 `event.php` 中追加 Listener 类，无需修改 Service 代码。

### 操作日志
- **HTTP 级别**：`AdminLogMiddleware` 自动记录所有 POST/PUT/DELETE 请求到 `admin_operation_logs` 表
- **业务级别**：Service 中的 `$this->log()` 写入文件日志（非数据库）
- **登录日志**：通过 `admin.login.success/failed` 事件由 Listener 写入 `admin_login_logs` 表

## 开发命令

### 前端开发
```bash
cd admin
npm install
npm run dev          # 开发服务器
npm run build        # 构建生产版本
npm run type-check   # TypeScript类型检查
npm run lint         # ESLint代码检查和修复
```

### 后端开发
```bash
cd server
composer install
php think list       # 查看可用命令
php think make:crud  # CRUD 代码生成器
php think yd:update  # 框架数据库增量升级（database/updates/vX.Y.Z）
```

### 代码生成器
`php think make:crud` 或通过管理后台「代码生成」页面使用，自动生成：
- Model、Repository、Service、Controller、Validate、Route
- 前端 API 文件（TypeScript）、列表页和表单组件（Vue）

生成的代码严格遵循 `Controller → Service → Repository → Model` 分层架构。

## 核心架构概念

### 前端动态路由系统
- 路由通过后端接口 `/adminapi/system/config/global` 获取菜单数据动态生成
- 使用 `src/router/index.ts` 中的 `filterAsyncRoutes` 和 `loadRouteView` 处理
- 页面组件存放在 `src/pages/` 下，自动映射到路由

### 认证与权限
- 使用JWT token认证，在请求拦截器中自动添加Authorization头
- 权限通过中间件 `admin_auth` 和 `admin_permission` 控制
- token过期时会自动跳转到登录页面

### 文件上传系统
- 上传接口: `/adminapi/upload/image` 和 `/adminapi/upload/file`
- 文件存储在 `server/public/storage/uploads/` 下
- 返回完整URL用于前端显示，相对路径用于数据库存储
- 通过 `appStore.getImageUrl()` 方法处理图片URL拼接

### 系统配置管理
- 配置存储在 `system_configs` 表中，支持分组管理
- 配置类型: string、number、boolean、json、file
- 前端通过 `auth.guard.ts` 在应用启动时获取全局配置
- 配置更新后通过 `config.changed` 事件自动清除缓存

## 重要约定

### 数据库字段命名
- 时间戳字段: `created_at`, `updated_at`, `deleted_at`
- 状态字段: `status` (1启用, 0禁用)
- 软删除统一使用 `deleted_at` 字段

### 安装数据 (init.sql)
- 菜单和权限数据在 `server/public/install/data/init.sql`，表结构在 `schema.sql`
- 新增菜单时必须检查 menus 表已用 ID（包括子按钮 ID），避免 PRIMARY KEY 冲突
- 一级菜单 ID 分配：1-8 已用，9=用户管理；子菜单按 x00 段分配（如 900-920）
- 新增表结构时同步更新 `schema.sql`，否则全新安装会缺表导致 DI 注入失败

### API响应格式
```php
// 成功响应
return $this->success('操作成功', $data);

// 错误响应
return $this->error('错误信息');
```

### Controller 参数校验
```php
// 正确的 validate() 调用签名（第一个参数是数据数组，不是验证类）
$data = $this->request->post();
$this->validate($data, UserManageValidate::class, [], false, 'sceneName');
```

### 前端请求处理
- 使用 `src/utils/request.ts` 中的 `myRequest` 进行API调用
- 响应拦截器自动处理token过期和错误提示
- 避免在响应处理中造成循环跳转

### 组件开发规范
- Vue组件使用Composition API + TypeScript
- 表单验证使用Element Plus的FormInstance
- 文件上传使用el-upload组件，参考系统配置页面的实现

### 新增模块开发规范
1. 使用代码生成器创建基础 CRUD 文件
2. 如果有副作用需求（日志、通知等），创建对应的 Listener 并注册到 `event.php`
3. Service 中通过 `$this->trigger()` 触发事件，不要内联副作用逻辑
4. 所有数据库查询必须封装在 Repository 中，Service 不允许直接调用 `Db::table()` 或 Model 静态方法
5. Model 中定义 `getXxxAttr()` 访问器时，必须同时声明 `protected $append = ['xxx']` 才会出现在 `toArray()` 输出中
6. 日志类表（只追加不修改）的 Model 设置 `$updateTime = false` 和 `$deleteTime = false`，表结构不需要 `updated_at` / `deleted_at` 字段

## Git 提交规范

采用约定式提交（Conventional Commits）：

**格式**
```
<type>(<scope>): <subject>
```
- `<scope>` 可省略，使用模块名（如 `auth`、`profile`、`api`、`ui`、`db`）
- `<subject>` 使用祈使句、首字母小写、结尾不加句号；尽量控制在 72 字符内

**类型**
- `feat` 新功能
- `fix` 修复缺陷
- `docs` 文档变更
- `style` 仅格式/样式调整（不改逻辑）
- `refactor` 重构（不新增功能、不修复缺陷）
- `perf` 性能优化
- `test` 测试相关
- `chore` 杂项/工具链
- `build` 构建系统或依赖变更
- `ci` CI 配置变更
- `revert` 回滚提交

**Body / Footer（可选）**
- Body：说明动机、关键实现、影响面
- Footer：如有破坏性变更，使用 `BREAKING CHANGE: ...`

**示例**
- `feat(api): 添加验证码以进行登录`
- `fix(profile): 对齐排版标记`
- `docs: 添加子模块工作流程`

## 版本管理

### 版本号规范
遵循 [语义化版本 (SemVer)](https://semver.org/lang/zh-CN/)：
- **MAJOR (主版本)**: 不兼容的 API 变更、数据库结构破坏性变更
- **MINOR (次版本)**: 新增功能模块、新增 API 端点（向后兼容）
- **PATCH (修订版本)**: Bug 修复、性能优化、样式调整（向后兼容）

### 发版流程
采用**积累发版**模式，日常按 Conventional Commits 提交，积累到合适节点统一发版：

1. 确认要发布的版本号（根据变更内容判断 MAJOR/MINOR/PATCH）
2. 更新 `CHANGELOG.md`：将 `[Unreleased]` 中的内容移入新版本号章节，注明日期
3. 提交变更：`git commit -m "chore: release vX.Y.Z"`
4. 创建 annotated tag：`git tag -a vX.Y.Z -m "vX.Y.Z: 版本描述"`
5. 推送代码和 tag：`git push && git push --tags`

### CHANGELOG 更新规范
- 格式遵循 [Keep a Changelog](https://keepachangelog.com/zh-CN/)
- 新版本始终添加在 `[Unreleased]` 之后、上一版本之前
- 变更分类：`Added`（新增）、`Changed`（变更）、`Fixed`（修复）、`Removed`（移除）
- 每条变更使用简洁的祈使句描述
- 开发过程中新增功能或修复时，及时记录到 `[Unreleased]` 区域

### 版本升级判断
| 变更类型 | 版本升级 | 示例 |
|---|---|---|
| 新增功能模块 | MINOR | 新增优惠券系统 |
| 新增 API 端点 | MINOR | 新增 /api/coupon/list |
| Bug 修复 | PATCH | 修复登录页跳转问题 |
| 性能优化 | PATCH | 仪表盘查询缓存优化 |
| 数据库表结构破坏性变更 | MAJOR | 重命名核心表字段 |
| 移除已有 API | MAJOR | 删除 /api/v1/old-endpoint |

### 数据库升级机制（重要）
框架发行版升级的**唯一数据库机制**是 `php think yd:update`（框架自身不再使用 think-migration）。

- 老用户升级：`cd server && php think yd:update --dry-run` 预览，再 `php think yd:update` 执行。升级器自动套用当前表前缀（复用 `core/database/SqlRunner`），并把已应用版本记录在 `system_upgrades` 表中，保证幂等、可断点续跑。
- 首次使用升级系统的老库需用 `--baseline=<当前版本>` 确立基线（如 `php think yd:update --baseline=2.14.0`）；全新安装由安装程序自动 seed 基线，无需操作。
- 升级脚本一律书写**裸表名**（不写前缀），详见 `server/database/updates/README.md`。
- 框架自身的 `database/migrations/`、`database/seeds/` 已移除；代码生成器不再产出迁移文件，表结构变更统一走 `database/updates/vX.Y.Z/`。
- **插件数据库生命周期与框架升级相互独立**：由 `PluginManager` 执行各插件 `database/install.sql` / `database/updates/vX.Y.Z.sql` / `database/uninstall.sql`（经 `SqlRunner`）；版本记录为 `plugin_migrations` 中的 semver。`PluginMigrationRunner` 仅为 pending SQL updates 的薄封装。项目已移除 `topthink/think-migration`。

### 发版数据规范
- `server/public/install/data/schema.sql` 和 `init.sql` 始终保持最新完整状态，新安装用户直接获得当前版本的全部表结构和初始数据
- 如果本次发版涉及数据库变更（表结构、初始数据修改），必须同时：
    1. 更新 `schema.sql` / `init.sql`（保证新安装正确）
    2. 在 `server/database/updates/vX.Y.Z/update.sql`（增量 SQL，裸表名）和/或 `update.php`（需 PHP 逻辑的数据迁移钩子）写升级脚本（保证老用户 `php think yd:update` 可升级）
    3. 在 `server/database/updates/vX.Y.Z/README.md` 写本版本升级说明
- 补丁 SQL 不允许放在 `server/public/install/data/` 目录，统一放 `server/database/updates/` 目录
- 更新 `server/config/version.php` 中的版本号
- 没有数据库变更的版本不需要创建 updates 目录

## 常见问题解决

### Token循环请求问题
如果遇到token过期导致的循环请求，检查：
- `src/router/guards/auth.guard.ts` 中是否正确处理登录页面跳过逻辑
- `src/store/modules/app.store.ts` 中的 `getConfig` 方法是否使用了请求锁
- 响应拦截器中是否避免了重复跳转

### 表单验证问题
在Tab组件中的表单可能会有ref引用问题，可以：
- 跳过非必要的表单验证
- 使用条件检查: `if (formRef.value && typeof formRef.value.validate === 'function')`

### 文件上传URL问题
前后端分离项目中，确保：
- 后端返回完整URL用于前端显示
- 数据库存储相对路径，便于域名迁移
- 使用 `appStore.getImageUrl()` 处理图片URL拼接

## Shop 商城系统

基于 ydadmin 框架的 B2C 自营商城。

### 商城模块
- 商品中心：SPU/SKU 多规格、参数属性、虚拟商品、组合套餐
- 订单系统：下单/支付/发货/退款状态机
- 用户/会员/分销：会员等级、积分余额、二级分销
- 营销插件：优惠券/满减/秒杀/拼团（plugins/ 目录）
- AI 选品助手：多模型适配

### 数据库表前缀
- goods_ 商品 | order_ 订单 | member_ 会员
- distribution_ 分销 | marketing_ 营销 | ai_ AI
