/**
 * Theme utilities — light-only color swapper for Shop Admin.
 * 3 functions: setPrimaryColor, setCompact, setSidebarLabels.
 */

interface BrandPalette {
    b500: string
    b600: string
    b400: string
    b50: string
    b100: string
    shadow: string
}

export const COLOR_MAP: Record<string, BrandPalette> = {
    '#4f6bff': {
        b500: '#4f6bff',
        b600: '#3f58e6',
        b400: '#7a8dff',
        b50: '#eef1ff',
        b100: '#dde3ff',
        shadow: 'rgba(79, 107, 255, 0.35)'
    },
    '#0ea5e9': {
        b500: '#0ea5e9',
        b600: '#0284c7',
        b400: '#38bdf8',
        b50: '#e0f2fe',
        b100: '#bae6fd',
        shadow: 'rgba(14, 165, 233, 0.35)'
    },
    '#14b8a6': {
        b500: '#14b8a6',
        b600: '#0d9488',
        b400: '#5eead4',
        b50: '#e6fbf7',
        b100: '#ccf5ed',
        shadow: 'rgba(20, 184, 166, 0.35)'
    },
    '#8b5cf6': {
        b500: '#8b5cf6',
        b600: '#7c3aed',
        b400: '#a78bfa',
        b50: '#f3edff',
        b100: '#e9dcff',
        shadow: 'rgba(139, 92, 246, 0.35)'
    },
    '#f97316': {
        b500: '#f97316',
        b600: '#ea580c',
        b400: '#fb923c',
        b50: '#fff4e9',
        b100: '#ffe1c6',
        shadow: 'rgba(249, 115, 22, 0.35)'
    }
}

export const PRESET_COLORS = Object.keys(COLOR_MAP)

const root = document.documentElement

export function setPrimaryColor(color: string): void {
    const palette = COLOR_MAP[color]
    if (!palette) return

    root.style.setProperty('--brand-500', palette.b500)
    root.style.setProperty('--brand-600', palette.b600)
    root.style.setProperty('--brand-400', palette.b400)
    root.style.setProperty('--brand-50', palette.b50)
    root.style.setProperty('--brand-100', palette.b100)
    root.style.setProperty('--brand-shadow', palette.shadow)
    root.style.setProperty('--el-color-primary', palette.b500)
}

export function setCompact(enabled: boolean): void {
    document.body.classList.toggle('compact', enabled)
}

export function setSidebarLabels(show: boolean): void {
    document.body.classList.toggle('hide-labels', !show)
}

export function applySettings(settings: {
    primaryColor: string
    compact: boolean
    sidebarLabels: boolean
}): void {
    setPrimaryColor(settings.primaryColor)
    setCompact(settings.compact)
    setSidebarLabels(settings.sidebarLabels)
}
