#!/usr/bin/env node
/**
 * 同步插件前端到宿主（symlink，Windows 无软链时 copy）。
 *
 *   node scripts/sync-plugins.mjs --target admin
 *   node scripts/sync-plugins.mjs --target pc --clean
 *
 * admin：server/plugins/<code>/admin/src/{views,api,locales,components}
 *        → admin/src/<module>/plugins/<code>
 *        api/components 另在原路径做兼容软链，保证 @/api/coupon 仍可用
 * pc：   server/plugins/<code>/pc → pc/plugins/<code>
 *        并把 pc/pages|api|components 按相对路径链回宿主，供 Nuxt 文件路由
 */
import {
    existsSync,
    mkdirSync,
    readdirSync,
    rmSync,
    statSync,
    symlinkSync,
    writeFileSync,
    readFileSync,
    unlinkSync,
    lstatSync,
    cpSync,
} from 'node:fs'
import { dirname, resolve, relative, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const repoRoot = resolve(__dirname, '..')
const MANIFEST_NAME = '.plugin-sync-manifest.json'
const ADMIN_MODULES = ['views', 'api', 'locales', 'components']
const PC_COMPAT = ['pages', 'api', 'components']

function parseArgs(argv) {
    const args = { target: null, clean: false }
    for (let i = 2; i < argv.length; i++) {
        if (argv[i] === '--target') args.target = argv[++i]
        else if (argv[i] === '--clean') args.clean = true
    }
    if (!['admin', 'pc'].includes(args.target)) {
        throw new Error('usage: --target admin|pc [--clean]')
    }
    return args
}

function readManifest(srcRoot) {
    const p = resolve(srcRoot, MANIFEST_NAME)
    if (!existsSync(p)) return { links: [] }
    return JSON.parse(readFileSync(p, 'utf8'))
}

function writeManifest(srcRoot, data) {
    writeFileSync(resolve(srcRoot, MANIFEST_NAME), JSON.stringify(data, null, 2))
}

function tryUnlink(p) {
    try {
        const stat = lstatSync(p)
        if (stat.isSymbolicLink() || stat.isFile()) unlinkSync(p)
        else rmSync(p, { recursive: true, force: true })
    } catch {
        /* ignore */
    }
}

function linkOrCopy(from, to) {
    mkdirSync(dirname(to), { recursive: true })
    tryUnlink(to)
    try {
        symlinkSync(relative(dirname(to), from), to, statSync(from).isDirectory() ? 'dir' : 'file')
        return 'link'
    } catch {
        cpSync(from, to, { recursive: true })
        return 'copy'
    }
}

function walkFiles(dir, acc = [], prefix = '') {
    if (!existsSync(dir)) return acc
    for (const name of readdirSync(dir)) {
        const abs = join(dir, name)
        const rel = prefix ? `${prefix}/${name}` : name
        if (statSync(abs).isDirectory()) walkFiles(abs, acc, rel)
        else acc.push(rel)
    }
    return acc
}

function cleanup(srcRoot, extraDirs = []) {
    const manifest = readManifest(srcRoot)
    for (const link of manifest.links || []) {
        tryUnlink(resolve(srcRoot, link))
    }
    for (const dir of extraDirs) {
        if (existsSync(dir) && readdirSync(dir).length === 0) {
            try {
                rmSync(dir, { recursive: true })
            } catch {
                /* ignore */
            }
        }
    }
    const mf = resolve(srcRoot, MANIFEST_NAME)
    if (existsSync(mf)) unlinkSync(mf)
}

function syncAdmin() {
    const srcRoot = resolve(repoRoot, 'admin/src')
    const pluginsRoot = resolve(repoRoot, 'server/plugins')
    cleanup(srcRoot)
    const created = []
    if (!existsSync(pluginsRoot)) {
        console.log('[sync] admin: 无插件目录')
        return
    }
    for (const code of readdirSync(pluginsRoot)) {
        const pluginSrc = resolve(pluginsRoot, code, 'admin/src')
        if (!existsSync(pluginSrc) || !statSync(resolve(pluginsRoot, code)).isDirectory()) continue
        if (code.startsWith('.')) continue
        for (const mod of ADMIN_MODULES) {
            const sourceDir = resolve(pluginSrc, mod)
            if (!existsSync(sourceDir)) continue
            const bucket = mod === 'views' ? 'plugin-apps' : 'plugins'
            const linkPath = resolve(srcRoot, mod, bucket, code)
            const kind = linkOrCopy(sourceDir, linkPath)
            created.push(relative(srcRoot, linkPath))
            console.log(`[sync] admin ${kind}: ${mod}/${bucket}/${code}`)
            if (mod === 'api' || mod === 'components') {
                for (const rel of walkFiles(sourceDir)) {
                    const dest = resolve(srcRoot, mod, rel)
                    if (existsSync(dest) && !lstatSync(dest).isSymbolicLink()) continue
                    linkOrCopy(resolve(sourceDir, rel), dest)
                    created.push(relative(srcRoot, dest))
                }
            }
        }
    }
    writeManifest(srcRoot, { links: created, syncedAt: new Date().toISOString() })
    console.log(`[sync] admin: ${created.length} 项`)
}

function syncPc() {
    const pcRoot = resolve(repoRoot, 'pc')
    const pluginsRoot = resolve(repoRoot, 'server/plugins')
    const generatedDir = resolve(pcRoot, 'generated')
    mkdirSync(generatedDir, { recursive: true })
    cleanup(pcRoot, [resolve(pcRoot, 'plugins')])
    const created = []
    const routes = []
    if (!existsSync(pluginsRoot)) {
        writeFileSync(
            resolve(generatedDir, 'plugin-routes.ts'),
            '// generated by scripts/sync-plugins.mjs\nexport const pluginRoutes = [] as const\n'
        )
        console.log('[sync] pc: 无插件目录')
        return
    }
    for (const code of readdirSync(pluginsRoot)) {
        if (code.startsWith('.')) continue
        const pcDir = resolve(pluginsRoot, code, 'pc')
        if (!existsSync(pcDir) || !statSync(resolve(pluginsRoot, code)).isDirectory()) continue
        const bundle = resolve(pcRoot, 'plugins', code)
        linkOrCopy(pcDir, bundle)
        created.push(relative(pcRoot, bundle))
        for (const mod of PC_COMPAT) {
            const sourceDir = resolve(pcDir, mod)
            if (!existsSync(sourceDir)) continue
            for (const rel of walkFiles(sourceDir)) {
                const dest = resolve(pcRoot, mod, rel)
                if (existsSync(dest) && !lstatSync(dest).isSymbolicLink()) continue
                linkOrCopy(resolve(sourceDir, rel), dest)
                created.push(relative(pcRoot, dest))
                if (mod === 'pages' && rel.endsWith('.vue')) {
                    routes.push({
                        pluginCode: code,
                        route: '/' + rel.replace(/\.vue$/, '').replace(/\/index$/, ''),
                    })
                }
            }
        }
        console.log(`[sync] pc: plugins/${code}`)
    }
    writeManifest(pcRoot, { links: created, syncedAt: new Date().toISOString() })
    writeFileSync(
        resolve(generatedDir, 'plugin-routes.ts'),
        `// generated by scripts/sync-plugins.mjs\nexport const pluginRoutes = ${JSON.stringify(routes, null, 2)} as const\n`
    )
    console.log(`[sync] pc: ${created.length} 项, ${routes.length} 路由`)
}

const args = parseArgs(process.argv)
if (args.target === 'admin') {
    const srcRoot = resolve(repoRoot, 'admin/src')
    if (args.clean) {
        cleanup(srcRoot)
        console.log('[sync] admin: cleaned')
    } else {
        syncAdmin()
    }
} else {
    const pcRoot = resolve(repoRoot, 'pc')
    if (args.clean) {
        cleanup(pcRoot, [resolve(pcRoot, 'plugins')])
        console.log('[sync] pc: cleaned')
    } else {
        syncPc()
    }
}
