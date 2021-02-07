const plugin = require('tailwindcss/plugin')

const base = plugin(function ({addBase, theme}) {
    addBase({
        'h1': {
            fontSize: theme('fontSize.3xl'),
            lineHeight: theme('lineHeight.9'),
            letterSpacing: theme('letterSpacing.light'),
            fontWeight: theme('fontWeight.extrabold'),
            color: theme('colors.gray.900')
        },
        'h2': {
            fontSize: theme('fontSize.2xl'),
            lineHeight: theme('lineHeight.7'),
            fontWeight: theme('fontWeight.bold'),
            color: theme('colors.gray.900')
        },
        'h3': {
            fontSize: theme('fontSize.lg'),
            fontWeight: theme('fontWeight.medium'),
            color: theme('colors.gray.900')
        },
    })
})

module.exports = base
