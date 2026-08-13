// uno.config.ts
import {
    defineConfig,
    presetAttributify,
    presetIcons,
    presetTypography,
    presetWebFonts,
    transformerDirectives,
    transformerVariantGroup,
} from "unocss";
import presetWind3 from '@unocss/preset-wind3'
import { FileSystemIconLoader } from "@iconify/utils/lib/loader/node-loaders";
import fs from "fs";

// 本地SVG图标目录
const iconsDir = "./src/assets/icons";

// 读取本地 SVG 目录，自动生成 safelist
const generateSafeList = () => {
    try {
        return fs
            .readdirSync(iconsDir)
            .filter((file) => file.endsWith(".svg"))
            .map((file) => `i-svg:${file.replace(".svg", "")}`);
    } catch (error) {
        console.error("无法读取图标目录:", error);
        return [];
    }
};

const staticSafeList = [
  'text-primary','bg-primary','border-primary','bg-sidebar','bg-sidebar-active',
  'text-success','bg-success','border-success',
  'text-warning','bg-warning','border-warning',
  'text-danger','bg-danger','border-danger',
  'text-info','bg-info','border-info',
]

// 后端菜单/字典中通过 DB 写入的 lucide 图标类 + 业务组件中的动态图标字符串，
// UnoCSS 扫源码扫不到，必须显式 safelist
const lucideMenuIcons = [
  'i-lucide:bar-chart-3',
  'i-lucide:check-circle',
  'i-lucide:coins',
  'i-lucide:credit-card',
  'i-lucide:file-x',
  'i-lucide:gift',
  'i-lucide:link',
  'i-lucide:medal',
  'i-lucide:menu',
  'i-lucide:message-circle',
  'i-lucide:monitor',
  'i-lucide:paintbrush',
  'i-lucide:share-2',
  'i-lucide:shopping-bag',
  'i-lucide:ticket',
  'i-lucide:user',
  'i-lucide:user-circle',
  'i-lucide:wand-sparkles',
  // 会员详情 — 操作日志条目动态 icon（从 logEntries 数组取，需 safelist）
  'i-lucide:shopping-cart',
  'i-lucide:log-in',
  'i-lucide:wallet',
  'i-lucide:truck',
  'i-lucide:message-square',
  'i-lucide:cake',
  // v2.7.0 渠道管理 / 应用管理 / 插件菜单图标
  'i-lucide:radio-tower',     // Channel 一级菜单
  'i-lucide:layout-grid',     // 已安装应用
  'i-lucide:layout-template', // 插件菜单
  'i-lucide:file-text',       // 文章管理 / 协议管理
  'i-lucide:package',         // 抽奖发货
  'i-lucide:percent',         // 满减
  'i-lucide:calendar-check',  // 签到
  'i-lucide:user-plus',       // 新人礼包
  'i-lucide:users',           // 拼团
  'i-lucide:zap',             // 秒杀
]

