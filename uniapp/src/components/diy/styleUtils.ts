export interface ComponentStyle {
    margin: { top: number; right: number; bottom: number; left: number }
    padding: { top: number; right: number; bottom: number; left: number }
    background: {
        type: 'color' | 'gradient' | 'image'
        color: string
        gradientStart: string
        gradientEnd: string
        gradientDirection: string
        image: string
    }
    borderRadius: { topLeft: number; topRight: number; bottomRight: number; bottomLeft: number; linked: boolean }
    boxShadow: { x: number; y: number; blur: number; color: string }
    border: { width: number; color: string; style: 'solid' | 'dashed' | 'dotted' | 'none' }
    opacity: number
}

export const defaultComponentStyle: ComponentStyle = {
    margin: { top: 0, right: 0, bottom: 0, left: 0 },
    padding: { top: 0, right: 0, bottom: 0, left: 0 },
    background: { type: 'color', color: 'transparent', gradientStart: '#ffffff', gradientEnd: '#000000', gradientDirection: 'to bottom', image: '' },
    borderRadius: { topLeft: 0, topRight: 0, bottomRight: 0, bottomLeft: 0, linked: true },
    boxShadow: { x: 0, y: 0, blur: 0, color: 'rgba(0,0,0,0.1)' },
    border: { width: 0, color: '#e0e0e0', style: 'solid' },
    opacity: 100,
}

/**
 * Convert ComponentStyle to a CSS style object.
 * @param style - The component style config
 * @param platform - 'uniapp' uses rpx (2x), others use px
 */
export function componentStyleToCss(style?: Partial<ComponentStyle>, platform?: string): Record<string, string> {
    if (!style) return {}
    const css: Record<string, string> = {}
    const unit = platform === 'uniapp' ? 'rpx' : 'px'
    const px = (v: number) => v ? `${platform === 'uniapp' ? v * 2 : v}${unit}` : '0'

    // Margin
    if (style.margin) {
        const m = style.margin
        if (m.top || m.right || m.bottom || m.left) {
            css.margin = `${px(m.top)} ${px(m.right)} ${px(m.bottom)} ${px(m.left)}`
        }
    }

    // Padding
    if (style.padding) {
        const p = style.padding
        if (p.top || p.right || p.bottom || p.left) {
            css.padding = `${px(p.top)} ${px(p.right)} ${px(p.bottom)} ${px(p.left)}`
        }
    }

    // Background
    if (style.background) {
        const bg = style.background
        if (bg.type === 'color' && bg.color && bg.color !== 'transparent') {
            css.background = bg.color
        } else if (bg.type === 'gradient' && bg.gradientStart && bg.gradientEnd) {
            css.background = `linear-gradient(${bg.gradientDirection || 'to bottom'}, ${bg.gradientStart}, ${bg.gradientEnd})`
        } else if (bg.type === 'image' && bg.image) {
            css.background = `url(${bg.image}) center/cover no-repeat`
        }
    }

    // Border Radius
    if (style.borderRadius) {
        const r = style.borderRadius
        if (r.topLeft || r.topRight || r.bottomRight || r.bottomLeft) {
            css.borderRadius = `${px(r.topLeft)} ${px(r.topRight)} ${px(r.bottomRight)} ${px(r.bottomLeft)}`
        }
    }

    // Box Shadow
    if (style.boxShadow) {
        const s = style.boxShadow
        if (s.blur || s.x || s.y) {
            css.boxShadow = `${px(s.x)} ${px(s.y)} ${px(s.blur)} ${s.color}`
        }
    }

    // Border
    if (style.border && style.border.width) {
        css.border = `${px(style.border.width)} ${style.border.style} ${style.border.color}`
    }

    // Opacity
    if (style.opacity !== undefined && style.opacity < 100) {
        css.opacity = String(style.opacity / 100)
    }

    return css
}
