<script setup>
import { computed, onMounted, onUpdated, ref, nextTick } from 'vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'
import 'highlight.js/styles/github-dark.css'

const props = defineProps({
    content: { type: String, default: '' },
})

const md = new MarkdownIt({
    html:        false,
    linkify:     true,
    breaks:      true,
    typographer: true,
    highlight(str, lang) {
        const language = lang && hljs.getLanguage(lang) ? lang : ''
        const label    = language || 'text'
        const escaped  = language
            ? hljs.highlight(str, { language, ignoreIllegals: true }).value
            : md.utils.escapeHtml(str)
        return `<pre class="hljs" data-lang="${label}"><code class="hljs language-${label}">${escaped}</code></pre>`
    },
})

// Open links in new tab safely
const defaultLinkOpen = md.renderer.rules.link_open
    || function (tokens, idx, opts, env, self) { return self.renderToken(tokens, idx, opts) }
md.renderer.rules.link_open = function (tokens, idx, opts, env, self) {
    tokens[idx].attrSet('target', '_blank')
    tokens[idx].attrSet('rel', 'noopener noreferrer')
    return defaultLinkOpen(tokens, idx, opts, env, self)
}

const html         = computed(() => md.render(props.content || ''))
const containerRef = ref(null)

const COPY_SVG  = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>`
const CHECK_SVG = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`

function attachCopyButtons() {
    if (!containerRef.value) return
    containerRef.value.querySelectorAll('pre.hljs').forEach(pre => {
        if (pre.querySelector('.code-copy-btn')) return

        // Language label
        const lang = pre.dataset.lang || 'text'
        const label = document.createElement('span')
        label.className   = 'code-lang-label'
        label.textContent = lang
        pre.appendChild(label)

        // Copy button
        const btn = document.createElement('button')
        btn.className = 'code-copy-btn'
        btn.innerHTML = COPY_SVG
        btn.title     = 'Copy code'
        btn.addEventListener('click', async () => {
            const code = pre.querySelector('code')?.innerText ?? ''
            try {
                await navigator.clipboard.writeText(code)
                btn.classList.add('copied')
                btn.innerHTML = CHECK_SVG
                setTimeout(() => {
                    btn.classList.remove('copied')
                    btn.innerHTML = COPY_SVG
                }, 1500)
            } catch {}
        })
        pre.appendChild(btn)
    })
}

onMounted(() => nextTick(attachCopyButtons))
onUpdated(() => nextTick(attachCopyButtons))
</script>

<template>
    <div ref="containerRef" class="markdown-body" v-html="html" />
</template>

<style>
/* Global so v-html children are styled */
.markdown-body {
    color: rgb(241 245 249);
    font-size: 0.875rem;
    line-height: 1.65;
    word-wrap: break-word;
}
.markdown-body > *:first-child { margin-top: 0; }
.markdown-body > *:last-child  { margin-bottom: 0; }

.markdown-body p { margin: 0.5rem 0; }

.markdown-body h1, .markdown-body h2, .markdown-body h3,
.markdown-body h4, .markdown-body h5, .markdown-body h6 {
    margin: 1rem 0 0.5rem; font-weight: 600;
    line-height: 1.3; color: rgb(248 250 252);
}
.markdown-body h1 { font-size: 1.25rem;  padding-bottom: 0.3rem;  border-bottom: 1px solid rgb(51 65 85); }
.markdown-body h2 { font-size: 1.125rem; padding-bottom: 0.25rem; border-bottom: 1px solid rgb(51 65 85); }
.markdown-body h3 { font-size: 1rem; }
.markdown-body h4 { font-size: 0.95rem; }
.markdown-body h5, .markdown-body h6 { font-size: 0.875rem; color: rgb(203 213 225); }

