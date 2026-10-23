import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'street-orange': {
                    50: '#fff7f0',
                    100: '#ffedde',
                    200: '#ffdac2',
                    300: '#ffc2a2',
                    400: '#ff8c42',
                    500: '#ff7a1e',
                    600: '#e67e2d',
                    700: '#cc6b1a',
                    800: '#b35a12',
                    900: '#8a4410',
                },
            },
        },
    },

    plugins: [
        forms,
        require('@tailwindcss/typography'),
    ],
};
