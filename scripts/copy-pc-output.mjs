#!/usr/bin/env node
import { cpSync, existsSync, rmSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = dirname(dirname(fileURLToPath(import.meta.url)))
const src = resolve(root, 'pc/.output/public')
const destName = process.env.BUILD_TMP === '1' ? 'pc.build-tmp' : 'pc'
const dest = resolve(root, 'server/public', destName)
if (!existsSync(src)) {
    console.error('[copy-pc-output] missing', src)
    process.exit(1)
}
rmSync(dest, { recursive: true, force: true })
cpSync(src, dest, { recursive: true })
console.log('[copy-pc-output]', destName)
