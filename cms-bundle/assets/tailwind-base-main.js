const plugin = require('tailwindcss/plugin')

const base = plugin(function ({addBase, theme}) {
    addBase({
        'h1': {
            fontSize: theme('fontSize.4xl'),
            fontWeight: theme('fontWeight.bold'),
            lineHeight: theme('lineHeight.9'),
            color: theme('colors.gray.900')
        },
        'h2': {
            fontSize: theme('fontSize.3xl'),
            fontWeight: theme('fontWeight.bold'),
            color: theme('colors.gray.900'),
        },
        'h3': {
            fontSize: theme('fontSize.lg'),
            fontWeight: theme('fontWeight.medium'),
            color: theme('colors.gray.900')
        },
        '[x-cloak]': {
            display: 'none',
        },
    })
})

module.exports = base
