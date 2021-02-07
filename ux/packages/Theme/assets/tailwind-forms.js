const plugin = require('tailwindcss/plugin')

module.exports = plugin(function ({addBase, theme}) {
    addBase({
        "[type='text'],[type='email'],[type='url'],[type='password'],[type='number'],[type='date'],[type='datetime-local'],[type='month'],[type='search'],[type='tel'],[type='time'],[type='week'],[multiple],textarea,select": {
            width: '100%',
            borderColor: theme('colors.gray.300'),
            borderRadius: theme('borderRadius.md'),
            fontSize: theme('fontSize.sm'),
            '--tw-shadow': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
            'box-shadow': 'var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)',
            '&:focus': {
                '--tw-ring-color': theme('colors.primary.500'),
                //'--tw-ring-offset-width': '1px',
                //'--tw-ring-shadow': `var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color)`,
                'border-color': theme('colors.primary.500'),
            },
            '&:disabled': {
                'opacity': '.5',
            },
        },
        "[type='checkbox'], [type='radio']": {
            borderColor: theme('colors.gray.400'),
            color: theme('colors.primary.DEFAULT'),
            height: theme('spacing.3'),
            width: theme('spacing.3'),
        },
        "[type='checkbox']": {
            borderRadius: theme('borderRadius.sm'),
        },
    })
})