export default defineConfig({
    // 只在 content.pipeline.include 中声明文件扫描路径，切勿再保留顶层 include
    content: {
        pipeline: {
            include: [
                './index.html',
                './src/**/*.{vue,js,ts,jsx,tsx}',
            ],
        },
    },

    presets: [
        presetWind3(),
        presetAttributify(),
        presetIcons({
            // 额外属性
            extraProperties: {
                display: "inline-block",
                width: "1em",
                height: "1em",
            },
            // 图表集合
            collections: {
                // svg 是图标集合名称，使用 `i-svg:图标名` 调用
                svg: FileSystemIconLoader(iconsDir, (svg) => {
                    // 如果 `fill` 没有定义，则添加 `fill="currentColor"`
                    return svg.includes('fill="') ? svg : svg.replace(/^<svg /, '<svg fill="currentColor" ');
                }),
            },
        }),
        presetTypography(),
        presetWebFonts({
            fonts: {
                // ...
            },
        }),
    ],

    theme: {
        colors: {
        primary: 'var(--color-primary)',
        'primary-hover': 'var(--color-primary-hover)',
        'primary-active': 'var(--color-primary-active)',
        success: 'var(--color-success-plain)',
        warning: 'var(--color-warning-plain)',
        danger:  'var(--color-danger-plain)',
        info:    'var(--color-info-plain)',

        // 中性色常用别名（按需使用）
        'text-primary':   'var(--color-text-primary)',
        'text-secondary': 'var(--color-text-secondary)',
        'text-tertiary':  'var(--color-text-tertiary)',
        'border':         'var(--color-border)',
        'divider':        'var(--color-divider)',
        'bg':             'var(--color-bg)',
        'surface':        'var(--color-surface)',
        'sidebar':        'var(--color-sidebar-bg)',
        'sidebar-active': 'var(--color-sidebar-active)',

        // 中性色板（src/theme/primitives.scss 定义的 CSS 变量）
        // 注册到 UnoCSS theme.colors 后，text-ink-500 / bg-ink-50 / border-ink-100 等类才能正确生成
        ink: {
            50:  'var(--ink-50)',
            100: 'var(--ink-100)',
            200: 'var(--ink-200)',
            300: 'var(--ink-300)',
            400: 'var(--ink-400)',
            500: 'var(--ink-500)',
            600: 'var(--ink-600)',
            700: 'var(--ink-700)',
            800: 'var(--ink-800)',
            900: 'var(--ink-900)',
        },
        brand: {
            50:  'var(--brand-50)',
            100: 'var(--brand-100)',
            400: 'var(--brand-400)',
            500: 'var(--brand-500)',
            600: 'var(--brand-600)',
        },
        rose:   { 50: 'var(--rose-50)',   500: 'var(--rose-500)' },
        amber:  { 50: 'var(--amber-50)',  500: 'var(--amber-500)' },
        teal:   { 50: 'var(--teal-50)',   500: 'var(--teal-500)' },
        purple: { 50: 'var(--purple-50)', 500: 'var(--purple-500)' },
        },
        fontFamily: {
        sans: 'var(--font-family-sans)',
        },
        // 也可扩展自定义屏幕断点与 spacing，但 Uno 原生就很好用了
    },
    shortcuts: [
        // 文字层级
        ['text-display',  'text-[var(--font-size-display)] leading-[var(--line-height-tight)] font-[var(--font-weight-bold)]'],
        ['text-title',    'text-[var(--font-size-title)] leading-[var(--line-height-tight)] font-[var(--font-weight-semibold)]'],
        ['text-subtitle', 'text-[var(--font-size-subtitle)] leading-[var(--line-height-default)] font-[var(--font-weight-medium)]'],
        ['text-body',     'text-[var(--font-size-body)] leading-[var(--line-height-default)] font-[var(--font-weight-regular)]'],
        ['text-caption',  'text-[var(--font-size-caption)] leading-[var(--line-height-default)] text-text-tertiary'],

        // 按钮与卡片
        ['btn',          'px-4 py-2 inline-flex items-center justify-center rounded-[var(--radius-md)] transition-all duration-[var(--motion-duration-base)] ease-[var(--motion-easing)] shadow-[var(--shadow-sm)]'],
        ['btn-primary',  'btn bg-primary text-white hover:opacity-95 active:opacity-85 focus:outline-none focus:ring-2 ring-[var(--color-focus-ring)]'],
        ['card',         'bg-surface border border-[var(--color-border)] rounded-[var(--radius-lg)] shadow-[var(--shadow-md)]'],

        // 帮助类
        ['bg-body',      'bg-[var(--color-bg)]'],
        ['disabled',     'opacity-[var(--opacity-disabled)] pointer-events-none'],
        ['divider',      'h-[1px] bg-[var(--color-divider)]'],
    ],
    safelist: [
        ...staticSafeList,
        ...generateSafeList(),
        ...lucideMenuIcons,
    ],
    transformers: [transformerDirectives(), transformerVariantGroup()],
})
