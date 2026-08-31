import * as esbuild from 'esbuild'

const isDev = process.argv.includes('--dev')

async function compile(options) {
    const context = await esbuild.context(options)

    if (isDev) {
        await context.watch()
    } else {
        await context.rebuild()
        await context.dispose()
    }
}

const defaultOptions = {
    define: {
        'process.env.NODE_ENV': isDev ? `'development'` : `'production'`,
    },
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    sourcemap: isDev ? 'inline' : false,
    sourcesContent: isDev,
    treeShaking: true,
    target: ['es2020'],
    minify: !isDev,
}

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/description-list.js'],
    outfile: './resources/dist/description-list.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/html-extensions.js'],
    outfile: './resources/dist/html-extensions.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/condition.js'],
    outfile: './resources/dist/condition.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/twig-rich-editor.js'],
    outfile: './resources/dist/twig-rich-editor.js',
})
