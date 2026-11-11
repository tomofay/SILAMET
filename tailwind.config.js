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
            fontFamily: {
                sans: ['Inter', 'Outfit', 'Figtree', ...defaultTheme.fontFamily.sans],
                heading: ['Inter', 'Outfit', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', 'Fira Code', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                primary: {
                    DEFAULT: '#4F46E5', // Modern Indigo
                    50: '#EEF2FF',
                    100: '#E0E7FF',
                    400: '#818CF8',
                    500: '#6366F1',
                    600: '#4F46E5',
                    700: '#4338CA',
                    dark: '#0F172A',    // Slate 900
                    light: '#818CF8',   // Indigo 400
                    soft: '#EEF2FF',    // Indigo 50
                    surface: '#1E1B4B', // Indigo 950
                },
                brand: {
                    50: '#F0F9FF',
                    100: '#E0F2FE',
                    500: '#0EA5E9',
                    600: '#0284C7',
                    700: '#0369A1',
                },
                success: {
                    DEFAULT: '#10B981', // Emerald 500
                    dark: '#065F46',    // Emerald 800
                    light: '#D1FAE5',   // Emerald 100
                },
                danger: {
                    DEFAULT: '#EF4444', // Red 500
                    dark: '#991B1B',    // Red 800
                    light: '#FEE2E2',   // Red 100
                },
                warning: {
                    DEFAULT: '#F59E0B', // Amber 500
                    dark: '#B45309',    // Amber 700
                    light: '#FEF3C7',   // Amber 100
                },
                info: {
                    DEFAULT: '#06B6D4', // Cyan 500
                    dark: '#0E7490',    // Cyan 700
                    light: '#CFFAFE',   // Cyan 100
                },
                neutral: {
                    50: '#F8FAFC',
                    100: '#F1F5F9',
                    200: '#E2E8F0',
                    300: '#CBD5E1',
                    400: '#94A3B8',
                    500: '#64748B',
                    600: '#475569',
                    700: '#334155',
                    800: '#1E293B',
                    900: '#0F172A',
                    950: '#020617',
                },
            },
        },
    },

    plugins: [forms],
};
