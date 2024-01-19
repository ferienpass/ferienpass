const colors = require('tailwindcss/colors')

module.exports = {
    darkMode: 'media',
    theme: {
        extend: {
            colors: {
                primary: {
                    50: 'var(--color-primary-50, #d7edf5)',
                    100: 'var(--color-primary-100, #C7E7F2)',
                    200: 'var(--color-primary-200, #9AC8D9)',
                    300: 'var(--color-primary-300, #22A6D6)',
                    400: 'var(--color-primary-400, #1C89B0)',
                    500: 'var(--color-primary-500, #1D8DB9)',
                    600: 'var(--color-primary-600, #1c6b9c)',
                    700: 'var(--color-primary-700, #185A85)',
                    800: 'var(--color-primary-800, #084F69)',
                    900: 'var(--color-primary-900, #0C394A)',
                },
                tint: {
                    'DEFAULT': 'var(--color-tint, #e08b2d)',
                    50: 'var(--color-tint-50, #e08b2d)',
                    100: 'var(--color-tint-100, #e08b2d)',
                    200: 'var(--color-tint-200, #e08b2d)',
                    300: 'var(--color-tint-300, #e08b2d)',
                    400: 'var(--color-tint-400, #e08b2d)',
                    500: 'var(--color-tint-500, #e08b2d)',
                    600: 'var(--color-tint-600, #e08b2d)',
                    700: 'var(--color-tint-700, #e08b2d)',
                    800: 'var(--color-tint-800, #e08b2d)',
                    900: 'var(--color-tint-900, #e08b2d)',
                },
            }
        }
    },
    plugins: [
        require('@tailwindcss/typography'),
        require('@tailwindcss/forms'),
        require('./tailwind-forms'),
    ],
}
