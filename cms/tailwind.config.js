import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#fcf5f0',
                    100: '#f8e6da',
                    200: '#f0cab3',
                    300: '#e3a07a',
                    400: '#d5825a',
                    500: '#c46a42',
                    600: '#b45c36',
                    700: '#a9532f',
                    800: '#8f4426',
                    900: '#6f3620',
                    950: '#3d1d11',
                },
                cream: '#fbf8f4',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'grow-x': {
                    '0%': { transform: 'scaleX(0)' },
                    '100%': { transform: 'scaleX(1)' },
                },
                'toast-in': {
                    '0%': { opacity: '0', transform: 'translateY(-8px) scale(0.98)' },
                    '100%': { opacity: '1', transform: 'translateY(0) scale(1)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.5s cubic-bezier(0.2, 0.7, 0.2, 1) both',
                'fade-in': 'fade-in 0.3s ease-out both',
                'grow-x': 'grow-x 0.8s cubic-bezier(0.2, 0.7, 0.2, 1) both',
                'toast-in': 'toast-in 0.35s cubic-bezier(0.2, 0.7, 0.2, 1) both',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
