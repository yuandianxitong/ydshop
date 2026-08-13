/**
 * Simple syntax highlighter for code generator result preview.
 * Supports PHP, TypeScript/JS, XML/Vue, and SQL.
 */

function escapeHtml(str: string): string {
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
}

function wrapSpan(cls: string, content: string): string {
    return `<span class="${cls}">${content}</span>`
}

export function highlightCode(lang: string, raw: string): string {
    const escaped = escapeHtml(raw)
    const lines = escaped.split('\n')
    const highlighted = lines.map((line) => {
        let out = line

        // Comments (PHP, JS use //, SQL uses --)
        if (lang === 'php' || lang === 'js') {
            out = out.replace(/(\/\/.*)$/gm, (_, m) => wrapSpan('cg-cmt', m))
        }
        if (lang === 'sql') {
            out = out.replace(/(--\s.*)$/gm, (_, m) => wrapSpan('cg-cmt', m))
        }

        // Strings
        out = out.replace(/(&quot;.*?&quot;)/g, (_, m) => wrapSpan('cg-str', m))
        out = out.replace(/('.*?')/g, (_, m) => wrapSpan('cg-str', m))

        // PHP attributes #[...]
        if (lang === 'php') {
            out = out.replace(/(#\[[\w\\]+(?:\(.*?\))?\])/g, (_, m) => wrapSpan('cg-anno', m))
        }

        // PHP variables $xxx
        if (lang === 'php') {
            out = out.replace(/(\$[a-zA-Z_]\w*)/g, (_, m) => wrapSpan('cg-tag', m))
        }

        // XML/HTML tags
        if (lang === 'xml') {
            out = out.replace(/(&lt;\/?[\w-]+)/g, (_, m) => wrapSpan('cg-tag', m))
            out = out.replace(/(\/?\s*&gt;)/g, (_, m) => wrapSpan('cg-tag', m))
        }

        // Keywords
        if (lang === 'php') {
            out = out.replace(
                /\b(namespace|use|class|extends|implements|function|public|protected|private|static|return|new|if|else|foreach|as|array|null|true|false|throw|try|catch|readonly|const|abstract|interface|match|fn|int|string|float|bool|mixed|void|self)\b/g,
                (_, m) => wrapSpan('cg-kw', m)
            )
        }
        if (lang === 'js') {
            out = out.replace(
                /\b(import|export|from|const|let|var|function|return|if|else|for|while|async|await|type|interface|extends)\b/g,
                (_, m) => wrapSpan('cg-kw', m)
            )
        }
        if (lang === 'sql') {
            out = out.replace(
                /\b(SELECT|FROM|WHERE|INSERT|INTO|VALUES|UPDATE|SET|DELETE|CREATE|ALTER|DROP|TABLE|INDEX|AND|OR|NOT|NULL|PRIMARY|KEY|FOREIGN|REFERENCES|DEFAULT|UNSIGNED|AUTO_INCREMENT|COMMENT|ENGINE|CHARSET)\b/gi,
                (_, m) => wrapSpan('cg-kw', m)
            )
        }

        // Numbers
        out = out.replace(/\b(\d+)\b/g, (_, m) => wrapSpan('cg-num', m))

        return out
    })
    return highlighted.join('\n')
}

export function getFileLang(name: string): string {
    if (name.endsWith('.php')) return 'php'
    if (name.endsWith('.vue') || name.endsWith('.html')) return 'xml'
    if (name.endsWith('.ts') || name.endsWith('.tsx') || name.endsWith('.js')) return 'js'
    if (name.endsWith('.sql')) return 'sql'
    return 'text'
}

export function getFileIcon(name: string): string {
    if (name.endsWith('.php')) return 'i-svg:file-php'
    if (name.endsWith('.vue')) return 'i-svg:file-vue'
    if (name.endsWith('.ts') || name.endsWith('.tsx')) return 'i-svg:file-ts'
    if (name.endsWith('.sql')) return 'i-svg:file-sql'
    return 'i-svg:file-text'
}
