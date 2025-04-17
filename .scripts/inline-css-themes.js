const fs = require('fs')
const path = require('path')
const postcss = require('postcss')
const postcssCssVariables = require('postcss-css-variables')
const cssnano = require('cssnano')

const root = path.resolve(__dirname, '..')

const corkThemeFiles = [
    `${root}/public/static/themes/cork.css`,
    `${root}/public/static/style.css`
]

const lightThemeFiles = [
    `${root}/public/static/themes/light.css`,
    `${root}/public/static/style.css`
]

const darkThemeFiles = [
    `${root}/public/static/themes/dark.css`,
    `${root}/public/static/style.css`
]

const corkCombinedCss = corkThemeFiles.map(file => fs.readFileSync(file, 'utf8')).join('\n')
const lightCombinedCss = lightThemeFiles.map(file => fs.readFileSync(file, 'utf8')).join('\n')
const darkCombinedCss = darkThemeFiles.map(file => fs.readFileSync(file, 'utf8')).join('\n')

const cork = postcss([postcssCssVariables, cssnano]).process(corkCombinedCss, {from: undefined})
const light = postcss([postcssCssVariables, cssnano]).process(lightCombinedCss, {from: undefined})
const dark = postcss([postcssCssVariables, cssnano]).process(darkCombinedCss, {from: undefined})

Promise
    .all([cork, light, dark])
    .then(([corkResult, lightResult, darkResult]) => {
        const twigThemePicker = `
            {% if theme == "cork" %}<style>${corkResult.css}</style>
            {% elseif theme == "light" %}<style>${lightResult.css}</style>
            {% elseif theme == "dark" %}<style>${darkResult.css}</style>
            {% endif %}
        `

        const stylesheetRegex = /(<link rel="stylesheet[\S\s]*style.css">)/g
        const layoutTwig = fs.readFileSync(`${root}/templates/layout.twig`, 'utf8')
        const newLayoutTwig = layoutTwig.replace(stylesheetRegex, twigThemePicker)
        fs.writeFileSync(`${root}/templates/layout.twig`, newLayoutTwig)

        console.info('Inlined the twig theme picker into layout.twig')
    })
    .catch(error => console.error('Error processing CSS:', error))