.markdown-body strong { color: #fff; font-weight: 600; }
.markdown-body em     { color: rgb(226 232 240); }

.markdown-body ul, .markdown-body ol { margin: 0.5rem 0; padding-left: 1.5rem; }
.markdown-body ul { list-style: disc; }
.markdown-body ol { list-style: decimal; }
.markdown-body li { margin: 0.2rem 0; }
.markdown-body li::marker { color: rgb(129 140 248); }

.markdown-body a {
    color: rgb(129 140 248);
    text-decoration: underline;
    text-decoration-color: rgb(99 102 241 / 0.4);
    text-underline-offset: 2px;
    transition: color 0.15s;
}
.markdown-body a:hover { color: rgb(165 180 252); text-decoration-color: rgb(99 102 241); }

.markdown-body blockquote {
    margin: 0.75rem 0; padding: 0.5rem 0.75rem 0.5rem 1rem;
    border-left: 3px solid rgb(99 102 241);
    background: rgb(30 41 59 / 0.5);
    color: rgb(203 213 225);
    border-radius: 0 0.375rem 0.375rem 0;
    font-style: italic;
}
.markdown-body blockquote > *:first-child { margin-top: 0; }
.markdown-body blockquote > *:last-child  { margin-bottom: 0; }

.markdown-body hr { border: 0; border-top: 1px solid rgb(51 65 85); margin: 1rem 0; }

/* Inline code */
.markdown-body code:not(.hljs) {
    background: rgb(15 23 42);
    color: rgb(252 211 77);
    padding: 0.15rem 0.4rem;
    border-radius: 0.3rem;
    font-size: 0.85em;
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
    border: 1px solid rgb(51 65 85);
}

/* Code blocks */
.markdown-body pre.hljs {
    position: relative;
    margin: 0.75rem 0;
    padding: 2rem 1rem 1rem;
    background: rgb(15 23 42) !important;
    border: 1px solid rgb(51 65 85);
    border-radius: 0.5rem;
    overflow-x: auto;
    font-size: 0.8125rem;
    line-height: 1.55;
}
.markdown-body pre.hljs::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1.5rem;
    background: rgb(30 41 59);
    border-bottom: 1px solid rgb(51 65 85);
    border-radius: 0.5rem 0.5rem 0 0;
}
.markdown-body pre.hljs code.hljs {
    background: transparent !important;
    padding: 0 !important;
    color: rgb(226 232 240);
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
    border: 0;
}

.markdown-body .code-lang-label {
    position: absolute; top: 0.25rem; left: 0.75rem;
    font-size: 0.7rem; font-weight: 500;
    color: rgb(148 163 184);
    letter-spacing: 0.05em;
    text-transform: uppercase;
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
}

.markdown-body .code-copy-btn {
    position: absolute; top: 0.2rem; right: 0.4rem;
    padding: 0.25rem 0.4rem; background: transparent;
    color: rgb(148 163 184); border: 0; border-radius: 0.25rem;
    cursor: pointer; display: flex; align-items: center;
    transition: color 0.15s, background 0.15s;
}
.markdown-body .code-copy-btn:hover  { color: rgb(241 245 249); background: rgb(51 65 85); }
.markdown-body .code-copy-btn.copied { color: rgb(74 222 128); }

/* Tables */
.markdown-body table {
    width: 100%; margin: 0.75rem 0;
    border-collapse: collapse;
    font-size: 0.8125rem;
    border-radius: 0.375rem;
    border: 1px solid rgb(51 65 85);
    overflow: hidden;
}
.markdown-body thead { background: rgb(30 41 59); }
.markdown-body th {
    padding: 0.5rem 0.75rem; text-align: left;
    font-weight: 600; color: rgb(248 250 252);
    border-bottom: 1px solid rgb(51 65 85);
}
.markdown-body td {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid rgb(51 65 85 / 0.5);
    color: rgb(226 232 240);
}
.markdown-body tbody tr:last-child td { border-bottom: 0; }
.markdown-body tbody tr:hover         { background: rgb(30 41 59 / 0.4); }

.markdown-body img {
    max-width: 100%; border-radius: 0.375rem; margin: 0.5rem 0;
}
.markdown-body input[type="checkbox"] { margin-right: 0.4rem; }
</style>
