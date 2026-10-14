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
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#5E72E4',
                    dark: '#172B4D',
                    light: '#8392AB',
                },
                success: {
                    DEFAULT: '#2DCE89',
                    dark: '#1A744C',
                    light: '#6EE7B7',
                },
                danger: {
                    DEFAULT: '#FB6340',
                    dark: '#C83A1B',
                    light: '#FCA5A5',
                },
                neutral: {
                    100: '#F7FAFC',
                    200: '#E9ECEF',
                    300: '#DEE2E6',
                    400: '#CED4DA',
                    500: '#ADB5BD',
                    600: '#6C757D',
                    700: '#495057',
                    800: '#343A40',
                    900: '#212529',
                },
            },
        },
    },

    plugins: [forms],
};
